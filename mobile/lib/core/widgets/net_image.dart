import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

/// Gambar dari jaringan dengan placeholder dan penanganan galat.
///
/// Dua hal yang ditangani di sini supaya layar lain tidak perlu peduli:
///
/// 1. **SVG.** Sampul artikel OtoGo berupa SVG. `Image.network` tidak bisa
///    mendekode SVG, jadi di perangkat native dipakai flutter_svg, sedangkan
///    di web dipakai elemen `<img>` milik browser (yang memang merender SVG).
/// 2. **CORS di web.** Gambar dari host API dimuat lewat XHR dan butuh header
///    CORS. `WebHtmlElementStrategy` membuat Flutter memakai `<img>` biasa
///    -- elemen itu tidak terikat CORS -- sehingga gambar tetap tampil walau
///    server statis tidak mengirim header tersebut.
class NetImage extends StatelessWidget {
  const NetImage(
    this.url, {
    super.key,
    this.fit = BoxFit.cover,
    this.width,
    this.height,
    this.borderRadius,
  });

  final String? url;
  final BoxFit fit;
  final double? width;
  final double? height;
  final BorderRadius? borderRadius;

  static bool isSvg(String url) => (Uri.tryParse(url)?.path ?? url).toLowerCase().endsWith('.svg');

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    Widget kosong() => Container(
          width: width,
          height: height,
          color: scheme.surfaceContainerHigh,
          alignment: Alignment.center,
          child: Icon(Icons.image_outlined, color: scheme.onSurfaceVariant.withValues(alpha: 0.5)),
        );

    Widget memuat() => Container(width: width, height: height, color: scheme.surfaceContainerHigh);

    final u = url;
    Widget gambar;

    if (u == null || u.isEmpty) {
      gambar = kosong();
    } else if (isSvg(u) && !kIsWeb) {
      gambar = SvgPicture.network(
        u,
        width: width,
        height: height,
        fit: fit,
        placeholderBuilder: (_) => memuat(),
        errorBuilder: (_, _, _) => kosong(),
      );
    } else {
      gambar = Image.network(
        u,
        width: width,
        height: height,
        fit: fit,
        // SVG di web: langsung pakai <img>. Raster: coba XHR dulu (lebih
        // efisien), jatuh ke <img> bila CORS menolak.
        webHtmlElementStrategy: isSvg(u) ? WebHtmlElementStrategy.prefer : WebHtmlElementStrategy.fallback,
        errorBuilder: (_, _, _) => kosong(),
        loadingBuilder: (context, child, progress) => progress == null ? child : memuat(),
      );
    }

    // Ukuran dipaksa agar <img> (platform view) di web tidak melebar keluar.
    gambar = SizedBox(width: width, height: height, child: gambar);

    if (borderRadius != null) {
      gambar = ClipRRect(borderRadius: borderRadius!, child: gambar);
    }

    return gambar;
  }
}
