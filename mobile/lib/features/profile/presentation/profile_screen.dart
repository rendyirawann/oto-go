import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/config/app_config.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/utils/links.dart';
import '../../../core/widgets/brand_logo.dart';
import '../../../core/widgets/common.dart';
import '../../../core/widgets/feedback.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../settings/presentation/alamat_api_sheet.dart';
import '../../shell/presentation/app_shell.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  Future<void> _keluar(BuildContext context, WidgetRef ref) async {
    final ya = await konfirmasi(
      context,
      judul: 'Keluar dari OtoGo?',
      pesan: 'Data kendaraan Anda tetap tersimpan di akun dan muncul lagi saat masuk kembali.',
      labelSetuju: 'Keluar',
      ikon: Icons.logout_rounded,
    );

    if (!ya) return;

    ref.read(tabAktifProvider.notifier).state = TabIndeks.beranda;
    await ref.read(authControllerProvider.notifier).logout();
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final user = auth.user;
    final tema = ref.watch(themeModeProvider);
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return SafeArea(
      bottom: false,
      child: RefreshIndicator(
        onRefresh: () => ref.read(authControllerProvider.notifier).muatUlangProfil(),
        child: ListView(
          padding: const EdgeInsets.only(bottom: AppSpacing.xxxl),
          children: [
            const TabHeader(judul: 'Profil'),

            // ------------------------------------------------ identitas
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: SectionCard(
                onTap: () => context.push('/profil/ubah'),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 28,
                      backgroundColor: scheme.inverseSurface,
                      child: Text(
                        user?.initials ?? '?',
                        style: theme.textTheme.titleMedium?.copyWith(
                          color: scheme.onInverseSurface,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                    const SizedBox(width: AppSpacing.lg),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(user?.name ?? '-', style: theme.textTheme.titleMedium),
                          Text(
                            user?.email ?? '-',
                            style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                          ),
                          if (user?.phone != null)
                            Text(
                              user!.phone!,
                              style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                            ),
                          if (user?.createdAt != null)
                            Padding(
                              padding: const EdgeInsets.only(top: 4),
                              child: Pill(text: 'Bergabung ${Fmt.bulanTahun(user!.createdAt)}', color: AppColors.success),
                            ),
                        ],
                      ),
                    ),
                    Icon(Icons.chevron_right_rounded, color: scheme.onSurfaceVariant),
                  ],
                ),
              ),
            ),

            // ------------------------------------------------ akun
            const SizedBox(height: AppSpacing.xl),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: GroupHeader(title: 'Akun'),
            ),
            _Grup(
              children: [
                _Baris(
                  ikon: Icons.person_outline_rounded,
                  judul: 'Ubah profil',
                  onTap: () => context.push('/profil/ubah'),
                ),
                _Baris(
                  ikon: Icons.lock_reset_rounded,
                  judul: 'Ganti password',
                  onTap: () => context.push('/profil/password'),
                ),
              ],
            ),

            // ------------------------------------------------ tampilan
            const SizedBox(height: AppSpacing.xl),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: GroupHeader(title: 'Tampilan'),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: SegmentedButton<ThemeMode>(
                showSelectedIcon: false,
                segments: const [
                  ButtonSegment(value: ThemeMode.system, label: Text('Ikuti HP'), icon: Icon(Icons.brightness_auto_outlined, size: 18)),
                  ButtonSegment(value: ThemeMode.light, label: Text('Terang'), icon: Icon(Icons.light_mode_outlined, size: 18)),
                  ButtonSegment(value: ThemeMode.dark, label: Text('Gelap'), icon: Icon(Icons.dark_mode_outlined, size: 18)),
                ],
                selected: {tema},
                onSelectionChanged: (s) => ref.read(themeModeProvider.notifier).pilih(s.first),
              ),
            ),

            // ------------------------------------------------ server
            const SizedBox(height: AppSpacing.xl),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: GroupHeader(title: 'Server'),
            ),
            _Grup(
              children: [
                _Baris(
                  ikon: Icons.dns_outlined,
                  judul: 'Alamat API',
                  keterangan: AppConfig.baseUrl,
                  onTap: () => AlamatApiSheet.tampil(context),
                ),
                _Baris(
                  ikon: Icons.description_outlined,
                  judul: 'Dokumentasi API (Swagger)',
                  keterangan: AppConfig.swaggerUrl,
                  ikonKanan: Icons.open_in_new_rounded,
                  onTap: () => bukaTautan(context, AppConfig.swaggerUrl),
                ),
              ],
            ),

            // ------------------------------------------------ keluar
            const SizedBox(height: AppSpacing.xl),
            _Grup(
              children: [
                _Baris(
                  ikon: Icons.logout_rounded,
                  judul: 'Keluar',
                  warna: AppColors.danger,
                  onTap: auth.sedangProses ? null : () => _keluar(context, ref),
                ),
              ],
            ),

            // ------------------------------------------------ tentang
            const SizedBox(height: AppSpacing.xxxl),
            Column(
              children: [
                BrandWordmark(height: 22, warna: scheme.onSurfaceVariant),
                const SizedBox(height: AppSpacing.sm),
                Text(
                  'Versi ${AppConfig.appVersion} · Kota ${AppConfig.kota}',
                  style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                ),
                Text(
                  '© ${DateTime.now().year} Core Technology Indonesia',
                  style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _Grup extends StatelessWidget {
  const _Grup({required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      child: Container(
        decoration: BoxDecoration(
          color: scheme.surface,
          borderRadius: AppRadius.card,
          border: Border.all(color: scheme.outline),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          children: [
            for (var i = 0; i < children.length; i++) ...[
              if (i > 0) Divider(height: 1, indent: 56, color: scheme.outline),
              children[i],
            ],
          ],
        ),
      ),
    );
  }
}

class _Baris extends StatelessWidget {
  const _Baris({
    required this.ikon,
    required this.judul,
    this.keterangan,
    this.onTap,
    this.warna,
    this.ikonKanan = Icons.chevron_right_rounded,
  });

  final IconData ikon;
  final String judul;
  final String? keterangan;
  final VoidCallback? onTap;
  final Color? warna;
  final IconData ikonKanan;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return ListTile(
      onTap: onTap,
      leading: Icon(ikon, color: warna ?? scheme.onSurface),
      title: Text(judul, style: theme.textTheme.bodyLarge?.copyWith(color: warna, fontWeight: FontWeight.w600)),
      subtitle: keterangan == null ? null : Text(keterangan!, maxLines: 1, overflow: TextOverflow.ellipsis),
      trailing: warna == null ? Icon(ikonKanan, size: 20, color: scheme.onSurfaceVariant) : null,
    );
  }
}
