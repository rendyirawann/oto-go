import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../theme/app_colors.dart';

/// Tanda OtoGo: huruf "O" yang ujung kanannya meruncing ke depan -- dibaca
/// sebagai O sekaligus penanda arah/lokasi (antar jemput, layanan di rumah).
///
/// Digambar sebagai vektor dari geometri yang sama dengan
/// `resources/brand/make_brand.py`, jadi tajam di semua ukuran dan warnanya
/// bisa mengikuti tema.
class BrandLogo extends StatelessWidget {
  const BrandLogo({
    super.key,
    this.size = 56,
    this.tile = true,
    this.warnaTile = AppColors.ink,
    this.warnaTanda = AppColors.orange,
  });

  /// Tanda saja tanpa ubin, untuk diletakkan di atas latar gelap/kartu.
  const BrandLogo.tanda({super.key, this.size = 56, this.warnaTanda = AppColors.orange})
      : tile = false,
        warnaTile = Colors.transparent;

  final double size;
  final bool tile;
  final Color warnaTile;
  final Color warnaTanda;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: 'Logo OtoGo',
      image: true,
      child: SizedBox(
        width: size,
        height: size,
        child: CustomPaint(
          painter: _MarkPainter(tile: tile, warnaTile: warnaTile, warnaTanda: warnaTanda),
        ),
      ),
    );
  }
}

class _MarkPainter extends CustomPainter {
  const _MarkPainter({required this.tile, required this.warnaTile, required this.warnaTanda});

  final bool tile;
  final Color warnaTile;
  final Color warnaTanda;

  // Geometri dalam kotak 512 (sama dengan brand kit).
  static const double _cx = 226;
  static const double _cy = 256;
  static const double _r = 108;
  static const double _stroke = 58;
  static const double _apexX = 392;
  static const double _hub = 26;

  @override
  void paint(Canvas canvas, Size size) {
    final k = size.width / 512;

    canvas.save();
    canvas.scale(k);

    if (tile) {
      canvas.drawRRect(
        RRect.fromRectAndRadius(const Rect.fromLTWH(0, 0, 512, 512), const Radius.circular(120)),
        Paint()..color = warnaTile,
      );
    }

    // Garis singgung dari ujung ke lingkaran: sambungan lengkung-ke-garis
    // jadi mulus, tanpa patahan.
    final theta = math.acos(_r / (_apexX - _cx));
    final atas = Offset(_cx + _r * math.cos(theta), _cy - _r * math.sin(theta));
    final bawah = Offset(_cx + _r * math.cos(theta), _cy + _r * math.sin(theta));

    final path = Path()
      ..moveTo(atas.dx, atas.dy)
      ..arcTo(
        Rect.fromCircle(center: const Offset(_cx, _cy), radius: _r),
        -theta,
        -(2 * math.pi - 2 * theta),
        false,
      )
      ..lineTo(bawah.dx, bawah.dy)
      ..lineTo(_apexX, _cy)
      ..close();

    canvas.drawPath(
      path,
      Paint()
        ..color = warnaTanda
        ..style = PaintingStyle.stroke
        ..strokeWidth = _stroke
        ..strokeJoin = StrokeJoin.round
        ..isAntiAlias = true,
    );

    canvas.drawCircle(const Offset(_cx, _cy), _hub, Paint()..color = warnaTanda);
    canvas.restore();
  }

  @override
  bool shouldRepaint(_MarkPainter old) =>
      old.tile != tile || old.warnaTile != warnaTile || old.warnaTanda != warnaTanda;
}

/// Logotype "otogo" geometris (huruf o terakhir oranye).
class BrandWordmark extends StatelessWidget {
  const BrandWordmark({super.key, this.height = 28, this.warna});

  final double height;

  /// Warna huruf; default mengikuti warna teks tema.
  final Color? warna;

  @override
  Widget build(BuildContext context) {
    final w = warna ?? Theme.of(context).colorScheme.onSurface;

    return Semantics(
      label: 'OtoGo',
      child: SizedBox(
        height: height,
        width: height * 286 / 120,
        child: CustomPaint(painter: _WordmarkPainter(warna: w)),
      ),
    );
  }
}

class _WordmarkPainter extends CustomPainter {
  const _WordmarkPainter({required this.warna});

  final Color warna;

  @override
  void paint(Canvas canvas, Size size) {
    final k = size.height / 120;

    canvas.save();
    canvas.scale(k);
    canvas.translate(-128, 0);

    Paint pena(Color c) => Paint()
      ..color = c
      ..style = PaintingStyle.stroke
      ..strokeWidth = 13
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..isAntiAlias = true;

    final tinta = pena(warna);

    // o
    canvas.drawCircle(const Offset(154, 60), 19.5, tinta);
    // t
    canvas.drawPath(
      Path()
        ..moveTo(199, 18)
        ..lineTo(199, 68)
        ..quadraticBezierTo(199, 79.5, 211, 79.5)
        ..lineTo(214, 79.5),
      tinta,
    );
    canvas.drawLine(const Offset(188, 40.5), const Offset(214, 40.5), tinta);
    // o
    canvas.drawCircle(const Offset(254, 60), 19.5, tinta);
    // g
    canvas.drawCircle(const Offset(314, 60), 19.5, tinta);
    canvas.drawPath(
      Path()
        ..moveTo(333.5, 40.5)
        ..lineTo(333.5, 88)
        ..quadraticBezierTo(333.5, 106, 315, 106)
        ..quadraticBezierTo(303, 106, 298, 99),
      tinta,
    );
    // o (aksen)
    canvas.drawCircle(const Offset(376, 60), 19.5, pena(AppColors.orange));

    canvas.restore();
  }

  @override
  bool shouldRepaint(_WordmarkPainter old) => old.warna != warna;
}

/// Logo lengkap (tanda + logotype + subjudul), dipakai di splash dan login.
class BrandLockup extends StatelessWidget {
  const BrandLockup({
    super.key,
    this.size = 72,
    this.terang = true,
    this.tampilkanSubjudul = true,
  });

  final double size;

  /// true = versi untuk latar gelap.
  final bool terang;

  final bool tampilkanSubjudul;

  static const String nama = 'OtoGo';
  static const String subjudul = 'Bengkel, doorsmeer & sparepart dalam satu aplikasi';

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final warnaTeks = terang ? AppColors.background : AppColors.ink;

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        BrandLogo(size: size, warnaTile: terang ? const Color(0xFF1C2027) : AppColors.ink),
        SizedBox(height: size * 0.3),
        BrandWordmark(height: size * 0.5, warna: warnaTeks),
        if (tampilkanSubjudul) ...[
          const SizedBox(height: 10),
          Text(
            subjudul,
            textAlign: TextAlign.center,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: warnaTeks.withValues(alpha: 0.7),
            ),
          ),
        ],
      ],
    );
  }
}
