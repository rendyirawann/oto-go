import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/plat_nomor.dart';
import '../../../../core/widgets/skeletons.dart';
import '../../../../routing/splash_screen.dart';
import '../../../shell/presentation/app_shell.dart';
import '../../../vehicles/presentation/vehicle_form_sheet.dart';
import '../../../vehicles/presentation/vehicle_providers.dart';

/// Kartu kendaraan utama di beranda: pelat nomor besar di atas latar tinta.
/// Bila belum ada kendaraan, kartu yang sama mengajak menambahkannya.
class VehicleHeroCard extends ConsumerWidget {
  const VehicleHeroCard({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(vehiclesProvider);
    final utama = ref.watch(primaryVehicleProvider);
    final jumlah = async.valueOrNull?.length ?? 0;
    final theme = Theme.of(context);

    if (async.isLoading && !async.hasValue) {
      return const KotakTiruan(tinggi: 168);
    }

    final terang = AppColors.background;

    return Container(
      decoration: BoxDecoration(
        gradient: AppColors.gradientTinta,
        borderRadius: BorderRadius.circular(AppRadius.xl),
      ),
      clipBehavior: Clip.antiAlias,
      child: Stack(
        children: [
          const Positioned(left: 0, right: 0, bottom: 18, child: MarkaJalan(opacity: 0.18)),
          Padding(
            padding: const EdgeInsets.fromLTRB(AppSpacing.xl, AppSpacing.xl, AppSpacing.xl, 40),
            child: utama == null
                ? Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Garasi masih kosong', style: theme.textTheme.titleLarge?.copyWith(color: terang)),
                      const SizedBox(height: AppSpacing.xs),
                      Text(
                        async.hasError
                            ? 'Daftar kendaraan belum bisa dimuat. Tarik ke bawah untuk mencoba lagi.'
                            : 'Tambahkan kendaraan supaya estimasi harga langsung menyesuaikan kategorinya.',
                        style: theme.textTheme.bodyMedium?.copyWith(color: terang.withValues(alpha: 0.7)),
                      ),
                      const SizedBox(height: AppSpacing.lg),
                      FilledButton.icon(
                        onPressed: () => VehicleFormSheet.tampil(context),
                        style: FilledButton.styleFrom(minimumSize: const Size(0, 44)),
                        icon: const Icon(Icons.add_rounded, size: 18),
                        label: const Text('Tambah kendaraan'),
                      ),
                    ],
                  )
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(
                            'KENDARAAN UTAMA',
                            style: theme.textTheme.labelSmall?.copyWith(
                              color: AppColors.yellow,
                              letterSpacing: 1.4,
                            ),
                          ),
                          const Spacer(),
                          if (jumlah > 1)
                            TextButton(
                              onPressed: () => ref.read(tabAktifProvider.notifier).state = TabIndeks.kendaraan,
                              style: TextButton.styleFrom(
                                foregroundColor: terang,
                                visualDensity: VisualDensity.compact,
                              ),
                              child: Text('+${jumlah - 1} lainnya'),
                            ),
                        ],
                      ),
                      const SizedBox(height: AppSpacing.md),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Container(
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(9),
                              border: Border.all(color: terang.withValues(alpha: 0.25)),
                            ),
                            child: PlatNomor(utama.plateNumber, ukuran: 1.25),
                          ),
                          const Spacer(),
                          Icon(
                            utama.isMotor ? Icons.two_wheeler_rounded : Icons.directions_car_filled_rounded,
                            color: AppColors.orange,
                            size: 30,
                          ),
                        ],
                      ),
                      const SizedBox(height: AppSpacing.lg),
                      Text(
                        utama.nickname?.isNotEmpty == true ? utama.nickname! : utama.judul,
                        style: theme.textTheme.titleLarge?.copyWith(color: terang),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      if (utama.keterangan.isNotEmpty || utama.nickname?.isNotEmpty == true)
                        Text(
                          [
                            if (utama.nickname?.isNotEmpty == true) utama.judul,
                            if (utama.keterangan.isNotEmpty) utama.keterangan,
                          ].join(' · '),
                          style: theme.textTheme.bodySmall?.copyWith(color: terang.withValues(alpha: 0.65)),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                    ],
                  ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.06, end: 0, curve: Curves.easeOutCubic);
  }
}
