import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_config.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../auth/presentation/auth_controller.dart';
import '../../blog/presentation/blog_providers.dart';
import '../../catalog/data/catalog_repository.dart';
import '../../shell/presentation/app_shell.dart';
import '../../vehicles/presentation/vehicle_providers.dart';
import 'widgets/coverage_card.dart';
import 'widgets/latest_posts.dart';
import 'widgets/module_cards.dart';
import 'widgets/price_estimator.dart';
import 'widgets/vehicle_hero_card.dart';

class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  Future<void> _segarkan(WidgetRef ref) async {
    ref.invalidate(latestPostsProvider);
    ref.invalidate(serviceAreasProvider);
    ref.invalidate(vehicleCategoriesProvider);
    await ref.read(vehiclesProvider.notifier).muatUlang();
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(authControllerProvider.select((s) => s.user));
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return SafeArea(
      bottom: false,
      child: RefreshIndicator(
        onRefresh: () => _segarkan(ref),
        child: ListView(
          padding: const EdgeInsets.only(bottom: AppSpacing.xxxl),
          children: [
            // ------------------------------------------------------ sapaan
            Padding(
              padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.lg, AppSpacing.lg, AppSpacing.lg),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${Fmt.sapaan()},',
                          style: theme.textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
                        ),
                        Text(
                          user?.namaDepan ?? 'Pengguna',
                          style: theme.textTheme.headlineMedium,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  _ChipKota(),
                  const SizedBox(width: AppSpacing.sm),
                  _Avatar(
                    initials: user?.initials ?? '?',
                    onTap: () => ref.read(tabAktifProvider.notifier).state = TabIndeks.profil,
                  ),
                ],
              ),
            ).animate().fadeIn(duration: 350.ms),

            const Padding(
              padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: VehicleHeroCard(),
            ),

            const SizedBox(height: AppSpacing.xxl),
            const ModuleCards(),

            const SizedBox(height: AppSpacing.xxl),
            const PriceEstimator(),

            const SizedBox(height: AppSpacing.xxl),
            const LatestPosts(),

            const SizedBox(height: AppSpacing.xxl),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              child: CoverageCard(),
            ),
          ],
        ),
      ),
    );
  }
}

class _ChipKota extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: BorderRadius.circular(AppRadius.pill),
        border: Border.all(color: theme.colorScheme.outline),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.place_outlined, size: 14, color: theme.colorScheme.onSurfaceVariant),
          const SizedBox(width: 4),
          Text(AppConfig.kota, style: theme.textTheme.labelMedium),
        ],
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({required this.initials, required this.onTap});

  final String initials;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Tooltip(
      message: 'Profil',
      child: Material(
        color: scheme.inverseSurface,
        shape: const CircleBorder(),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onTap,
          child: SizedBox(
            width: 40,
            height: 40,
            child: Center(
              child: Text(
                initials,
                style: Theme.of(context).textTheme.labelLarge?.copyWith(
                      color: scheme.onInverseSurface,
                      fontWeight: FontWeight.w800,
                    ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
