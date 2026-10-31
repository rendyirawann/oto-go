import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/common.dart';
import '../../../catalog/data/catalog_repository.dart';

/// Wilayah layanan awal (kecamatan di Kota Medan), bisa dibuka-tutup.
class CoverageCard extends ConsumerStatefulWidget {
  const CoverageCard({super.key});

  @override
  ConsumerState<CoverageCard> createState() => _CoverageCardState();
}

class _CoverageCardState extends ConsumerState<CoverageCard> {
  bool _terbuka = false;

  @override
  Widget build(BuildContext context) {
    final async = ref.watch(serviceAreasProvider);
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    // Disembunyikan bila gagal: informasi pelengkap, bukan fungsi utama.
    if (async.hasError) return const SizedBox.shrink();

    final area = async.valueOrNull ?? const [];

    return SectionCard(
      title: 'Wilayah layanan',
      subtitle: area.isEmpty ? 'Memuat...' : '${area.length} kecamatan di Kota Medan',
      icon: Icons.map_outlined,
      trailing: area.isEmpty
          ? null
          : IconButton(
              tooltip: _terbuka ? 'Tutup' : 'Lihat semua',
              onPressed: () => setState(() => _terbuka = !_terbuka),
              icon: AnimatedRotation(
                turns: _terbuka ? 0.5 : 0,
                duration: const Duration(milliseconds: 220),
                child: const Icon(Icons.expand_more_rounded),
              ),
            ),
      child: AnimatedCrossFade(
        duration: const Duration(milliseconds: 240),
        crossFadeState: _terbuka ? CrossFadeState.showSecond : CrossFadeState.showFirst,
        firstChild: Text(
          'Mulai dari Medan. Antar jemput dan layanan di rumah mengikuti radius tiap outlet.',
          style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
        ),
        secondChild: Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: [
            for (final a in area)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(AppRadius.pill),
                  border: Border.all(color: scheme.outline),
                ),
                child: Text(a.district, style: theme.textTheme.labelMedium),
              ),
          ],
        ),
      ),
    );
  }
}
