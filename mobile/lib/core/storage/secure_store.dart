import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Penyimpanan di perangkat.
///
/// Di Android nilainya tersimpan terenkripsi lewat Keystore. Password
/// pengguna TIDAK pernah disimpan -- hanya token API terbitan server, yang
/// bisa dicabut kapan saja dari sisi server.
class SecureStore {
  const SecureStore();

  static const _storage = FlutterSecureStorage();

  static const _kAccessToken = 'access_token';
  static const _kSavedEmail = 'saved_email';
  static const _kSavedName = 'saved_name';
  static const _kBaseUrl = 'api_base_url';
  static const _kThemeMode = 'theme_mode';

  // ---------------------------------------------------------- access token

  Future<void> saveSession({
    required String accessToken,
    required String email,
    required String name,
  }) async {
    await _storage.write(key: _kAccessToken, value: accessToken);
    await _storage.write(key: _kSavedEmail, value: email);
    await _storage.write(key: _kSavedName, value: name);
  }

  Future<String?> readAccessToken() => _safeRead(_kAccessToken);

  Future<void> clearAccessToken() => _safeDelete(_kAccessToken);

  // ------------------------------------------------------------- identitas

  Future<String?> readSavedEmail() => _safeRead(_kSavedEmail);

  Future<String?> readSavedName() => _safeRead(_kSavedName);

  // -------------------------------------------------------- alamat API

  /// Alamat API pilihan pengguna. null berarti memakai bawaan.
  Future<String?> readBaseUrl() => _safeRead(_kBaseUrl);

  Future<void> saveBaseUrl(String url) => _storage.write(key: _kBaseUrl, value: url);

  Future<void> clearBaseUrl() => _safeDelete(_kBaseUrl);

  // ------------------------------------------------------------- tampilan

  /// 'system' | 'light' | 'dark'
  Future<String?> readThemeMode() => _safeRead(_kThemeMode);

  Future<void> saveThemeMode(String mode) => _storage.write(key: _kThemeMode, value: mode);

  // --------------------------------------------------------------- keluar

  Future<void> logout() => clearAccessToken();

  /// Menghapus seluruh sesi. Alamat API dan pilihan tema SENGAJA tidak ikut
  /// dihapus: pengguna yang keluar tetap ingin kembali ke server yang sama.
  Future<void> wipe() async {
    await _safeDelete(_kAccessToken);
    await _safeDelete(_kSavedEmail);
    await _safeDelete(_kSavedName);
  }

  /// Storage browser bisa diblokir (mode privat) atau rusak setelah kunci
  /// enkripsi berganti. Kegagalan membaca diperlakukan sebagai "tidak ada",
  /// bukan crash di layar pembuka.
  Future<String?> _safeRead(String key) async {
    try {
      return await _storage.read(key: key);
    } catch (_) {
      return null;
    }
  }

  Future<void> _safeDelete(String key) async {
    try {
      await _storage.delete(key: key);
    } catch (_) {
      // abaikan; tidak ada yang perlu dihapus
    }
  }
}
