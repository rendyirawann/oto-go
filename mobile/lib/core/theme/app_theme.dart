import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';

import 'app_colors.dart';

/// Skala jarak. Dipakai konsisten agar ritme layout tidak acak.
class AppSpacing {
  const AppSpacing._();

  static const double xs = 4;
  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 20;
  static const double xxl = 24;
  static const double xxxl = 32;
}

class AppRadius {
  const AppRadius._();

  static const double sm = 8;
  static const double md = 12;
  static const double lg = 16;
  static const double xl = 22;
  static const double pill = 999;

  static const BorderRadius card = BorderRadius.all(Radius.circular(lg));
  static const BorderRadius field = BorderRadius.all(Radius.circular(md));
  static const BorderRadius sheet = BorderRadius.vertical(top: Radius.circular(xl));
}

class AppShadows {
  const AppShadows._();

  /// Bayangan tipis: kedalaman datang dari garis tepi, bukan dari blur besar.
  static const List<BoxShadow> card = [
    BoxShadow(color: Color(0x0A111418), blurRadius: 3, offset: Offset(0, 1)),
  ];

  static const List<BoxShadow> raised = [
    BoxShadow(color: Color(0x1F111418), blurRadius: 32, offset: Offset(0, 14)),
  ];
}

class AppTheme {
  const AppTheme._();

  /// Dimatikan di widget test: google_fonts mengunduh font saat berjalan,
  /// sedangkan test tidak punya jaringan.
  static bool pakaiGoogleFonts = true;

  static ThemeData get light => _build(Brightness.light);

  static ThemeData get dark => _build(Brightness.dark);

  static ThemeData _build(Brightness brightness) {
    final isDark = brightness == Brightness.dark;

    final scheme = ColorScheme(
      brightness: brightness,
      primary: AppColors.orange,
      onPrimary: Colors.white,
      primaryContainer: isDark ? AppColors.orangeDeep : AppColors.orangeSoft,
      onPrimaryContainer: isDark ? const Color(0xFFFFB896) : AppColors.orangeText,
      secondary: isDark ? AppColors.darkInk : AppColors.ink,
      onSecondary: isDark ? AppColors.ink : Colors.white,
      secondaryContainer: isDark ? AppColors.darkSurfaceAlt : AppColors.paper2,
      onSecondaryContainer: isDark ? AppColors.darkInk : AppColors.ink,
      tertiary: AppColors.yellow,
      onTertiary: AppColors.ink,
      error: AppColors.danger,
      onError: Colors.white,
      errorContainer: isDark ? const Color(0xFF3A1414) : AppColors.dangerSoft,
      onErrorContainer: isDark ? const Color(0xFFFFB4B4) : AppColors.danger,
      surface: isDark ? AppColors.darkSurface : AppColors.surface,
      onSurface: isDark ? AppColors.darkInk : AppColors.ink,
      surfaceContainerLowest: isDark ? AppColors.darkBackground : AppColors.surface,
      surfaceContainerLow: isDark ? AppColors.darkSurface : AppColors.surfaceAlt,
      surfaceContainer: isDark ? AppColors.darkSurfaceAlt : AppColors.surfaceAlt,
      surfaceContainerHigh: isDark ? AppColors.darkSurfaceAlt : AppColors.paper2,
      surfaceContainerHighest: isDark ? const Color(0xFF232931) : AppColors.paper2,
      onSurfaceVariant: isDark ? AppColors.darkInkMuted : AppColors.inkMuted,
      outline: isDark ? AppColors.darkOutline : AppColors.outline,
      outlineVariant: isDark ? const Color(0xFF353B44) : AppColors.outlineStrong,
      inverseSurface: isDark ? AppColors.darkInk : AppColors.ink,
      onInverseSurface: isDark ? AppColors.ink : AppColors.background,
      shadow: Colors.black,
      scrim: Colors.black,
    );

    final textTheme = _textTheme(scheme);
    final latar = isDark ? AppColors.darkBackground : AppColors.background;

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: latar,
      textTheme: textTheme,
      primaryTextTheme: textTheme,
      splashFactory: InkSparkle.splashFactory,

      appBarTheme: AppBarTheme(
        backgroundColor: latar,
        foregroundColor: scheme.onSurface,
        elevation: 0,
        scrolledUnderElevation: 0.5,
        surfaceTintColor: Colors.transparent,
        centerTitle: false,
        titleTextStyle: textTheme.titleLarge,
        systemOverlayStyle: isDark ? SystemUiOverlayStyle.light : SystemUiOverlayStyle.dark,
      ),

      cardTheme: CardThemeData(
        color: scheme.surface,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(
          borderRadius: AppRadius.card,
          side: BorderSide(color: scheme.outline),
        ),
      ),

      dividerTheme: DividerThemeData(color: scheme.outline, thickness: 1, space: 1),

      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: isDark ? AppColors.darkSurfaceAlt : AppColors.surface,
        contentPadding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.lg),
        border: OutlineInputBorder(borderRadius: AppRadius.field, borderSide: BorderSide(color: scheme.outline)),
        enabledBorder: OutlineInputBorder(borderRadius: AppRadius.field, borderSide: BorderSide(color: scheme.outline)),
        focusedBorder: OutlineInputBorder(
          borderRadius: AppRadius.field,
          borderSide: BorderSide(color: scheme.onSurface, width: 1.6),
        ),
        errorBorder: const OutlineInputBorder(
          borderRadius: AppRadius.field,
          borderSide: BorderSide(color: AppColors.danger, width: 1.2),
        ),
        focusedErrorBorder: const OutlineInputBorder(
          borderRadius: AppRadius.field,
          borderSide: BorderSide(color: AppColors.danger, width: 1.6),
        ),
        labelStyle: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant),
        floatingLabelStyle: textTheme.bodyMedium?.copyWith(color: scheme.onSurface),
        hintStyle: textTheme.bodyMedium?.copyWith(color: scheme.onSurfaceVariant.withValues(alpha: 0.7)),
        errorStyle: textTheme.bodySmall?.copyWith(color: AppColors.danger),
        prefixIconColor: scheme.onSurfaceVariant,
        suffixIconColor: scheme.onSurfaceVariant,
      ),

      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          minimumSize: const Size.fromHeight(52),
          backgroundColor: scheme.primary,
          foregroundColor: scheme.onPrimary,
          disabledBackgroundColor: scheme.primary.withValues(alpha: 0.4),
          disabledForegroundColor: Colors.white70,
          textStyle: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
          shape: const RoundedRectangleBorder(borderRadius: AppRadius.field),
        ),
      ),

      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          minimumSize: const Size.fromHeight(52),
          foregroundColor: scheme.onSurface,
          side: BorderSide(color: scheme.outlineVariant),
          textStyle: textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
          shape: const RoundedRectangleBorder(borderRadius: AppRadius.field),
        ),
      ),

      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: isDark ? const Color(0xFFFF7A45) : AppColors.orangeText,
          textStyle: textTheme.labelLarge?.copyWith(fontWeight: FontWeight.w700),
        ),
      ),

      floatingActionButtonTheme: FloatingActionButtonThemeData(
        backgroundColor: scheme.inverseSurface,
        foregroundColor: scheme.onInverseSurface,
        elevation: 2,
        shape: const RoundedRectangleBorder(borderRadius: AppRadius.card),
      ),

      chipTheme: ChipThemeData(
        backgroundColor: scheme.surface,
        selectedColor: scheme.inverseSurface,
        secondarySelectedColor: scheme.inverseSurface,
        checkmarkColor: scheme.onInverseSurface,
        side: BorderSide(color: scheme.outline),
        labelStyle: textTheme.labelMedium!,
        secondaryLabelStyle: textTheme.labelMedium!.copyWith(color: scheme.onInverseSurface),
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.xs),
        shape: const RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(AppRadius.pill))),
      ),

      bottomSheetTheme: BottomSheetThemeData(
        backgroundColor: scheme.surface,
        surfaceTintColor: Colors.transparent,
        shape: const RoundedRectangleBorder(borderRadius: AppRadius.sheet),
        showDragHandle: true,
        dragHandleColor: scheme.outlineVariant,
      ),

      dialogTheme: DialogThemeData(
        backgroundColor: scheme.surface,
        surfaceTintColor: Colors.transparent,
        shape: const RoundedRectangleBorder(borderRadius: AppRadius.card),
        titleTextStyle: textTheme.titleMedium,
        contentTextStyle: textTheme.bodyMedium,
      ),

      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: scheme.inverseSurface,
        contentTextStyle: textTheme.bodyMedium?.copyWith(color: scheme.onInverseSurface),
        shape: const RoundedRectangleBorder(borderRadius: AppRadius.field),
        insetPadding: const EdgeInsets.all(AppSpacing.lg),
      ),

      listTileTheme: ListTileThemeData(
        iconColor: scheme.onSurfaceVariant,
        titleTextStyle: textTheme.bodyLarge,
        subtitleTextStyle: textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
        contentPadding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.xs),
      ),

      progressIndicatorTheme: ProgressIndicatorThemeData(
        color: scheme.primary,
        linearTrackColor: scheme.outline,
        circularTrackColor: Colors.transparent,
      ),

      segmentedButtonTheme: SegmentedButtonThemeData(
        style: SegmentedButton.styleFrom(
          selectedBackgroundColor: scheme.inverseSurface,
          selectedForegroundColor: scheme.onInverseSurface,
          side: BorderSide(color: scheme.outline),
          textStyle: textTheme.labelLarge,
        ),
      ),

      pageTransitionsTheme: const PageTransitionsTheme(
        builders: {
          TargetPlatform.android: CupertinoPageTransitionsBuilder(),
          TargetPlatform.iOS: CupertinoPageTransitionsBuilder(),
        },
      ),
    );
  }

  /// Skala tipografi: Bricolage Grotesque untuk judul (berkarakter, sedikit
  /// rapat), Plus Jakarta Sans untuk teks.
  ///
  /// `decoration: TextDecoration.none` diterapkan ke seluruh gaya agar garis
  /// bawah kuning bawaan Flutter (teks tanpa induk Material) tidak muncul.
  static TextTheme _textTheme(ColorScheme scheme) {
    final base = ThemeData(brightness: scheme.brightness).textTheme;

    final body = pakaiGoogleFonts ? GoogleFonts.plusJakartaSansTextTheme(base) : base;

    TextStyle? judul(TextStyle? s, {double? size, FontWeight weight = FontWeight.w700, double spacing = -0.3}) {
      final dasar = s?.copyWith(fontSize: size, fontWeight: weight, letterSpacing: spacing);

      if (!pakaiGoogleFonts || dasar == null) return dasar;

      return GoogleFonts.bricolageGrotesque(textStyle: dasar);
    }

    return body
        .copyWith(
          displaySmall: judul(body.displaySmall, weight: FontWeight.w800, spacing: -1)?.copyWith(height: 1.08),
          headlineLarge: judul(body.headlineLarge, weight: FontWeight.w800, spacing: -0.8)?.copyWith(height: 1.1),
          headlineMedium: judul(body.headlineMedium, weight: FontWeight.w800, spacing: -0.6)?.copyWith(height: 1.15),
          headlineSmall: judul(body.headlineSmall, weight: FontWeight.w700)?.copyWith(height: 1.2),
          titleLarge: judul(body.titleLarge, size: 21, weight: FontWeight.w700)?.copyWith(height: 1.25),
          titleMedium: body.titleMedium?.copyWith(fontWeight: FontWeight.w700, fontSize: 16),
          titleSmall: body.titleSmall?.copyWith(fontWeight: FontWeight.w600, fontSize: 14),
          bodyLarge: body.bodyLarge?.copyWith(fontSize: 15, height: 1.5),
          bodyMedium: body.bodyMedium?.copyWith(fontSize: 14, height: 1.5),
          bodySmall: body.bodySmall?.copyWith(fontSize: 12.5, height: 1.4),
          labelLarge: body.labelLarge?.copyWith(fontWeight: FontWeight.w600, letterSpacing: 0.1),
          labelMedium: body.labelMedium?.copyWith(fontWeight: FontWeight.w600, fontSize: 12),
          labelSmall: body.labelSmall?.copyWith(fontWeight: FontWeight.w600, fontSize: 11, letterSpacing: 0.4),
        )
        .apply(
          bodyColor: scheme.onSurface,
          displayColor: scheme.onSurface,
          decoration: TextDecoration.none,
          decorationColor: Colors.transparent,
        );
  }

  /// Gaya angka & data: JetBrains Mono dengan digit tabular.
  ///
  /// Harga rupiah, pelat nomor, dan durasi tampil rata dan tidak "bergoyang"
  /// saat angkanya beranimasi, karena setiap digit selebar sama.
  static TextStyle money(BuildContext context, {double? fontSize, FontWeight? weight, Color? color}) {
    final style = TextStyle(
      fontSize: fontSize ?? 15,
      fontWeight: weight ?? FontWeight.w700,
      color: color ?? Theme.of(context).colorScheme.onSurface,
      decoration: TextDecoration.none,
      decorationColor: Colors.transparent,
      fontFeatures: const [FontFeature.tabularFigures()],
      letterSpacing: -0.3,
      height: 1.25,
    );

    return pakaiGoogleFonts ? GoogleFonts.jetBrainsMono(textStyle: style) : style;
  }
}
