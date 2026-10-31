import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../../../../core/config/app_config.dart';
import '../../../../core/theme/app_colors.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/brand_logo.dart';
import '../../../../routing/splash_screen.dart';
import '../../../settings/presentation/alamat_api_sheet.dart';

/// Kerangka layar masuk & daftar: kepala tinta dengan logo dan marka jalan,
/// lalu kartu form yang naik menutupi sebagian kepala.
class AuthScaffold extends StatelessWidget {
  const AuthScaffold({
    super.key,
    required this.judul,
    required this.subjudul,
    required this.form,
    this.leading,
  });

  final String judul;
  final String subjudul;
  final Widget form;
  final Widget? leading;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: LayoutBuilder(
        builder: (context, constraints) => SingleChildScrollView(
          child: ConstrainedBox(
            constraints: BoxConstraints(minHeight: constraints.maxHeight),
            child: Column(
              children: [
                // ------------------------------------------------ kepala
                Container(
                  width: double.infinity,
                  decoration: const BoxDecoration(gradient: AppColors.gradientTinta),
                  child: SafeArea(
                    bottom: false,
                    child: Stack(
                      children: [
                        const Positioned(left: 0, right: 0, bottom: 52, child: MarkaJalan(opacity: 0.22)),
                        Padding(
                          padding: const EdgeInsets.fromLTRB(AppSpacing.xl, AppSpacing.lg, AppSpacing.xl, 88),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  ?leading,
                                  const BrandLogo(size: 40, warnaTile: Color(0xFF1C2027)),
                                  const SizedBox(width: AppSpacing.md),
                                  const BrandWordmark(height: 22, warna: AppColors.background),
                                  const SizedBox(width: AppSpacing.md),
                                  // Chip kota mengalah lebih dulu di layar sempit.
                                  Expanded(
                                    child: Align(
                                      alignment: Alignment.centerRight,
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                        decoration: BoxDecoration(
                                          borderRadius: BorderRadius.circular(AppRadius.pill),
                                          border: Border.all(color: AppColors.background.withValues(alpha: 0.2)),
                                        ),
                                        child: Text(
                                          'Kota ${AppConfig.kota}',
                                          maxLines: 1,
                                          overflow: TextOverflow.fade,
                                          softWrap: false,
                                          style: theme.textTheme.labelSmall?.copyWith(
                                            color: AppColors.background.withValues(alpha: 0.75),
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: AppSpacing.xxxl),
                              Text(
                                judul,
                                style: theme.textTheme.headlineLarge?.copyWith(color: AppColors.background),
                              ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.3, end: 0),
                              const SizedBox(height: AppSpacing.sm),
                              Text(
                                subjudul,
                                style: theme.textTheme.bodyMedium?.copyWith(
                                  color: AppColors.background.withValues(alpha: 0.72),
                                ),
                              ).animate(delay: 120.ms).fadeIn(duration: 400.ms),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // ------------------------------------------------ form
                Transform.translate(
                  offset: const Offset(0, -56),
                  child: Center(
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 480),
                      child: Container(
                        margin: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
                        padding: const EdgeInsets.all(AppSpacing.xl),
                        decoration: BoxDecoration(
                          color: scheme.surface,
                          borderRadius: BorderRadius.circular(AppRadius.xl),
                          border: Border.all(color: scheme.outline),
                          boxShadow: AppShadows.raised,
                        ),
                        child: form,
                      )
                          .animate(delay: 200.ms)
                          .fadeIn(duration: 450.ms)
                          .slideY(begin: 0.08, end: 0, curve: Curves.easeOutCubic),
                    ),
                  ),
                ),

                // ------------------------------------------------ kaki
                Transform.translate(
                  offset: const Offset(0, -40),
                  child: TextButton.icon(
                    onPressed: () => AlamatApiSheet.tampil(context),
                    icon: Icon(Icons.dns_outlined, size: 16, color: scheme.onSurfaceVariant),
                    label: Text(
                      'Server: ${AppConfig.baseUrl}',
                      style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
