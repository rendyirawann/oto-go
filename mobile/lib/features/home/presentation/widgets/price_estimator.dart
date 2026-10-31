import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/common.dart';
import '../../../../core/widgets/skeletons.dart';
import '../../../catalog/data/catalog_repository.dart';
import '../../../catalog/domain/catalog.dart';
import '../../../vehicles/presentation/vehicle_providers.dart';

/// "Cek kisaran harga": pilih kategori kendaraan dan modul, tampilkan
/// estimasi dari template Tarif Pasar Medan.
///
/// Kategori bawaan mengikuti kendaraan utama pengguna; bila belum ada,
/// MPV (kategori mobil paling umum di Medan).
class PriceEstimator extends ConsumerStatefulWidget {
  const PriceEstimator({super.key});

  @override
  ConsumerState<PriceEstimator> createState() => _PriceEstimatorState();
}

class _PriceEstimatorState extends ConsumerState<PriceEstimator> {
  String? _kategori;
  String _modul = Modul.carwash;

  @override
  Widget build(BuildContext context) {
    final kategoriAsync = ref.watch(vehicleCategoriesProvider(null));
    final utama = ref.watch(primaryVehicleProvider);
    final kode = _kategori ?? utama?.category?.code ?? 'car_mpv';

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      child: SectionCard(
        title: 'Cek kisaran harga',
        subtitle: 'Tarif pasar Medan, sebelum diskon & sparepart',
        icon: Icons.sell_outlined,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            SegmentedButton<String>(
              showSelectedIcon: false,
              segments: const [
                ButtonSegment(value: Modul.carwash, label: Text('Doorsmeer'), icon: Icon(Icons.local_car_wash_outlined, size: 18)),
                ButtonSegment(value: Modul.workshop, label: Text('Bengkel'), icon: Icon(Icons.build_outlined, size: 18)),
              ],
              selected: {_modul},
              onSelectionChanged: (s) => setState(() => _modul = s.first),
            ),
            const SizedBox(height: AppSpacing.md),

            // ------------------------------------------------ kategori
            kategoriAsync.when(
              loading: () => const SizedBox(height: 40, child: BarisSkeleton(jumlah: 1)),
              error: (e, _) => _GagalKecil(
                pesan: 'Kategori kendaraan belum bisa dimuat.',
                onRetry: () => ref.invalidate(vehicleCategoriesProvider(null)),
              ),
              data: (kategori) => SizedBox(
                height: 40,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: kategori.length,
                  separatorBuilder: (_, _) => const SizedBox(width: AppSpacing.sm),
                  itemBuilder: (context, i) {
                    final k = kategori[i];

                    return ChoiceChip(
                      label: Text(k.namaPendek),
                      avatar: Icon(
                        k.isMotor ? Icons.two_wheeler_rounded : Icons.directions_car_outlined,
                        size: 16,
                      ),
                      selected: k.code == kode,
                      showCheckmark: false,
                      onSelected: (_) => setState(() => _kategori = k.code),
                    );
                  },
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.lg),

            // ------------------------------------------------ hasil
            _Hasil(kategori: kode, modul: _modul),
          ],
        ),
      ),
    );
  }
}

class _Hasil extends ConsumerWidget {
  const _Hasil({required this.kategori, required this.modul});

  final String kategori;
  final String modul;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final kunci = (kategori: kategori, modul: modul);
    final async = ref.watch(priceEstimatesProvider(kunci));
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return AnimatedSize(
      duration: const Duration(milliseconds: 260),
      curve: Curves.easeOutCubic,
      alignment: Alignment.topCenter,
      child: async.when(
        loading: () => const BarisSkeleton(),
        error: (e, _) => _GagalKecil(
          pesan: 'Estimasi harga belum bisa dimuat.',
          onRetry: () => ref.invalidate(priceEstimatesProvider(kunci)),
        ),
        data: (hasil) {
          if (hasil.items.isEmpty) {
            return Padding(
              padding: const EdgeInsets.symmetric(vertical: AppSpacing.lg),
              child: Text(
                'Belum ada estimasi ${Modul.label(modul).toLowerCase()} untuk kategori ini.',
                style: theme.textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
              ),
            );
          }

          return Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              for (final entry in hasil.perKelompok.entries) ...[
                Padding(
                  padding: const EdgeInsets.only(top: AppSpacing.sm, bottom: 2),
                  child: Text(
                    entry.key.toUpperCase(),
                    style: theme.textTheme.labelSmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                      letterSpacing: 1,
                    ),
                  ),
                ),
                for (final item in entry.value) _BarisHarga(item: item),
              ],
              if (hasil.disclaimer != null) ...[
                const SizedBox(height: AppSpacing.md),
                Text(
                  hasil.disclaimer!,
                  style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                ),
              ],
            ],
          );
        },
      ),
    );
  }
}

class _BarisHarga extends StatelessWidget {
  const _BarisHarga({required this.item});

  final PriceEstimate item;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Container(
      padding: const EdgeInsets.symmetric(vertical: 10),
      decoration: BoxDecoration(border: Border(bottom: BorderSide(color: scheme.outline.withValues(alpha: 0.7)))),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item.service, style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600)),
                if (item.durationMinutes != null || item.isAddon)
                  Text(
                    [
                      if (item.durationMinutes != null) '± ${Fmt.durasi(item.durationMinutes)}',
                      if (item.isAddon) 'tambahan',
                    ].join(' · '),
                    style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                  ),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          Text(Fmt.rupiah(item.price), style: AppTheme.money(context, fontSize: 14)),
        ],
      ),
    );
  }
}

class _GagalKecil extends StatelessWidget {
  const _GagalKecil({required this.pesan, required this.onRetry});

  final String pesan;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Row(
      children: [
        Icon(Icons.cloud_off_rounded, size: 18, color: theme.colorScheme.error),
        const SizedBox(width: AppSpacing.sm),
        Expanded(child: Text(pesan, style: theme.textTheme.bodySmall)),
        TextButton(onPressed: onRetry, child: const Text('Coba lagi')),
      ],
    );
  }
}
