import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../config/app_config.dart';
import 'api_exception.dart';

/// Pembungkus Dio untuk seluruh panggilan API OtoGo.
///
/// API mengikuti gaya Laravel API Resource:
/// - sukses  : `{ "data": ..., "meta"?: {...} }`
/// - galat   : `{ "message": "...", "errors"?: { field: [pesan] } }`
///
/// Pembongkaran envelope dipusatkan di sini, jadi repository cukup menerima
/// isi `data` yang sudah bersih (atau envelope utuh lewat [getEnvelope] bila
/// butuh `meta` pagination).
class ApiClient {
  ApiClient() {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.apiUrl,
        connectTimeout: AppConfig.connectTimeout,
        receiveTimeout: AppConfig.receiveTimeout,
        headers: const {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        // Status non-2xx tetap masuk ke response supaya pesan server bisa
        // dipetakan sendiri menjadi ApiException.
        validateStatus: (status) => status != null && status < 600,
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          if (_token != null) {
            options.headers['Authorization'] = 'Bearer $_token';
          }

          return handler.next(options);
        },
      ),
    );

    if (kDebugMode) {
      _dio.interceptors.add(
        LogInterceptor(
          request: false,
          requestHeader: false,
          requestBody: false,
          responseHeader: false,
          responseBody: false,
        ),
      );
    }
  }

  late final Dio _dio;

  String? _token;

  /// Dipanggil saat server menolak token (401), supaya aplikasi langsung
  /// kembali ke halaman login.
  void Function()? onUnauthenticated;

  void setToken(String? token) => _token = token;

  String? get token => _token;

  bool get hasToken => _token != null && _token!.isNotEmpty;

  /// Mengarahkan ulang seluruh permintaan berikutnya ke alamat baru.
  void setBaseUrl(String baseUrl) => _dio.options.baseUrl = '$baseUrl/api/v1';

  String get baseUrl => _dio.options.baseUrl;

  /// Menguji sebuah alamat dengan memanggil `/api/health`.
  ///
  /// Memakai instans Dio tersendiri: pengujian tidak boleh mengubah alamat
  /// aktif sebelum pengguna menyimpannya, dan tidak membawa token lama.
  Future<({bool berhasil, String pesan})> cekAlamat(String baseUrl) async {
    final penguji = Dio(
      BaseOptions(
        connectTimeout: const Duration(seconds: 8),
        receiveTimeout: const Duration(seconds: 8),
        headers: const {'Accept': 'application/json'},
        validateStatus: (status) => status != null && status < 600,
      ),
    );

    try {
      final response = await penguji.get('$baseUrl/api/health');
      final body = asMap(response.data);

      if (response.statusCode == 200 && body['status'] == 'ok') {
        final nama = body['app'] ?? 'API';

        return (berhasil: true, pesan: 'Tersambung ke $nama.');
      }

      return (
        berhasil: false,
        pesan: 'Server menjawab kode ${response.statusCode}, tetapi bukan API OtoGo.',
      );
    } on DioException catch (e) {
      return (berhasil: false, pesan: _fromDio(e, baseUrl).message);
    } catch (e) {
      return (berhasil: false, pesan: 'Gagal menghubungi alamat itu.');
    } finally {
      penguji.close();
    }
  }

  // ------------------------------------------------------------------ verbs

  Future<dynamic> get(String path, {Map<String, dynamic>? query}) =>
      _send(() => _dio.get(path, queryParameters: _clean(query)));

  Future<dynamic> post(
    String path, {
    Object? body,
    Map<String, dynamic>? query,
    Duration? receiveTimeout,
  }) =>
      _send(() => _dio.post(
            path,
            data: body,
            queryParameters: _clean(query),
            options: receiveTimeout == null ? null : Options(receiveTimeout: receiveTimeout),
          ));

  Future<dynamic> put(String path, {Object? body}) => _send(() => _dio.put(path, data: body));

  Future<dynamic> delete(String path, {Object? body}) =>
      _send(() => _dio.delete(path, data: body));

  /// Respons lengkap (dipakai saat `meta` pagination dibutuhkan).
  Future<Map<String, dynamic>> getEnvelope(String path, {Map<String, dynamic>? query}) async {
    final response = await _guard(() => _dio.get(path, queryParameters: _clean(query)));
    final body = asMap(response.data);

    _throwIfError(response.statusCode, body);

    return body;
  }

  /// POST yang mengembalikan envelope utuh (mis. asisten: `{ok, data}`).
  Future<Map<String, dynamic>> postEnvelope(
    String path, {
    Object? body,
    Duration? receiveTimeout,
  }) async {
    final response = await _guard(
      () => _dio.post(
        path,
        data: body,
        options: receiveTimeout == null ? null : Options(receiveTimeout: receiveTimeout),
      ),
    );
    final map = asMap(response.data);

    _throwIfError(response.statusCode, map);

    return map;
  }

  // ------------------------------------------------------------------ core

  Future<dynamic> _send(Future<Response<dynamic>> Function() call) async {
    final response = await _guard(call);
    final body = asMap(response.data);

    _throwIfError(response.statusCode, body);

    // Sebagian endpoint (logout, ganti password) hanya membalas {message}.
    return body.containsKey('data') ? body['data'] : body;
  }

  Future<T> _guard<T>(Future<T> Function() call) async {
    try {
      return await call();
    } on DioException catch (e) {
      throw _fromDio(e, AppConfig.baseUrl);
    }
  }

  void _throwIfError(int? status, Map<String, dynamic> body) {
    final ok = status != null && status >= 200 && status < 300;

    if (ok) return;

    final pesanServer = body['message'];
    final exception = ApiException(
      message: pesanServer is String && pesanServer.trim().isNotEmpty
          ? _ramah(status, pesanServer)
          : _pesanBawaan(status),
      code: body['code']?.toString(),
      statusCode: status,
      fieldErrors: fieldErrors(body['errors']),
    );

    if (exception.isUnauthenticated && hasToken) {
      onUnauthenticated?.call();
    }

    throw exception;
  }

  /// Pesan bawaan Laravel berbahasa Inggris diganti yang lebih jelas.
  static String _ramah(int? status, String pesan) {
    final p = pesan.trim();

    if (status == 401 && p.toLowerCase() == 'unauthenticated.') {
      return 'Sesi berakhir. Silakan masuk kembali.';
    }

    if (status == 429 && p.toLowerCase().startsWith('too many')) {
      return 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.';
    }

    return p;
  }

  static String _pesanBawaan(int? status) => switch (status) {
        401 => 'Sesi berakhir. Silakan masuk kembali.',
        403 => 'Akses ditolak untuk akun ini.',
        404 => 'Data tidak ditemukan.',
        419 || 422 => 'Data yang dikirim belum valid.',
        429 => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
        503 => 'Server sedang dalam perawatan. Coba lagi nanti.',
        final s? when s >= 500 => 'Server sedang bermasalah (kode $s). Coba lagi nanti.',
        _ => 'Permintaan gagal${status == null ? '' : ' (kode $status)'}.',
      };

  static ApiException _fromDio(DioException e, String alamat) {
    final message = switch (e.type) {
      DioExceptionType.connectionTimeout =>
        'Server tidak merespons. Pastikan API berjalan di $alamat.',
      DioExceptionType.sendTimeout => 'Waktu pengiriman data habis. Coba lagi.',
      DioExceptionType.receiveTimeout => 'Server terlalu lama merespons. Coba lagi.',
      DioExceptionType.connectionError =>
        'Tidak dapat terhubung ke $alamat. Periksa jaringan dan alamat API.',
      DioExceptionType.cancel => 'Permintaan dibatalkan.',
      DioExceptionType.badCertificate => 'Sertifikat server tidak valid.',
      _ => 'Terjadi gangguan jaringan. Coba lagi.',
    };

    final data = e.response?.data;

    if (data is Map) {
      final body = asMap(data);
      final serverMessage = body['message'];

      if (serverMessage is String && serverMessage.trim().isNotEmpty) {
        return ApiException(
          message: serverMessage,
          statusCode: e.response?.statusCode,
          fieldErrors: fieldErrors(body['errors']),
        );
      }
    }

    return ApiException(message: message, statusCode: e.response?.statusCode);
  }

  static Map<String, dynamic> asMap(dynamic data) {
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return data.cast<String, dynamic>();

    return <String, dynamic>{};
  }

  /// `{ "email": ["Email sudah dipakai."] }` -> `{ "email": "Email sudah dipakai." }`
  static Map<String, String> fieldErrors(dynamic errors) {
    if (errors is! Map) return const {};

    final out = <String, String>{};

    errors.forEach((key, value) {
      if (value is List && value.isNotEmpty) {
        out[key.toString()] = value.first.toString();
      } else if (value is String) {
        out[key.toString()] = value;
      }
    });

    return out;
  }

  /// Buang parameter query yang null/kosong agar URL tetap bersih.
  static Map<String, dynamic>? _clean(Map<String, dynamic>? query) {
    if (query == null) return null;

    final out = <String, dynamic>{};

    query.forEach((key, value) {
      if (value == null) return;
      if (value is String && value.trim().isEmpty) return;

      out[key] = value;
    });

    return out.isEmpty ? null : out;
  }
}
