import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../theme/app_colors.dart';
import '../theme/app_theme.dart';
import 'app_button.dart';

enum PesanJenis { sukses, galat, info, peringatan }

/// Notifikasi mengapung dengan ikon beranimasi.
///
/// Selalu dibungkus Material dan memakai gaya teks dari tema, sehingga tidak
/// ada teks yang lolos ke gaya debug bergaris bawah kuning.
class AppSnack {
  const AppSnack._();

  static void tampil(
    BuildContext context,
    String pesan, {
    PesanJenis jenis = PesanJenis.info,
    Duration durasi = const Duration(seconds: 4),
    SnackBarAction? aksi,
  }) {
    final messenger = ScaffoldMessenger.maybeOf(context);

    if (messenger == null) return;

    final (warna, ikon) = _gaya(jenis);

    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          duration: durasi,
          behavior: SnackBarBehavior.floating,
          backgroundColor: Theme.of(context).colorScheme.inverseSurface,
          shape: const RoundedRectangleBorder(borderRadius: AppRadius.field),
          margin: const EdgeInsets.all(AppSpacing.lg),
          padding: const EdgeInsets.symmetric(
            horizontal: AppSpacing.lg,
            vertical: AppSpacing.md,
          ),
          action: aksi,
          content: Row(
            children: [
              Container(
                width: 26,
                height: 26,
                decoration: BoxDecoration(
                  color: warna.withValues(alpha: 0.22),
                  shape: BoxShape.circle,
                ),
                child: Icon(ikon, size: 15, color: warna),
              )
                  .animate()
                  .scaleXY(begin: 0.4, end: 1, duration: 320.ms, curve: Curves.easeOutBack),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Text(
                  pesan,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Theme.of(context).colorScheme.onInverseSurface,
                      ),
                ),
              ),
            ],
          ),
        ),
      );
  }

  static void sukses(BuildContext context, String pesan) =>
      tampil(context, pesan, jenis: PesanJenis.sukses);

  static void galat(BuildContext context, String pesan) =>
      tampil(context, pesan, jenis: PesanJenis.galat, durasi: const Duration(seconds: 5));

  static void info(BuildContext context, String pesan) =>
      tampil(context, pesan, jenis: PesanJenis.info);

  static void peringatan(BuildContext context, String pesan) =>
      tampil(context, pesan, jenis: PesanJenis.peringatan);

  static (Color, IconData) _gaya(PesanJenis jenis) => switch (jenis) {
        PesanJenis.sukses => (const Color(0xFF4ADE80), Icons.check_rounded),
        PesanJenis.galat => (const Color(0xFFFF6B6B), Icons.close_rounded),
        PesanJenis.peringatan => (AppColors.yellow, Icons.warning_amber_rounded),
        PesanJenis.info => (const Color(0xFFFF8A5B), Icons.info_outline_rounded),
      };
}

/// Dialog konfirmasi. Mengembalikan true bila pengguna menyetujui.
Future<bool> konfirmasi(
  BuildContext context, {
  required String judul,
  required String pesan,
  String labelSetuju = 'Ya, Lanjutkan',
  String labelBatal = 'Batal',
  bool berbahaya = false,
  IconData? ikon,
}) async {
  final scheme = Theme.of(context).colorScheme;
  final warna = berbahaya ? scheme.error : scheme.primary;

  final hasil = await showDialog<bool>(
    context: context,
    builder: (context) => AlertDialog(
      icon: Container(
        width: 52,
        height: 52,
        decoration: BoxDecoration(
          color: warna.withValues(alpha: 0.12),
          shape: BoxShape.circle,
        ),
        child: Icon(
          ikon ?? (berbahaya ? Icons.delete_outline_rounded : Icons.help_outline_rounded),
          color: warna,
          size: 26,
        ),
      ).animate().scaleXY(begin: 0.5, end: 1, duration: 280.ms, curve: Curves.easeOutBack),
      title: Text(judul, textAlign: TextAlign.center),
      content: Text(pesan, textAlign: TextAlign.center),
      actionsPadding: const EdgeInsets.fromLTRB(
        AppSpacing.lg,
        0,
        AppSpacing.lg,
        AppSpacing.lg,
      ),
      actions: [
        Row(
          children: [
            Expanded(
              child: AppButton(
                label: labelBatal,
                variant: AppButtonVariant.ghost,
                onPressed: () => Navigator.of(context).pop(false),
              ),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: AppButton(
                label: labelSetuju,
                variant: berbahaya ? AppButtonVariant.danger : AppButtonVariant.primary,
                onPressed: () => Navigator.of(context).pop(true),
              ),
            ),
          ],
        ),
      ],
    ),
  );

  return hasil ?? false;
}

/// Overlay pemuatan penuh layar dengan animasi tetes tinta.
class LoadingOverlay extends StatelessWidget {
  const LoadingOverlay({super.key, this.pesan});

  final String? pesan;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Material(
      color: scheme.scrim.withValues(alpha: 0.45),
      child: Center(
        child: Container(
          padding: const EdgeInsets.all(AppSpacing.xxl),
          decoration: BoxDecoration(
            color: scheme.surface,
            borderRadius: AppRadius.card,
            boxShadow: AppShadows.raised,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              SizedBox(
                width: 44,
                height: 44,
                child: CircularProgressIndicator(color: scheme.primary, strokeWidth: 3),
              ),
              if (pesan != null) ...[
                const SizedBox(height: AppSpacing.lg),
                Text(pesan!, style: Theme.of(context).textTheme.bodyMedium),
              ],
            ],
          ),
        ).animate().scaleXY(begin: 0.85, end: 1, duration: 220.ms, curve: Curves.easeOutCubic),
      ),
    );
  }
}
