import 'package:flutter/material.dart';

/// Token warna OtoGo (sama dengan `public/assets/css/otogo-tokens.css`).
///
/// Netral hangat "kertas" + "tinta" hampir hitam, satu warna sinyal (oranye
/// OtoGo), dan kuning marka jalan yang dipakai sangat hemat. Oranye murni
/// tidak cukup kontras untuk teks kecil di atas putih, jadi teks oranye
/// memakai [orangeText].
class AppColors {
  const AppColors._();

  // ---------------------------------------------------------------- merek
  static const Color orange = Color(0xFFFF5A1F);
  static const Color orangePress = Color(0xFFE64A12);
  static const Color orangeText = Color(0xFFC2410C);
  static const Color orangeSoft = Color(0xFFFFE8DE);
  static const Color orangeDeep = Color(0xFF3A1A0C);

  /// Kuning marka jalan -- aksen kecil saja (garis putus-putus, titik aktif).
  static const Color yellow = Color(0xFFFFC531);

  // Nama lama dipertahankan agar widget turunan o-mobile tetap terbaca.
  static const Color primary = orange;
  static const Color primarySoft = orangeSoft;

  // ---------------------------------------------------------------- netral
  static const Color ink = Color(0xFF111418);
  static const Color ink2 = Color(0xFF2A2F36);
  static const Color inkMuted = Color(0xFF6B6F76);
  static const Color inkFaint = Color(0xFF9A9DA3);

  static const Color surface = Color(0xFFFFFFFF);
  static const Color surfaceAlt = Color(0xFFF9F8F5);
  static const Color background = Color(0xFFF5F3EE); // kertas
  static const Color paper2 = Color(0xFFECE8DF);
  static const Color outline = Color(0xFFDEDAD0);
  static const Color outlineStrong = Color(0xFFCFCABE);

  // ---------------------------------------------------------------- status
  static const Color success = Color(0xFF15803D);
  static const Color successSoft = Color(0xFFDCFCE7);
  static const Color danger = Color(0xFFDC2626);
  static const Color dangerSoft = Color(0xFFFDECEC);
  static const Color warning = Color(0xFFB45309);
  static const Color warningSoft = Color(0xFFFEF3C7);
  static const Color info = Color(0xFF2A2F36);
  static const Color infoSoft = Color(0xFFECE8DF);

  // ---------------------------------------------------------------- gelap
  static const Color darkBackground = Color(0xFF0C0E11);
  static const Color darkSurface = Color(0xFF15191E);
  static const Color darkSurfaceAlt = Color(0xFF1C2128);
  static const Color darkOutline = Color(0xFF2A2F36);
  static const Color darkInk = Color(0xFFEDEAE3);
  static const Color darkInkMuted = Color(0xFFA9A59C);

  // ---------------------------------------------------------------- pelat
  /// Pelat nomor Indonesia: dasar hitam, huruf putih (kode Medan "BK").
  static const Color plate = Color(0xFF111418);
  static const Color plateText = Color(0xFFF5F3EE);

  /// Latar gelap untuk splash, login, dan kartu sorotan.
  static const LinearGradient gradientTinta = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFF181C22), Color(0xFF0E1013)],
  );

  /// Warna per modul usaha.
  static Color modul(String code) => switch (code) {
        'workshop' => orange,
        'carwash' => const Color(0xFF2F7DB5),
        'sparepart' => const Color(0xFF15803D),
        _ => inkMuted,
      };
}
