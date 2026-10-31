import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:loading_animation_widget/loading_animation_widget.dart';

import '../core/theme/app_colors.dart';
import '../core/theme/app_theme.dart';
import '../core/widgets/brand_logo.dart';

/// Layar pembuka selama sesi dipulihkan. Warna tinta yang sama dengan layar
/// peluncuran Android dan latar web, jadi peralihannya tidak berkedip.
class SplashScreen extends StatelessWidget {
  const SplashScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: AppColors.ink,
      body: Stack(
        children: [
          const Positioned(left: 0, right: 0, bottom: 56, child: MarkaJalan()),
          Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const BrandLogo(size: 88, warnaTile: Color(0xFF1C2027))
                    .animate()
                    .scaleXY(begin: 0.7, end: 1, duration: 520.ms, curve: Curves.easeOutBack)
                    .fadeIn(duration: 300.ms),
                const SizedBox(height: AppSpacing.xxl),
                const BrandWordmark(height: 34, warna: AppColors.background)
                    .animate(delay: 160.ms)
                    .fadeIn(duration: 400.ms)
                    .slideY(begin: 0.3, end: 0, curve: Curves.easeOutCubic),
                const SizedBox(height: AppSpacing.xxxl),
                LoadingAnimationWidget.staggeredDotsWave(color: AppColors.orange, size: 36),
                const SizedBox(height: AppSpacing.md),
                Text(
                  'Menyiapkan garasi Anda...',
                  style: theme.textTheme.bodySmall?.copyWith(color: AppColors.background.withValues(alpha: 0.6)),
                ).animate(delay: 400.ms).fadeIn(),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

/// Garis marka jalan putus-putus yang bergerak pelan -- motif visual OtoGo.
class MarkaJalan extends StatelessWidget {
  const MarkaJalan({super.key, this.warna = AppColors.yellow, this.opacity = 0.35});

  final Color warna;
  final double opacity;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 6,
      child: ClipRect(
        child: OverflowBox(
          maxWidth: double.infinity,
          alignment: Alignment.centerLeft,
          child: Row(
            children: [
              for (var i = 0; i < 40; i++)
                Container(
                  width: 34,
                  height: 6,
                  margin: const EdgeInsets.only(right: 22),
                  decoration: BoxDecoration(
                    color: warna.withValues(alpha: opacity),
                    borderRadius: BorderRadius.circular(3),
                  ),
                ),
            ],
          ),
        )
            .animate(onPlay: (c) => c.repeat())
            .moveX(begin: 0, end: -56, duration: 900.ms, curve: Curves.linear),
      ),
    );
  }
}
