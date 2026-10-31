import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_nav_bar/google_nav_bar.dart';

import '../../../core/theme/app_theme.dart';
import '../../assistant/presentation/assistant_screen.dart';
import '../../blog/presentation/blog_list_screen.dart';
import '../../home/presentation/home_screen.dart';
import '../../profile/presentation/profile_screen.dart';
import '../../vehicles/presentation/vehicles_screen.dart';

/// Tab aktif dibagikan lewat provider agar layar lain bisa memindah tab
/// (mis. tombol "Tambah kendaraan" di beranda membuka tab Kendaraan).
final tabAktifProvider = StateProvider<int>((ref) => 0);

class TabIndeks {
  const TabIndeks._();

  static const int beranda = 0;
  static const int kendaraan = 1;
  static const int blog = 2;
  static const int asisten = 3;
  static const int profil = 4;
}

/// Kerangka utama: lima tab dengan bottom nav beranimasi (google_nav_bar).
class AppShell extends ConsumerWidget {
  const AppShell({super.key});

  static const List<({IconData icon, String label})> _tabs = [
    (icon: Icons.space_dashboard_outlined, label: 'Beranda'),
    (icon: Icons.directions_car_outlined, label: 'Kendaraan'),
    (icon: Icons.menu_book_outlined, label: 'Blog'),
    (icon: Icons.auto_awesome_outlined, label: 'Asisten'),
    (icon: Icons.person_outline_rounded, label: 'Profil'),
  ];

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final index = ref.watch(tabAktifProvider);
    final scheme = Theme.of(context).colorScheme;

    return Scaffold(
      body: IndexedStack(
        index: index,
        children: const [
          HomeScreen(),
          VehiclesScreen(),
          BlogListScreen(),
          AssistantScreen(),
          ProfileScreen(),
        ],
      ),
      bottomNavigationBar: DecoratedBox(
        decoration: BoxDecoration(
          color: scheme.surface,
          border: Border(top: BorderSide(color: scheme.outline)),
        ),
        child: SafeArea(
          top: false,
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: AppSpacing.sm),
            child: GNav(
              selectedIndex: index,
              onTabChange: (i) => ref.read(tabAktifProvider.notifier).state = i,
              gap: 6,
              haptic: true,
              curve: Curves.easeOutCubic,
              duration: const Duration(milliseconds: 380),
              tabBorderRadius: AppRadius.pill,
              backgroundColor: Colors.transparent,
              color: scheme.onSurfaceVariant,
              activeColor: scheme.onInverseSurface,
              tabBackgroundColor: scheme.inverseSurface,
              iconSize: 21,
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md, vertical: 10),
              textStyle: Theme.of(context).textTheme.labelMedium?.copyWith(
                    color: scheme.onInverseSurface,
                    fontWeight: FontWeight.w700,
                  ),
              tabs: _tabs.map((t) => GButton(icon: t.icon, text: t.label)).toList(growable: false),
            ),
          ),
        ),
      ),
    );
  }
}

/// Judul halaman tab yang seragam: judul besar + subjudul + aksi opsional.
class TabHeader extends StatelessWidget {
  const TabHeader({super.key, required this.judul, this.subjudul, this.aksi});

  final String judul;
  final String? subjudul;
  final Widget? aksi;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.md),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(judul, style: theme.textTheme.headlineMedium),
                if (subjudul != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    subjudul!,
                    style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                  ),
                ],
              ],
            ),
          ),
          ?aksi,
        ],
      ),
    );
  }
}
