import 'package:flutter/material.dart';
import 'package:loading_animation_widget/loading_animation_widget.dart';

import '../theme/app_theme.dart';

enum AppButtonVariant { primary, secondary, outline, danger, ghost }

/// Tombol utama aplikasi.
///
/// Dua detail yang membuatnya terasa hidup:
/// 1. Skala mengecil saat ditekan (respons taktil).
/// 2. Saat memuat, label berganti animasi titik dan lebar tombol TIDAK berubah,
///    sehingga tata letak tidak melompat.
class AppButton extends StatefulWidget {
  const AppButton({
    super.key,
    required this.label,
    this.onPressed,
    this.icon,
    this.loading = false,
    this.variant = AppButtonVariant.primary,
    this.expand = true,
    this.loadingLabel,
  });

  final String label;
  final VoidCallback? onPressed;
  final IconData? icon;
  final bool loading;
  final AppButtonVariant variant;
  final bool expand;

  /// Teks pendamping animasi saat memuat, mis. "Menyimpan...".
  final String? loadingLabel;

  @override
  State<AppButton> createState() => _AppButtonState();
}

class _AppButtonState extends State<AppButton> {
  bool _tertekan = false;

  bool get _aktif => widget.onPressed != null && !widget.loading;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final (bg, fg, border) = _warna(scheme);

    final child = AnimatedSize(
      duration: const Duration(milliseconds: 220),
      curve: Curves.easeOutCubic,
      child: widget.loading ? _isiMemuat(fg) : _isiNormal(fg),
    );

    return AnimatedScale(
      scale: _tertekan && _aktif ? 0.97 : 1,
      duration: const Duration(milliseconds: 110),
      curve: Curves.easeOut,
      child: AnimatedOpacity(
        opacity: _aktif ? 1 : 0.55,
        duration: const Duration(milliseconds: 180),
        child: SizedBox(
          width: widget.expand ? double.infinity : null,
          height: 52,
          child: Material(
            color: bg,
            borderRadius: AppRadius.field,
            clipBehavior: Clip.antiAlias,
            child: InkWell(
              onTap: _aktif ? widget.onPressed : null,
              onTapDown: (_) => setState(() => _tertekan = true),
              onTapUp: (_) => setState(() => _tertekan = false),
              onTapCancel: () => setState(() => _tertekan = false),
              borderRadius: AppRadius.field,
              splashColor: fg.withValues(alpha: 0.12),
              highlightColor: fg.withValues(alpha: 0.06),
              child: Container(
                decoration: BoxDecoration(
                  borderRadius: AppRadius.field,
                  border: border == null ? null : Border.all(color: border, width: 1.4),
                ),
                alignment: Alignment.center,
                padding: const EdgeInsets.symmetric(horizontal: AppSpacing.xl),
                child: child,
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _isiNormal(Color fg) {
    final teks = Text(
      widget.label,
      key: const ValueKey('label'),
      style: Theme.of(context).textTheme.titleSmall?.copyWith(
            color: fg,
            fontWeight: FontWeight.w700,
          ),
      overflow: TextOverflow.ellipsis,
    );

    if (widget.icon == null) return teks;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(widget.icon, size: 19, color: fg),
        const SizedBox(width: AppSpacing.sm),
        Flexible(child: teks),
      ],
    );
  }

  Widget _isiMemuat(Color fg) {
    return Row(
      key: const ValueKey('loading'),
      mainAxisSize: MainAxisSize.min,
      children: [
        LoadingAnimationWidget.staggeredDotsWave(color: fg, size: 26),
        if (widget.loadingLabel != null) ...[
          const SizedBox(width: AppSpacing.md),
          Text(
            widget.loadingLabel!,
            style: Theme.of(context).textTheme.titleSmall?.copyWith(color: fg),
          ),
        ],
      ],
    );
  }

  /// (background, foreground, border)
  (Color, Color, Color?) _warna(ColorScheme scheme) => switch (widget.variant) {
        AppButtonVariant.primary => (scheme.primary, scheme.onPrimary, null),
        AppButtonVariant.secondary => (
            scheme.secondaryContainer,
            scheme.onSecondaryContainer,
            null,
          ),
        AppButtonVariant.outline => (
            Colors.transparent,
            scheme.primary,
            scheme.primary.withValues(alpha: 0.45),
          ),
        AppButtonVariant.danger => (scheme.error, scheme.onError, null),
        AppButtonVariant.ghost => (
            scheme.surfaceContainer,
            scheme.onSurface,
            scheme.outline,
          ),
      };
}

/// Tombol ikon bulat dengan animasi tekan, dipakai di app bar & kartu.
class AppIconButton extends StatefulWidget {
  const AppIconButton({
    super.key,
    required this.icon,
    this.onPressed,
    this.tooltip,
    this.background,
    this.foreground,
    this.size = 42,
    this.loading = false,
  });

  final IconData icon;
  final VoidCallback? onPressed;
  final String? tooltip;
  final Color? background;
  final Color? foreground;
  final double size;
  final bool loading;

  @override
  State<AppIconButton> createState() => _AppIconButtonState();
}

class _AppIconButtonState extends State<AppIconButton> {
  bool _tertekan = false;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final fg = widget.foreground ?? scheme.onSurface;

    final tombol = AnimatedScale(
      scale: _tertekan ? 0.9 : 1,
      duration: const Duration(milliseconds: 110),
      child: Material(
        color: widget.background ?? scheme.surfaceContainer,
        shape: const CircleBorder(),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: widget.loading ? null : widget.onPressed,
          onTapDown: (_) => setState(() => _tertekan = true),
          onTapUp: (_) => setState(() => _tertekan = false),
          onTapCancel: () => setState(() => _tertekan = false),
          child: SizedBox(
            width: widget.size,
            height: widget.size,
            child: Center(
              child: widget.loading
                  ? SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2, color: fg),
                    )
                  : Icon(widget.icon, size: widget.size * 0.45, color: fg),
            ),
          ),
        ),
      ),
    );

    return widget.tooltip == null
        ? tombol
        : Tooltip(message: widget.tooltip!, child: tombol);
  }
}
