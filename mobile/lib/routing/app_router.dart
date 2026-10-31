import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/auth/presentation/auth_controller.dart';
import '../features/auth/presentation/change_password_screen.dart';
import '../features/auth/presentation/edit_profile_screen.dart';
import '../features/auth/presentation/login_screen.dart';
import '../features/auth/presentation/register_screen.dart';
import '../features/blog/presentation/blog_detail_screen.dart';
import '../features/shell/presentation/app_shell.dart';
import 'splash_screen.dart';

/// Rute aplikasi + penjaga autentikasi.
///
/// Selama status masih `memeriksa`, semua rute dialihkan ke splash sehingga
/// halaman masuk tidak sempat berkedip saat sesi sebenarnya masih ada.
final routerProvider = Provider<GoRouter>((ref) {
  final status = ValueNotifier<AuthStatus>(AuthStatus.memeriksa);

  ref.listen(
    authControllerProvider.select((s) => s.status),
    (_, next) => status.value = next,
    fireImmediately: true,
  );

  ref.onDispose(status.dispose);

  const publik = {'/login', '/daftar'};

  return GoRouter(
    initialLocation: '/splash',
    refreshListenable: status,
    redirect: (context, state) {
      final lokasi = state.matchedLocation;

      switch (status.value) {
        case AuthStatus.memeriksa:
          return lokasi == '/splash' ? null : '/splash';

        case AuthStatus.keluar:
          return publik.contains(lokasi) ? null : '/login';

        case AuthStatus.masuk:
          return publik.contains(lokasi) || lokasi == '/splash' ? '/' : null;
      }
    },
    routes: [
      GoRoute(path: '/splash', builder: (context, state) => const SplashScreen()),
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(path: '/daftar', builder: (context, state) => const RegisterScreen()),
      GoRoute(
        path: '/',
        builder: (context, state) => const AppShell(),
        routes: [
          GoRoute(
            path: 'blog/:slug',
            builder: (context, state) => BlogDetailScreen(slug: state.pathParameters['slug'] ?? ''),
          ),
          GoRoute(path: 'profil/ubah', builder: (context, state) => const EditProfileScreen()),
          GoRoute(path: 'profil/password', builder: (context, state) => const ChangePasswordScreen()),
        ],
      ),
    ],
    errorBuilder: (context, state) => Scaffold(
      appBar: AppBar(title: const Text('Halaman tidak ditemukan')),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.wrong_location_outlined, size: 56),
              const SizedBox(height: 16),
              Text(
                'Rute "${state.uri}" tidak dikenali.',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 20),
              FilledButton(onPressed: () => context.go('/'), child: const Text('Kembali ke Beranda')),
            ],
          ),
        ),
      ),
    ),
  );
});
