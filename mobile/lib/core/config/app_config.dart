import 'package:flutter/foundation.dart';

/// Konfigurasi lingkungan aplikasi.
///
/// Alamat API punya tiga sumber, urut dari yang paling menang:
///
/// 1. **Tersimpan di perangkat** — diubah pengguna lewat Profil > Alamat API.
///    Pilihan pengguna selalu menang, termasuk atas nilai yang dikompilasi,
///    karena itulah yang bisa diperbaiki tanpa build ulang saat server pindah.
/// 2. **`--dart-define` saat build** —
///    `flutter build apk --dart-define=API_BASE_URL=https://api.contoh.id`
/// 3. **Bawaan menurut mode build** — debug menunjuk ke server lokal
///    (`php artisan serve --port=8822`), supaya `start-dev.bat` langsung jalan.
class AppConfig {
  const AppConfig._();

  static const String appName = 'OtoGo';
  static const String appVersion = '1.0.0';
  static const String kota = 'Medan';

  /// Port `php artisan serve` milik proyek oto-go (lihat APP_URL di .env).
  static const int portLokal = 8822;

  /// Alamat produksi untuk APK rilis bila tidak ditimpa `--dart-define`.
  ///
  /// GANTI sebelum rilis ke Play Store, atau selalu build dengan
  /// `--dart-define=API_BASE_URL=...`.
  static const String baseUrlProduksi = 'https://otogo.id';

  static const String _dariBuild = String.fromEnvironment('API_BASE_URL');

  /// Nilai pilihan pengguna, dimuat sekali di `main()` sebelum runApp.
  ///
  /// Disimpan statis karena ApiClient membutuhkannya secara sinkron saat
  /// dibangun, sedangkan pembacaan storage bersifat asinkron.
  static String? _tersimpan;

  /// Dipanggil `main()` setelah membaca storage, dan oleh lembar Alamat API
  /// setiap kali pengguna menyimpan alamat baru.
  static void terapkan(String? url) {
    final bersih = normalkan(url);

    _tersimpan = bersih.isEmpty ? null : bersih;
  }

  /// Alamat bawaan menurut mode build dan platform.
  ///
  /// Emulator Android tidak bisa menjangkau 127.0.0.1 milik komputer (alamat
  /// itu menunjuk ke emulator sendiri); aliasnya 10.0.2.2. Untuk perangkat
  /// fisik, ubah lewat Profil > Alamat API ke IP komputer di Wi-Fi yang sama.
  static String get baseUrlBawaan {
    if (_dariBuild.isNotEmpty) return normalkan(_dariBuild);

    if (kReleaseMode) return baseUrlProduksi;

    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:$portLokal';
    }

    return 'http://127.0.0.1:$portLokal';
  }

  static String get baseUrl => _tersimpan ?? baseUrlBawaan;

  /// Semua endpoint aplikasi berada di bawah `/api/v1`.
  static String get apiUrl => '$baseUrl/api/v1';

  /// `/api/health` berada di luar `/v1`; dipakai untuk "Uji Koneksi".
  static String get healthUrl => '$baseUrl/api/health';

  /// Dokumentasi Swagger, ditampilkan di halaman Profil.
  static String get swaggerUrl => '$baseUrl/api/documentation';

  static bool get pakaiAlamatKustom => _tersimpan != null;

  // ------------------------------------------------------------- pembersihan

  /// Merapikan alamat yang diketik pengguna.
  ///
  /// Menghapus spasi, garis miring di akhir, dan akhiran `/api` atau
  /// `/api/v1` — keduanya ditambahkan sendiri oleh [apiUrl]. Tanpa ini,
  /// alamat yang disalin dari Swagger menghasilkan "/api/api/v1" dan semua
  /// permintaan berbalas 404. Subfolder (mis. `http://10.0.0.5/otogo`) tetap
  /// dipertahankan.
  static String normalkan(String? url) {
    var s = (url ?? '').trim();

    if (s.isEmpty) return '';

    s = _buangGarisAkhir(s);

    final kecil = s.toLowerCase();

    if (kecil.endsWith('/api/v1')) {
      s = s.substring(0, s.length - 7);
    } else if (kecil.endsWith('/api')) {
      s = s.substring(0, s.length - 4);
    }

    return _buangGarisAkhir(s);
  }

  static String _buangGarisAkhir(String s) {
    var hasil = s;

    while (hasil.endsWith('/')) {
      hasil = hasil.substring(0, hasil.length - 1);
    }

    return hasil;
  }

  /// Alasan alamat tidak bisa dipakai, atau null bila sudah benar.
  static String? galatAlamat(String? url) {
    final s = normalkan(url);

    if (s.isEmpty) return 'Alamat tidak boleh kosong.';

    final uri = Uri.tryParse(s);

    if (uri == null || !uri.hasScheme || uri.host.isEmpty) {
      return 'Sertakan http:// atau https://, mis. http://192.168.1.10:$portLokal';
    }

    if (uri.scheme != 'http' && uri.scheme != 'https') {
      return 'Hanya http dan https yang didukung.';
    }

    return null;
  }

  static const Duration connectTimeout = Duration(seconds: 15);
  static const Duration receiveTimeout = Duration(seconds: 30);

  /// Jawaban asisten AI bisa butuh beberapa putaran alat (pencarian web).
  static const Duration assistantTimeout = Duration(seconds: 90);
}
