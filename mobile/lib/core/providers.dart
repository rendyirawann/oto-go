import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'network/api_client.dart';
import 'storage/secure_store.dart';

/// Satu instans ApiClient untuk seluruh aplikasi -- token yang dipasang saat
/// login otomatis terpakai di semua repository.
final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());

final secureStoreProvider = Provider<SecureStore>((ref) => const SecureStore());

/// Mode tema pilihan pengguna (Profil > Tampilan).
class ThemeModeController extends StateNotifier<ThemeMode> {
  ThemeModeController(this._store, ThemeMode awal) : super(awal);

  final SecureStore _store;

  static ThemeMode parse(String? raw) => switch (raw) {
        'light' => ThemeMode.light,
        'dark' => ThemeMode.dark,
        _ => ThemeMode.system,
      };

  Future<void> pilih(ThemeMode mode) async {
    state = mode;

    try {
      await _store.saveThemeMode(mode.name);
    } catch (_) {
      // Gagal menyimpan bukan alasan menolak perubahan di layar.
    }
  }
}

/// Nilai awal dimuat di `main()` lewat override, supaya tema sudah benar
/// sejak frame pertama (tanpa kedipan terang -> gelap).
final themeModeProvider = StateNotifierProvider<ThemeModeController, ThemeMode>(
  (ref) => ThemeModeController(ref.watch(secureStoreProvider), ThemeMode.system),
);
