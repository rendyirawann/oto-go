import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:otogo_mobile/core/config/app_config.dart';
import 'package:otogo_mobile/core/network/api_client.dart';
import 'package:otogo_mobile/core/utils/formatters.dart';

void main() {
  setUpAll(() => initializeDateFormatting('id_ID'));

  group('Fmt.rupiah', () {
    test('rupiah bulat dengan pemisah titik', () {
      expect(Fmt.rupiah(45000), 'Rp45.000');
      expect(Fmt.rupiah(1250000), 'Rp1.250.000');
      expect(Fmt.rupiah(null), 'Rp0');
    });
  });

  group('Fmt.plat', () {
    test('merapikan nomor polisi Medan', () {
      expect(Fmt.plat('bk1234abc'), 'BK 1234 ABC');
      expect(Fmt.plat('BK  1234   AB'), 'BK 1234 AB');
      expect(Fmt.plat('b 1 x'), 'B 1 X');
      expect(Fmt.plat('BK1234'), 'BK 1234');
    });

    test('teks yang bukan pelat dikembalikan huruf besar apa adanya', () {
      expect(Fmt.plat('cd 12 34 56'), 'CD 12 34 56');
    });
  });

  group('Fmt.durasi', () {
    test('menit dan jam', () {
      expect(Fmt.durasi(30), '30 mnt');
      expect(Fmt.durasi(90), '1 jam 30 mnt');
      expect(Fmt.durasi(240), '4 jam');
      expect(Fmt.durasi(null), '-');
    });
  });

  group('AppConfig.normalkan', () {
    test('akhiran /api/v1 dan /api dibuang', () {
      expect(AppConfig.normalkan('http://127.0.0.1:8822/api/v1/'), 'http://127.0.0.1:8822');
      expect(AppConfig.normalkan('http://127.0.0.1:8822/api'), 'http://127.0.0.1:8822');
      expect(AppConfig.normalkan(' http://10.0.2.2:8822/ '), 'http://10.0.2.2:8822');
    });

    test('alamat bawaan debug memakai port 8822', () {
      expect(AppConfig.baseUrlBawaan, endsWith(':${AppConfig.portLokal}'));
    });
  });

  group('ApiClient.fieldErrors', () {
    test('pesan pertama tiap kolom diambil', () {
      expect(
        ApiClient.fieldErrors({
          'email': ['Email sudah dipakai.', 'lain'],
          'name': 'Nama wajib diisi.',
        }),
        {'email': 'Email sudah dipakai.', 'name': 'Nama wajib diisi.'},
      );
      expect(ApiClient.fieldErrors(null), isEmpty);
    });
  });
}
