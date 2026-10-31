import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/common.dart';
import '../../../core/widgets/feedback.dart';
import '../../../core/widgets/plat_nomor.dart';
import '../../../core/widgets/skeletons.dart';
import '../../shell/presentation/app_shell.dart';
import '../domain/vehicle.dart';
import 'vehicle_form_sheet.dart';
import 'vehicle_providers.dart';

class VehiclesScreen extends ConsumerWidget {
  const VehiclesScreen({super.key});

  Future<void> _hapus(BuildContext context, WidgetRef ref, Vehicle v) async {
    final ya = await konfirmasi(
      context,
      judul: 'Hapus kendaraan?',
      pesan: '${v.judul} (${v.plateNumber}) akan dihapus dari garasi Anda.',
      labelSetuju: 'Hapus',
      berbahaya: true,
    );

    if (!ya || !context.mounted) return;

    try {
      await ref.read(vehiclesProvider.notifier).hapus(v.id);

      if (context.mounted) AppSnack.sukses(context, 'Kendaraan dihapus.');
    } on ApiException catch (e) {
      if (context.mounted) AppSnack.galat(context, e.message);
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(vehiclesProvider);
    final jumlah = async.valueOrNull?.length;

    return Scaffold(
      body: SafeArea(
        bottom: false,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TabHeader(
              judul: 'Garasi',
              subjudul: jumlah == null
                  ? 'Kendaraan yang Anda rawat'
                  : jumlah == 0
                      ? 'Belum ada kendaraan'
                      : '$jumlah kendaraan terdaftar',
            ),
            Expanded(
              child: async.when(
                skipLoadingOnRefresh: true,
                loading: () => const ListSkeleton(tinggi: 96),
                error: (e, _) => ErrorState(
                  message: e is ApiException ? e.message : 'Terjadi kesalahan tak terduga.',
                  onRetry: () => ref.read(vehiclesProvider.notifier).muatUlang(),
                ),
                data: (list) => RefreshIndicator(
                  onRefresh: () => ref.read(vehiclesProvider.notifier).muatUlang(),
                  child: list.isEmpty
                      ? ListView(
                          children: [
                            const SizedBox(height: 48),
                            EmptyState(
                              icon: Icons.garage_outlined,
                              title: 'Garasi masih kosong',
                              message: 'Tambahkan motor atau mobil Anda supaya estimasi harga '
                                  'dan rekomendasi perawatan menyesuaikan kendaraan.',
                              action: FilledButton.icon(
                                onPressed: () => VehicleFormSheet.tampil(context),
                                style: FilledButton.styleFrom(minimumSize: const Size(0, 46)),
                                icon: const Icon(Icons.add_rounded),
                                label: const Text('Tambah kendaraan'),
                              ),
                            ),
                          ],
                        )
                      : ListView.separated(
                          padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, 96),
                          itemCount: list.length,
                          separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.md),
                          itemBuilder: (context, i) => _KartuKendaraan(
                            kendaraan: list[i],
                            onUbah: () => VehicleFormSheet.tampil(context, awal: list[i]),
                            onHapus: () => _hapus(context, ref, list[i]),
                          ).animate(delay: (50 * i).ms).fadeIn(duration: 300.ms).slideY(begin: 0.08, end: 0),
                        ),
                ),
              ),
            ),
          ],
        ),
      ),
      floatingActionButton: (jumlah ?? 0) == 0
          ? null
          : FloatingActionButton.extended(
              onPressed: () => VehicleFormSheet.tampil(context),
              icon: const Icon(Icons.add_rounded),
              label: const Text('Tambah'),
            ),
    );
  }
}

class _KartuKendaraan extends StatelessWidget {
  const _KartuKendaraan({required this.kendaraan, required this.onUbah, required this.onHapus});

  final Vehicle kendaraan;
  final VoidCallback onUbah;
  final VoidCallback onHapus;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final v = kendaraan;

    return SectionCard(
      onTap: onUbah,
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: scheme.primaryContainer,
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: Icon(
              v.isMotor ? Icons.two_wheeler_rounded : Icons.directions_car_filled_rounded,
              color: scheme.onPrimaryContainer,
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  v.nickname?.isNotEmpty == true ? v.nickname! : v.judul,
                  style: theme.textTheme.titleMedium,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 2),
                Text(
                  [if (v.nickname?.isNotEmpty == true) v.judul, if (v.keterangan.isNotEmpty) v.keterangan].join(' · '),
                  style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: AppSpacing.sm),
                PlatNomor(v.plateNumber, ukuran: 0.85),
              ],
            ),
          ),
          PopupMenuButton<String>(
            tooltip: 'Pilihan',
            onSelected: (aksi) => aksi == 'ubah' ? onUbah() : onHapus(),
            itemBuilder: (context) => const [
              PopupMenuItem(value: 'ubah', child: ListTile(leading: Icon(Icons.edit_outlined), title: Text('Ubah'))),
              PopupMenuItem(value: 'hapus', child: ListTile(leading: Icon(Icons.delete_outline_rounded), title: Text('Hapus'))),
            ],
          ),
        ],
      ),
    );
  }
}
