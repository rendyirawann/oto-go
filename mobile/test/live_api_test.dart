// Uji kontrak terhadap API OtoGo yang SEDANG BERJALAN.
//
// Dilewati secara bawaan (tidak butuh jaringan). Jalankan dengan:
//   flutter test test/live_api_test.dart --dart-define=OTOGO_LIVE_API=http://127.0.0.1:8822
//
// Membuat satu akun pelanggan uji (uat-mobile-<waktu>@otogo.test) dan satu
// kendaraan yang langsung dihapus lagi.
import 'package:flutter_test/flutter_test.dart';
import 'package:otogo_mobile/core/config/app_config.dart';
import 'package:otogo_mobile/core/network/api_client.dart';
import 'package:otogo_mobile/core/network/api_exception.dart';
import 'package:otogo_mobile/features/assistant/data/assistant_repository.dart';
import 'package:otogo_mobile/features/assistant/domain/chat.dart';
import 'package:otogo_mobile/features/auth/data/auth_repository.dart';
import 'package:otogo_mobile/features/blog/data/blog_repository.dart';
import 'package:otogo_mobile/features/catalog/data/catalog_repository.dart';
import 'package:otogo_mobile/features/vehicles/data/vehicle_repository.dart';
import 'package:otogo_mobile/features/vehicles/domain/vehicle.dart';

const _live = String.fromEnvironment('OTOGO_LIVE_API');

void main() {
  final skip = _live.isEmpty ? 'set --dart-define=OTOGO_LIVE_API=<url> untuk menjalankan' : null;

  late ApiClient api;

  setUpAll(() {
    AppConfig.terapkan(_live);
    api = ApiClient();
  });

  test('alur pelanggan end-to-end', () async {
    final health = await api.cekAlamat(AppConfig.baseUrl);
    expect(health.berhasil, isTrue, reason: health.pesan);

    // ---------------------------------------------------------- auth
    final auth = AuthRepository(api);
    final stamp = DateTime.now().millisecondsSinceEpoch;
    final email = 'uat-mobile-$stamp@otogo.test';

    final salah = await auth
        .login(email: email, password: 'bukan12345', deviceName: 'uji')
        .then<ApiException?>((_) => null, onError: (Object e) => e as ApiException);
    expect(salah?.statusCode, 422);
    expect(salah?.message, isNotEmpty);

    final sesi = await auth.daftar(
      DaftarInput(name: 'Uji Mobile', email: email, password: 'rahasia123', konfirmasi: 'rahasia123'),
      deviceName: 'flutter test',
    );
    expect(sesi.token, isNotEmpty);
    api.setToken(sesi.token);

    final saya = await auth.profil();
    expect(saya.email, email);
    expect(saya.isCustomer, isTrue);

    final diubah = await auth.updateProfil(name: 'Uji Mobile Dua', phone: '081234567890');
    expect(diubah.name, 'Uji Mobile Dua');

    // ---------------------------------------------------------- master
    final katalog = CatalogRepository(api);
    final kategori = await katalog.categories(type: 'mobil');
    final mpv = kategori.firstWhere((k) => k.code == 'car_mpv');
    final merek = await katalog.brands(type: 'mobil');
    expect(merek, isNotEmpty);
    final model = await katalog.models(brandId: merek.first.id);
    final area = await katalog.serviceAreas();
    expect(area.length, 21);

    final estimasi = await katalog.priceEstimates(vehicleCategory: 'car_mpv', module: 'carwash');
    expect(estimasi.items, isNotEmpty);
    expect(estimasi.disclaimer, isNotNull);
    expect(estimasi.items.every((e) => e.price > 0), isTrue);

    // ---------------------------------------------------------- kendaraan
    final repo = VehicleRepository(api);
    final baru = await repo.create(VehicleInput(
      vehicleCategoryId: mpv.id,
      vehicleModelId: model.isEmpty ? null : model.first.id,
      plateNumber: 'BK 1234 UJI',
      year: 2020,
      color: 'Putih',
    ));
    expect(baru.category?.code, 'car_mpv');

    final ubah = await repo.update(
      baru.id,
      VehicleInput(vehicleCategoryId: mpv.id, plateNumber: 'BK 1234 UJI', nickname: 'Mobil uji'),
    );
    expect(ubah.nickname, 'Mobil uji');
    expect((await repo.list()).map((v) => v.id), contains(baru.id));

    final plat = await repo
        .create(VehicleInput(vehicleCategoryId: mpv.id, plateNumber: '!!'))
        .then<ApiException?>((_) => null, onError: (Object e) => e as ApiException);
    expect(plat?.isValidation, isTrue);

    await repo.delete(baru.id);
    expect((await repo.list()).map((v) => v.id), isNot(contains(baru.id)));

    // ---------------------------------------------------------- blog
    final blog = BlogRepository(api);
    final daftar = await blog.posts(perPage: 2);
    expect(daftar.items, isNotEmpty);
    final detail = await blog.detail(daftar.items.first.slug);
    expect(detail.contentHtml, isNotEmpty);
    expect(await blog.categories(), isNotEmpty);

    // ---------------------------------------------------------- asisten
    final asisten = AssistantRepository(api);
    final balasan = await asisten.kirim(const [
      PesanChat(peran: PeranPesan.user, isi: 'Satu kalimat saja: kapan oli motor matic diganti?'),
    ]);
    // ok=false (AI sedang tidak tersedia) tetap kontrak yang sah, asal ada pesannya.
    expect(balasan.ok ? balasan.reply : balasan.pesan, isNotEmpty);
    // ignore: avoid_print
    print('asisten ok=${balasan.ok} model=${balasan.model}');

    // ---------------------------------------------------------- keluar
    await auth.logout();
    final ditolak = await auth.profil().then<ApiException?>((_) => null, onError: (Object e) => e as ApiException);
    expect(ditolak?.isUnauthenticated, isTrue);
  }, skip: skip, timeout: const Timeout(Duration(minutes: 2)));
}
