/// Kesalahan API dalam satu bentuk, sudah diterjemahkan ke pesan yang layak
/// ditampilkan ke pengguna.
class ApiException implements Exception {
  const ApiException({
    required this.message,
    this.code,
    this.statusCode,
    this.fieldErrors = const {},
  });

  final String message;

  /// Kode opsional dari server (bila ada).
  final String? code;

  final int? statusCode;

  /// Kesalahan per kolom dari respons 422, siap dipasang ke form.
  final Map<String, String> fieldErrors;

  bool get isValidation => statusCode == 422;

  /// Token ditolak: sesi habis, dicabut, atau akun dinonaktifkan.
  bool get isUnauthenticated => statusCode == 401;

  bool get isForbidden => statusCode == 403;

  bool get isNotFound => statusCode == 404;

  bool get isRateLimited => statusCode == 429;

  /// Tidak ada balasan dari server sama sekali (jaringan / alamat salah).
  bool get isNetwork => statusCode == null;

  /// Pesan untuk satu kolom form.
  String? forField(String field) => fieldErrors[field];

  @override
  String toString() => 'ApiException($statusCode, $code): $message';
}
