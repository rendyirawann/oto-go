import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/common.dart';
import '../../../catalog/domain/catalog.dart';

/// Tiga modul usaha OtoGo + tiga mode layanan. Booking belum dibuka di
/// aplikasi, jadi kartunya jujur menandai "Segera hadir" dan menjelaskan apa
/// yang bisa dilakukan sekarang.
class ModuleCards extends StatelessWidget {
  const ModuleCards({super.key});

  static const _modul = [
    (
      code: Modul.workshop,
      ikon: Icons.build_outlined,
      judul: 'Bengkel',
      isi: 'Servis berkala, rem, kaki-kaki, AC, kelistrikan.',
    ),
    (
      code: Modul.carwash,
      ikon: Icons.local_car_wash_outlined,
      judul: 'Doorsmeer',
      isi: 'Cuci salju, vakum interior, detailing.',
    ),
    (
      code: Modul.sparepart,
      ikon: Icons.inventory_2_outlined,
      judul: 'Sparepart',
      isi: 'Oli, aki, kampas rem - ambil atau diantar.',
    ),
  ];

  static const _mode = [
    (ikon: Icons.storefront_outlined, label: 'Datang ke outlet'),
    (ikon: Icons.swap_horiz_rounded, label: 'Antar jemput'),
    (ikon: Icons.home_outlined, label: 'Di rumah Anda'),
  ];

  void _info(BuildContext context, String judul) {
    showModalBottomSheet<void>(
      context: context,
      builder: (context) {
        final theme = Theme.of(context);

        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(AppSpacing.xl, 0, AppSpacing.xl, AppSpacing.xl),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Booking $judul segera hadir', style: theme.textTheme.titleLarge),
                const SizedBox(height: AppSpacing.sm),
                Text(
                  'Kami sedang mengajak bengkel, doorsmeer, dan toko sparepart di Medan '
                  'untuk bergabung. Sambil menunggu, Anda sudah bisa menyimpan data '
                  'kendaraan, mengecek kisaran harga pasar, dan bertanya ke Asisten OtoGo.',
                  style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant),
                ),
                const SizedBox(height: AppSpacing.xl),
                FilledButton(onPressed: () => Navigator.of(context).pop(), child: const Text('Mengerti')),
              ],
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
          child: GroupHeader(title: 'Layanan', icon: Icons.handyman_outlined),
        ),
        SizedBox(
          height: 156,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
            itemCount: _modul.length,
            separatorBuilder: (_, _) => const SizedBox(width: AppSpacing.md),
            itemBuilder: (context, i) {
              final m = _modul[i];
              final warna = AppColors.modul(m.code);

              return SizedBox(
                width: 200,
                child: SectionCard(
                  onTap: () => _info(context, m.judul),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            width: 36,
                            height: 36,
                            decoration: BoxDecoration(
                              color: warna.withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(AppRadius.sm),
                            ),
                            child: Icon(m.ikon, size: 20, color: warna),
                          ),
                          const Spacer(),
                          Pill(text: 'Segera hadir', color: scheme.onSurfaceVariant),
                        ],
                      ),
                      const SizedBox(height: AppSpacing.md),
                      Text(m.judul, style: theme.textTheme.titleMedium),
                      const SizedBox(height: 2),
                      Text(
                        m.isi,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                      ),
                    ],
                  ),
                ),
              ).animate(delay: (80 * i).ms).fadeIn(duration: 350.ms).slideX(begin: 0.1, end: 0);
            },
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
          child: Row(
            children: [
              for (final mode in _mode)
                Expanded(
                  child: Row(
                    children: [
                      Icon(mode.ikon, size: 16, color: scheme.onSurfaceVariant),
                      const SizedBox(width: 6),
                      Flexible(
                        child: Text(
                          mode.label,
                          style: theme.textTheme.labelMedium?.copyWith(color: scheme.onSurfaceVariant),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }
}
