import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_theme.dart';
import '../utils/formatters.dart';

/// Pelat nomor kendaraan bergaya pelat Indonesia: dasar hitam, bingkai tipis,
/// huruf putih bermotif mono. Dipakai di kartu kendaraan dan beranda.
class PlatNomor extends StatelessWidget {
  const PlatNomor(this.nomor, {super.key, this.ukuran = 1});

  final String nomor;

  /// Pengali ukuran; 1 = ukuran kartu daftar.
  final double ukuran;

  @override
  Widget build(BuildContext context) {
    final u = ukuran;

    return Container(
      padding: EdgeInsets.all(3 * u),
      decoration: BoxDecoration(
        color: AppColors.plate,
        borderRadius: BorderRadius.circular(7 * u),
      ),
      child: Container(
        padding: EdgeInsets.symmetric(horizontal: 10 * u, vertical: 4 * u),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(5 * u),
          border: Border.all(color: AppColors.plateText.withValues(alpha: 0.75), width: 1.2 * u),
        ),
        child: Text(
          Fmt.plat(nomor),
          maxLines: 1,
          style: AppTheme.money(
            context,
            fontSize: 15 * u,
            weight: FontWeight.w700,
            color: AppColors.plateText,
          ).copyWith(letterSpacing: 1.2 * u),
        ),
      ),
    );
  }
}
