import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/date_symbol_data_local.dart';

import 'core/config/app_config.dart';
import 'core/providers.dart';
import 'core/storage/secure_store.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/presentation/auth_controller.dart';
import 'routing/app_router.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Nama bulan & hari berbahasa Indonesia untuk seluruh pemformatan tanggal.
  await initializeDateFormatting('id_ID');

  // Alamat API dan tema dimuat SEBELUM runApp: ApiClient membaca alamat
  // secara sinkron saat dibangun, dan tema harus benar sejak frame pertama.
  const store = SecureStore();
  AppConfig.terapkan(await store.readBaseUrl());
  final tema = ThemeModeController.parse(await store.readThemeMode());

  runApp(
    ProviderScope(
      overrides: [
        themeModeProvider.overrideWith((ref) => ThemeModeController(ref.watch(secureStoreProvider), tema)),
      ],
      child: const OtoGoApp(),
    ),
  );
}

class OtoGoApp extends ConsumerStatefulWidget {
  const OtoGoApp({super.key});

  @override
  ConsumerState<OtoGoApp> createState() => _OtoGoAppState();
}

class _OtoGoAppState extends ConsumerState<OtoGoApp> {
  @override
  void initState() {
    super.initState();

    // Pemulihan sesi dimulai setelah frame pertama agar splash sudah tampil.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(authControllerProvider.notifier).bootstrap();
    });
  }

  @override
  Widget build(BuildContext context) {
    final router = ref.watch(routerProvider);

    return MaterialApp.router(
      title: AppConfig.appName,
      debugShowCheckedModeBanner: false,
      routerConfig: router,
      theme: AppTheme.light,
      darkTheme: AppTheme.dark,
      themeMode: ref.watch(themeModeProvider),
      scrollBehavior: const _ScrollBisaDiseret(),
      locale: const Locale('id', 'ID'),
      supportedLocales: const [Locale('id', 'ID'), Locale('en', 'US')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],

      // Skala teks dibatasi supaya harga & pelat tidak pecah pada perangkat
      // dengan pengaturan font sangat besar.
      builder: (context, child) => MediaQuery.withClampedTextScaling(
        minScaleFactor: 0.85,
        maxScaleFactor: 1.3,
        child: child ?? const SizedBox.shrink(),
      ),
    );
  }
}

/// Mengizinkan scroll dengan menyeret mouse -- aplikasi ini sering
/// dipratinjau di browser saat pengembangan.
class _ScrollBisaDiseret extends MaterialScrollBehavior {
  const _ScrollBisaDiseret();

  @override
  Set<PointerDeviceKind> get dragDevices => {
        PointerDeviceKind.touch,
        PointerDeviceKind.stylus,
        PointerDeviceKind.invertedStylus,
        PointerDeviceKind.mouse,
        PointerDeviceKind.trackpad,
      };
}
