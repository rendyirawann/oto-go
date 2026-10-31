import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../utils/formatters.dart';

/// Menampilkan nominal rupiah yang SELALU utuh.
///
/// Nominal tidak boleh dipotong. "Rp12.312.312..." menghilangkan justru digit
/// yang menentukan besarannya, dan pengguna tidak punya cara tahu angka
/// sebenarnya. Karena itu widget ini memakai `FittedBox(scaleDown)`: ukuran
/// aslinya dipakai selama masih muat, dan hanya mengecil bila kolomnya
/// kurang lebar -- perilaku yang sama di layar 320px maupun tablet.
///
/// Untuk nominal yang perlu beranimasi naik, pakai `AnimatedMoney`.
class Uang extends StatelessWidget {
  const Uang(
    this.nilai, {
    super.key,
    this.style,
    this.fontSize,
    this.weight,
    this.color,
    this.prefix = true,
    this.singkat = false,
    this.align = TextAlign.left,
  }) : teks = null;

  /// Menampilkan teks yang sudah diformat server (mis. `subtotal_formatted`),
  /// sehingga pembulatannya persis sama dengan yang tersimpan.
  const Uang.teks(
    this.teks, {
    super.key,
    this.style,
    this.fontSize,
    this.weight,
    this.color,
    this.align = TextAlign.left,
  })  : nilai = null,
        prefix = true,
        singkat = false;

  final num? nilai;
  final String? teks;
  final TextStyle? style;
  final double? fontSize;
  final FontWeight? weight;
  final Color? color;
  final bool prefix;

  /// "Rp1,63 M" alih-alih "Rp1.625.000.000".
  final bool singkat;

  final TextAlign align;

  String get _tampil {
    if (teks != null) return teks!;

    return singkat
        ? Fmt.rupiahSingkat(nilai, prefix: prefix)
        : Fmt.rupiah(nilai, prefix: prefix);
  }

  Alignment get _selaras => switch (align) {
        TextAlign.right => Alignment.centerRight,
        TextAlign.end => Alignment.centerRight,
        TextAlign.center => Alignment.center,
        _ => Alignment.centerLeft,
      };

  @override
  Widget build(BuildContext context) {
    final dasar = style ??
        AppTheme.money(
          context,
          fontSize: fontSize,
          weight: weight,
          color: color,
        );

    return FittedBox(
      fit: BoxFit.scaleDown,
      alignment: _selaras,
      child: Text(
        _tampil,
        style: color == null ? dasar : dasar.copyWith(color: color),
        textAlign: align,
        maxLines: 1,
        softWrap: false,
      ),
    );
  }
}
