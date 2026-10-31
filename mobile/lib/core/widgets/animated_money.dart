import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../utils/formatters.dart';

/// Angka rupiah yang berhitung naik/turun saat nilainya berubah.
///
/// Dipakai di kartu ringkasan dan estimasi harga: ketika pengguna mengubah
/// pagu atau persentase, angka bergerak ke nilai baru alih-alih berganti
/// mendadak, sehingga perubahannya terbaca.
///
/// `TweenAnimationBuilder` bawaan Flutter sudah menganimasikan dari nilai
/// tween saat ini ke `end` yang baru, jadi cukup mengganti `end` setiap build.
class AnimatedMoney extends StatelessWidget {
  const AnimatedMoney({
    super.key,
    required this.value,
    this.style,
    this.singkat = false,
    this.duration = const Duration(milliseconds: 650),
    this.prefix = true,
    this.textAlign,
    this.warnaNegatif,
  });

  final int value;
  final TextStyle? style;

  /// true = tampilkan bentuk pendek (Rp1,63 M) untuk ruang sempit.
  final bool singkat;

  final Duration duration;
  final bool prefix;
  final TextAlign? textAlign;
  final Color? warnaNegatif;

  @override
  Widget build(BuildContext context) {
    final dasar = style ?? AppTheme.money(context);
    final warna =
        value < 0 ? (warnaNegatif ?? Theme.of(context).colorScheme.error) : dasar.color;

    return TweenAnimationBuilder<double>(
      tween: Tween<double>(end: value.toDouble()),
      duration: duration,
      curve: Curves.easeOutCubic,
      builder: (context, angka, _) {
        final n = angka.round();

        // scaleDown, BUKAN ellipsis. Nominal yang terpotong jadi
        // "Rp12.312.312..." tidak ada gunanya -- justru digit terakhirnya
        // yang hilang. FittedBox membiarkan ukuran aslinya selama masih muat
        // dan hanya mengecilkannya bila kurang ruang, jadi angkanya selalu
        // tampil utuh di lebar layar apa pun.
        return FittedBox(
          fit: BoxFit.scaleDown,
          alignment: _keSelaras(textAlign),
          child: Text(
            singkat ? Fmt.rupiahSingkat(n, prefix: prefix) : Fmt.rupiah(n, prefix: prefix),
            style: dasar.copyWith(color: warna),
            textAlign: textAlign,
            maxLines: 1,
            softWrap: false,
          ),
        );
      },
    );
  }
}

/// Persen yang beranimasi, mis. "25,5%".
class AnimatedPersen extends StatelessWidget {
  const AnimatedPersen({
    super.key,
    required this.value,
    this.style,
    this.duration = const Duration(milliseconds: 500),
    this.maxDecimals = 2,
  });

  final double value;
  final TextStyle? style;
  final Duration duration;
  final int maxDecimals;

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween<double>(end: value),
      duration: duration,
      curve: Curves.easeOutCubic,
      builder: (context, angka, _) => FittedBox(
        fit: BoxFit.scaleDown,
        child: Text(
          Fmt.persen(angka, maxDecimals: maxDecimals),
          style: style ?? AppTheme.money(context, fontSize: 13),
          maxLines: 1,
          softWrap: false,
        ),
      ),
    );
  }
}

/// Bilangan bulat biasa (jumlah kendaraan, jumlah artikel) yang beranimasi.
class AnimatedAngka extends StatelessWidget {
  const AnimatedAngka({
    super.key,
    required this.value,
    this.style,
    this.suffix,
    this.duration = const Duration(milliseconds: 550),
  });

  final int value;
  final TextStyle? style;
  final String? suffix;
  final Duration duration;

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween<double>(end: value.toDouble()),
      duration: duration,
      curve: Curves.easeOutCubic,
      builder: (context, angka, _) => FittedBox(
        fit: BoxFit.scaleDown,
        child: Text(
          '${Fmt.angka(angka.round())}${suffix ?? ''}',
          style: style ?? AppTheme.money(context),
          maxLines: 1,
          softWrap: false,
        ),
      ),
    );
  }
}

/// Bar progres tipis yang bergerak ke nilai barunya.
/// Dipakai untuk "realisasi vs rencana".
class AnimatedProgressBar extends StatelessWidget {
  const AnimatedProgressBar({
    super.key,
    required this.value,
    this.color,
    this.background,
    this.height = 8,
    this.duration = const Duration(milliseconds: 700),
  });

  /// 0..1 (nilai di luar rentang akan dipangkas).
  final double value;

  final Color? color;
  final Color? background;
  final double height;
  final Duration duration;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return TweenAnimationBuilder<double>(
      tween: Tween<double>(end: value.clamp(0, 1)),
      duration: duration,
      curve: Curves.easeOutCubic,
      builder: (context, v, _) => ClipRRect(
        borderRadius: BorderRadius.circular(height),
        child: LinearProgressIndicator(
          value: v,
          minHeight: height,
          backgroundColor: background ?? scheme.surfaceContainerHighest,
          valueColor: AlwaysStoppedAnimation(color ?? scheme.primary),
        ),
      ),
    );
  }
}

/// Menerjemahkan TextAlign ke Alignment untuk FittedBox.
///
/// FittedBox menyelaraskan anaknya di dalam ruang yang tersisa; tanpa ini,
/// nominal yang mengecil akan melayang di tengah padahal kolomnya rata kanan.
Alignment _keSelaras(TextAlign? align) {
  if (align == TextAlign.right || align == TextAlign.end) {
    return Alignment.centerRight;
  }

  if (align == TextAlign.center) return Alignment.center;

  return Alignment.centerLeft;
}
