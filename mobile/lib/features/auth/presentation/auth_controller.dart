import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/network/api_exception.dart';
import '../../../core/providers.dart';
import '../../../core/storage/secure_store.dart';
import '../data/auth_repository.dart';
import '../domain/user.dart';

enum AuthStatus {
  /// Sedang memulihkan sesi saat aplikasi dibuka.
  memeriksa,

  /// Belum login -- tampilkan halaman masuk.
  keluar,

  /// Sudah login.
  masuk,
}

class AuthState {
  const AuthState({
    this.status = AuthStatus.memeriksa,
    this.user,
    this.savedEmail,
    this.sedangProses = false,
    this.error,
    this.fieldErrors = const {},
  });

  final AuthStatus status;
  final AppUser? user;

  /// Email terakhir yang berhasil masuk, untuk mengisi form otomatis.
  final String? savedEmail;

  final bool sedangProses;
  final String? error;
  final Map<String, String> fieldErrors;

  bool get sudahMasuk => status == AuthStatus.masuk && user != null;

  AuthState copyWith({
    AuthStatus? status,
    AppUser? user,
    bool hapusUser = false,
    String? savedEmail,
    bool? sedangProses,
    String? error,
    bool hapusError = false,
    Map<String, String>? fieldErrors,
  }) =>
      AuthState(
        status: status ?? this.status,
        user: hapusUser ? null : (user ?? this.user),
        savedEmail: savedEmail ?? this.savedEmail,
        sedangProses: sedangProses ?? this.sedangProses,
        error: hapusError ? null : (error ?? this.error),
        fieldErrors: fieldErrors ?? this.fieldErrors,
      );
}

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._ref) : super(const AuthState()) {
    _api.onUnauthenticated = _paksaKeluar;
  }

  final Ref _ref;

  AuthRepository get _repo => _ref.read(authRepositoryProvider);

  SecureStore get _store => _ref.read(secureStoreProvider);

  ApiClient get _api => _ref.read(apiClientProvider);

  // ------------------------------------------------------------------ start

  /// Dipanggil sekali saat aplikasi mulai: token tersimpan diverifikasi ke
  /// server lewat `GET /me`. Bila ditolak, pengguna diarahkan ke login.
  ///
  /// Bila server tidak terjangkau (bukan menolak), sesi TIDAK dibuang --
  /// pengguna cukup mencoba lagi setelah jaringan pulih.
  Future<void> bootstrap() async {
    final email = await _store.readSavedEmail();

    state = state.copyWith(savedEmail: email);

    final token = await _store.readAccessToken();

    if (token == null || token.isEmpty) {
      state = state.copyWith(status: AuthStatus.keluar);

      return;
    }

    _api.setToken(token);

    try {
      final user = await _repo.profil();

      state = state.copyWith(status: AuthStatus.masuk, user: user);
    } on ApiException catch (e) {
      if (e.isNetwork || (e.statusCode ?? 0) >= 500) {
        // Server tidak bisa dihubungi: tampilkan halaman masuk dengan pesan,
        // tetapi simpan tokennya agar sesi pulih begitu server kembali.
        _api.setToken(null);
        state = state.copyWith(status: AuthStatus.keluar, error: e.message);

        return;
      }

      _api.setToken(null);
      await _store.clearAccessToken();
      state = state.copyWith(status: AuthStatus.keluar, hapusUser: true);
    }
  }

  // ------------------------------------------------------------------ masuk

  Future<bool> login({required String email, required String password}) async {
    state = state.copyWith(sedangProses: true, hapusError: true, fieldErrors: const {});

    try {
      final sesi = await _repo.login(
        email: email,
        password: password,
        deviceName: await _namaPerangkat(),
      );

      await _terapkanSesi(sesi);

      return true;
    } on ApiException catch (e) {
      state = state.copyWith(sedangProses: false, error: e.message, fieldErrors: e.fieldErrors);

      return false;
    }
  }

  Future<bool> daftar(DaftarInput input) async {
    state = state.copyWith(sedangProses: true, hapusError: true, fieldErrors: const {});

    try {
      final sesi = await _repo.daftar(input, deviceName: await _namaPerangkat());

      await _terapkanSesi(sesi);

      return true;
    } on ApiException catch (e) {
      state = state.copyWith(sedangProses: false, error: e.message, fieldErrors: e.fieldErrors);

      return false;
    }
  }

  // --------------------------------------------------------------- profil

  Future<ApiException?> simpanProfil({required String name, String? phone}) async {
    state = state.copyWith(sedangProses: true, hapusError: true, fieldErrors: const {});

    try {
      final user = await _repo.updateProfil(name: name, phone: phone);

      state = state.copyWith(sedangProses: false, user: user);

      return null;
    } on ApiException catch (e) {
      state = state.copyWith(sedangProses: false, fieldErrors: e.fieldErrors);

      return e;
    }
  }

  Future<ApiException?> gantiPassword({
    required String passwordSaatIni,
    required String passwordBaru,
    required String konfirmasi,
  }) async {
    state = state.copyWith(sedangProses: true, hapusError: true, fieldErrors: const {});

    try {
      await _repo.gantiPassword(
        passwordSaatIni: passwordSaatIni,
        passwordBaru: passwordBaru,
        konfirmasi: konfirmasi,
      );

      state = state.copyWith(sedangProses: false);

      return null;
    } on ApiException catch (e) {
      state = state.copyWith(sedangProses: false, fieldErrors: e.fieldErrors);

      return e;
    }
  }

  Future<void> muatUlangProfil() async {
    try {
      final user = await _repo.profil();

      state = state.copyWith(user: user);
    } on ApiException {
      // biarkan profil lama; kegagalan sementara tidak perlu mengusir pengguna
    }
  }

  /// Mengganti alamat API lalu mengakhiri sesi (token server lama tidak
  /// berlaku di server baru). null = kembali ke alamat bawaan.
  Future<void> gantiAlamatApi(String? alamat) async {
    if (alamat == null) {
      await _store.clearBaseUrl();
    } else {
      await _store.saveBaseUrl(alamat);
    }

    AppConfig.terapkan(alamat);
    _api.setBaseUrl(AppConfig.baseUrl);
    _api.setToken(null);
    await _store.wipe();

    state = const AuthState(status: AuthStatus.keluar);
  }

  // ----------------------------------------------------------------- keluar

  Future<void> logout() async {
    state = state.copyWith(sedangProses: true);

    try {
      await _repo.logout();
    } on ApiException {
      // token mungkin sudah tidak berlaku di server -- tetap bersihkan lokal
    }

    _api.setToken(null);
    await _store.logout();

    state = AuthState(status: AuthStatus.keluar, savedEmail: state.savedEmail);
  }

  void _paksaKeluar() {
    if (state.status != AuthStatus.masuk) return;

    _api.setToken(null);
    _store.clearAccessToken();

    state = state.copyWith(
      status: AuthStatus.keluar,
      hapusUser: true,
      sedangProses: false,
      error: 'Sesi berakhir. Silakan masuk kembali.',
    );
  }

  void bersihkanError() => state = state.copyWith(hapusError: true, fieldErrors: const {});

  // ------------------------------------------------------------------ utils

  Future<void> _terapkanSesi(AuthSession sesi) async {
    _api.setToken(sesi.token);

    await _store.saveSession(
      accessToken: sesi.token,
      email: sesi.user.email,
      name: sesi.user.name,
    );

    state = state.copyWith(
      status: AuthStatus.masuk,
      user: sesi.user,
      savedEmail: sesi.user.email,
      sedangProses: false,
      hapusError: true,
      fieldErrors: const {},
    );
  }

  String? _cacheNamaPerangkat;

  /// Nama perangkat dikirim sebagai nama token, supaya pengguna bisa
  /// mengenali sesinya di daftar perangkat.
  Future<String> _namaPerangkat() async {
    if (_cacheNamaPerangkat != null) return _cacheNamaPerangkat!;

    try {
      final info = DeviceInfoPlugin();

      if (kIsWeb) {
        final web = await info.webBrowserInfo;
        _cacheNamaPerangkat = 'OtoGo Web (${web.browserName.name})';
      } else if (defaultTargetPlatform == TargetPlatform.android) {
        final android = await info.androidInfo;
        _cacheNamaPerangkat = '${android.manufacturer} ${android.model}'.trim();
      } else if (defaultTargetPlatform == TargetPlatform.iOS) {
        final ios = await info.iosInfo;
        _cacheNamaPerangkat = ios.name;
      } else {
        _cacheNamaPerangkat = 'OtoGo ${defaultTargetPlatform.name}';
      }
    } catch (_) {
      _cacheNamaPerangkat = 'OtoGo Mobile';
    }

    return _cacheNamaPerangkat ?? 'OtoGo Mobile';
  }
}

final authControllerProvider = StateNotifierProvider<AuthController, AuthState>(
  (ref) => AuthController(ref),
);
