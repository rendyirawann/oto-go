import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:otogo_mobile/core/theme/app_theme.dart';
import 'package:otogo_mobile/core/widgets/brand_logo.dart';
import 'package:otogo_mobile/core/widgets/plat_nomor.dart';
import 'package:otogo_mobile/features/auth/presentation/login_screen.dart';
import 'package:otogo_mobile/features/auth/presentation/register_screen.dart';
import 'package:otogo_mobile/features/blog/domain/post.dart';
import 'package:otogo_mobile/features/blog/presentation/blog_detail_screen.dart';
import 'package:otogo_mobile/features/blog/presentation/blog_providers.dart';
import 'package:otogo_mobile/features/catalog/data/catalog_repository.dart';
import 'package:otogo_mobile/features/catalog/domain/catalog.dart';
import 'package:otogo_mobile/features/vehicles/domain/vehicle.dart';
import 'package:otogo_mobile/features/vehicles/presentation/vehicle_form_sheet.dart';
import 'package:otogo_mobile/features/vehicles/presentation/vehicle_providers.dart';
import 'package:otogo_mobile/features/vehicles/presentation/vehicles_screen.dart';

/// Daftar kendaraan palsu: tidak menyentuh jaringan.
class _KendaraanPalsu extends VehiclesController {
  _KendaraanPalsu(this.isi);

  final List<Vehicle> isi;

  @override
  Future<List<Vehicle>> build() async => isi;
}

Widget _app(Widget child, {List<Override> overrides = const []}) => ProviderScope(
      overrides: overrides,
      child: MaterialApp(theme: AppTheme.light, home: child),
    );

/// Animasi flutter_animate memakai jeda; dipompa cukup lama agar semua
/// timer selesai sebelum test berakhir.
Future<void> _tunggu(WidgetTester tester) async {
  for (var i = 0; i < 12; i++) {
    await tester.pump(const Duration(milliseconds: 100));
  }
}

void main() {
  setUpAll(() async {
    AppTheme.pakaiGoogleFonts = false;
    await initializeDateFormatting('id_ID');
  });

  testWidgets('Layar masuk tampil dan menolak form kosong', (tester) async {
    tester.view.physicalSize = const Size(1080, 2400);
    tester.view.devicePixelRatio = 2.6;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(_app(const LoginScreen()));
    await _tunggu(tester);

    expect(find.text('Masuk'), findsWidgets);
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
    expect(find.text('Daftar gratis'), findsOneWidget);
    expect(find.byType(BrandWordmark), findsOneWidget);

    await tester.tap(find.widgetWithText(InkWell, 'Masuk').last);
    await _tunggu(tester);

    expect(find.text('Email dan password wajib diisi.'), findsOneWidget);
  });

  testWidgets('Garasi kosong menawarkan tambah kendaraan', (tester) async {
    await tester.pumpWidget(_app(
      const VehiclesScreen(),
      overrides: [vehiclesProvider.overrideWith(() => _KendaraanPalsu(const []))],
    ));
    await _tunggu(tester);

    expect(find.text('Garasi masih kosong'), findsOneWidget);
    expect(find.text('Tambah kendaraan'), findsOneWidget);
  });

  testWidgets('Garasi menampilkan pelat yang dirapikan', (tester) async {
    const kendaraan = Vehicle(
      id: '1',
      plateNumber: 'bk1234abc',
      year: 2020,
      category: VehicleCategory(id: '7', code: 'car_mpv', name: 'MPV (Avanza, Xenia, Ertiga, dll)', vehicleType: 'mobil'),
      modelName: 'Avanza',
      brandName: 'Toyota',
    );

    await tester.pumpWidget(_app(
      const VehiclesScreen(),
      overrides: [vehiclesProvider.overrideWith(() => _KendaraanPalsu(const [kendaraan]))],
    ));
    await _tunggu(tester);

    expect(find.text('1 kendaraan terdaftar'), findsOneWidget);
    expect(find.text('Toyota Avanza'), findsOneWidget);
    expect(find.text('BK 1234 ABC'), findsOneWidget);
    expect(find.byType(PlatNomor), findsOneWidget);
  });

  testWidgets('Layar daftar tidak meluap di layar 320px', (tester) async {
    tester.view.physicalSize = const Size(320, 720);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(_app(const RegisterScreen()));
    await _tunggu(tester);

    expect(tester.takeException(), isNull);
    expect(find.text('Buat akun'), findsOneWidget);
  });

  testWidgets('Form kendaraan tidak meluap di layar 320px', (tester) async {
    tester.view.physicalSize = const Size(320, 720);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    await tester.pumpWidget(_app(
      Builder(
        builder: (context) => Scaffold(
          body: Center(
            child: TextButton(
              onPressed: () => VehicleFormSheet.tampil(context),
              child: const Text('buka'),
            ),
          ),
        ),
      ),
      overrides: [
        vehiclesProvider.overrideWith(() => _KendaraanPalsu(const [])),
        vehicleCategoriesProvider.overrideWith((ref, type) async => const [
              VehicleCategory(id: '1', code: 'car_city', name: 'City Car / Hatchback', vehicleType: 'mobil'),
              VehicleCategory(id: '2', code: 'car_mpv', name: 'MPV (Avanza, Xenia, Ertiga, dll)', vehicleType: 'mobil'),
            ]),
        vehicleBrandsProvider.overrideWith((ref, type) async => const [VehicleBrand(id: '9', name: 'Toyota')]),
      ],
    ));
    await tester.tap(find.text('buka'));
    await _tunggu(tester);

    expect(tester.takeException(), isNull);
    expect(find.text('Tambah kendaraan'), findsOneWidget);
    expect(find.text('BK 0000 XX'), findsOneWidget, reason: 'pratinjau pelat kosong');
  });

  testWidgets('Artikel asli (tabel harga) tidak meluap di layar 320px', (tester) async {
    tester.view.physicalSize = const Size(320, 720);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.reset);

    final json = jsonDecode(File('test/fixtures/post_detail.json').readAsStringSync()) as Map<String, dynamic>;
    final post = PostDetail.fromJson(json['data'] as Map<String, dynamic>);

    await tester.pumpWidget(_app(
      BlogDetailScreen(slug: post.summary.slug),
      overrides: [postDetailProvider.overrideWith((ref, slug) async => post)],
    ));
    await _tunggu(tester);

    expect(tester.takeException(), isNull);
    expect(find.text(post.summary.title), findsOneWidget);

    // Gulir sampai bawah: tabel dan "Baca juga" ikut dirender.
    await tester.scrollUntilVisible(
      find.text('BACA JUGA'),
      400,
      scrollable: find.byType(Scrollable).first,
      maxScrolls: 60,
    );
    await _tunggu(tester);

    expect(tester.takeException(), isNull);
    expect(find.text('BACA JUGA'), findsOneWidget);
  });

  testWidgets('Logo dan pelat dirender tanpa galat di tema gelap', (tester) async {
    await tester.pumpWidget(MaterialApp(
      theme: AppTheme.dark,
      home: const Scaffold(
        body: Column(
          children: [
            BrandLogo(size: 48),
            BrandLogo.tanda(size: 32),
            BrandWordmark(height: 24),
            PlatNomor('BK 1 OTO', ukuran: 1.2),
          ],
        ),
      ),
    ));

    expect(tester.takeException(), isNull);
    expect(find.text('BK 1 OTO'), findsOneWidget);
  });
}

