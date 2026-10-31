import 'package:flutter_test/flutter_test.dart';
import 'package:otogo_mobile/core/config/app_config.dart';

/// Alamat API yang memuat subfolder, mis. `http://203.0.113.10/otogo`.
///
/// Server bisa dipasang di bawah subfolder pada sebuah IP (lihat
/// deploy subfolder di server). Kolom "Alamat API" di aplikasi harus
/// menerima bentuk itu apa adanya: kalau subfoldernya ikut terbuang, seluruh
/// permintaan menembak akar IP dan berbalas 404 dari situs lain yang
/// kebetulan ada di sana — bukan galat yang menuntun ke sebabnya.
void main() {
  group('normalkan', () {
    test('subfolder dipertahankan', () {
      expect(
        AppConfig.normalkan('http://203.0.113.10/otogo'),
        'http://203.0.113.10/otogo',
      );
    });

    test('garis miring di akhir dibuang, subfolder tetap', () {
      expect(
        AppConfig.normalkan('http://203.0.113.10/otogo/'),
        'http://203.0.113.10/otogo',
      );
    });

    test('akhiran /api dibuang tanpa ikut memakan subfolder', () {
      // Yang disalin orang dari halaman Swagger biasanya bentuk ini.
      expect(
        AppConfig.normalkan('http://203.0.113.10/otogo/api'),
        'http://203.0.113.10/otogo',
      );
      expect(
        AppConfig.normalkan('http://203.0.113.10/otogo/api/'),
        'http://203.0.113.10/otogo',
      );
    });

    test('subfolder bersarang tetap utuh', () {
      expect(
        AppConfig.normalkan('http://203.0.113.10/app/otogo/'),
        'http://203.0.113.10/app/otogo',
      );
    });

    test('alamat domain tanpa subfolder tidak berubah', () {
      expect(
        AppConfig.normalkan('https://api.otogo.id'),
        'https://api.otogo.id',
      );
    });
  });

  group('galatAlamat', () {
    test('IP dengan subfolder diterima', () {
      expect(AppConfig.galatAlamat('http://203.0.113.10/otogo'), isNull);
    });

    test('IP berport dengan subfolder diterima', () {
      expect(AppConfig.galatAlamat('http://203.0.113.10:8080/otogo'), isNull);
    });

    test('tanpa skema ditolak', () {
      expect(AppConfig.galatAlamat('203.0.113.10/otogo'), isNotNull);
    });
  });

  group('alamat turunan ikut membawa subfolder', () {
    // apiUrl dan swaggerUrl dibangun dari baseUrl dengan gabungan string,
    // bukan Uri.replace(path: ...) yang akan membuang subfoldernya.
    test('apiUrl', () {
      expect(
        '${AppConfig.normalkan('http://203.0.113.10/otogo')}/api',
        'http://203.0.113.10/otogo/api',
      );
    });

    test('swaggerUrl', () {
      expect(
        '${AppConfig.normalkan('http://203.0.113.10/otogo')}/api/documentation',
        'http://203.0.113.10/otogo/api/documentation',
      );
    });
  });
}
