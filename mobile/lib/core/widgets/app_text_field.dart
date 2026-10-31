import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';

import '../theme/app_theme.dart';
import '../utils/formatters.dart';

/// Kolom input dengan label di luar kotak (lebih mudah dibaca di layar kecil
/// dibanding floating label) dan animasi halus saat fokus.
class AppTextField extends StatefulWidget {
  const AppTextField({
    super.key,
    required this.controller,
    required this.label,
    this.hint,
    this.helper,
    this.errorText,
    this.prefixIcon,
    this.suffix,
    this.obscure = false,
    this.keyboardType,
    this.inputFormatters,
    this.maxLines = 1,
    this.maxLength,
    this.enabled = true,
    this.readOnly = false,
    this.onTap,
    this.onChanged,
    this.onSubmitted,
    this.textInputAction,
    this.autofocus = false,
    this.wajib = false,
  });

  final TextEditingController controller;
  final String label;
  final String? hint;
  final String? helper;
  final String? errorText;
  final IconData? prefixIcon;
  final Widget? suffix;
  final bool obscure;
  final TextInputType? keyboardType;
  final List<TextInputFormatter>? inputFormatters;
  final int maxLines;
  final int? maxLength;
  final bool enabled;
  final bool readOnly;
  final VoidCallback? onTap;
  final ValueChanged<String>? onChanged;
  final ValueChanged<String>? onSubmitted;
  final TextInputAction? textInputAction;
  final bool autofocus;
  final bool wajib;

  @override
  State<AppTextField> createState() => _AppTextFieldState();
}

class _AppTextFieldState extends State<AppTextField> {
  late final FocusNode _focus = FocusNode()..addListener(_onFocus);

  bool _fokus = false;
  bool _tersembunyi = true;

  void _onFocus() => setState(() => _fokus = _focus.hasFocus);

  @override
  void dispose() {
    _focus
      ..removeListener(_onFocus)
      ..dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final adaError = widget.errorText != null && widget.errorText!.isNotEmpty;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Label ikut berubah warna saat fokus -- petunjuk halus posisi kursor.
        AnimatedDefaultTextStyle(
          duration: const Duration(milliseconds: 180),
          style: theme.textTheme.labelLarge!.copyWith(
            color: adaError
                ? scheme.error
                : _fokus
                    ? scheme.primary
                    : scheme.onSurfaceVariant,
          ),
          // Text.rich, bukan Row: label panjang turun baris di layar sempit
          // alih-alih meluap.
          child: Text.rich(
            TextSpan(
              text: widget.label,
              children: [
                if (widget.wajib)
                  TextSpan(text: ' *', style: TextStyle(color: scheme.error, fontWeight: FontWeight.w700)),
              ],
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.sm),
        TextField(
          controller: widget.controller,
          focusNode: _focus,
          obscureText: widget.obscure && _tersembunyi,
          enabled: widget.enabled,
          readOnly: widget.readOnly,
          onTap: widget.onTap,
          onChanged: widget.onChanged,
          onSubmitted: widget.onSubmitted,
          keyboardType: widget.keyboardType,
          inputFormatters: widget.inputFormatters,
          maxLines: widget.obscure ? 1 : widget.maxLines,
          maxLength: widget.maxLength,
          autofocus: widget.autofocus,
          textInputAction: widget.textInputAction,
          style: theme.textTheme.bodyLarge,
          cursorColor: scheme.primary,
          decoration: InputDecoration(
            hintText: widget.hint,
            errorText: adaError ? widget.errorText : null,
            helperText: widget.helper,
            helperMaxLines: 3,
            errorMaxLines: 3,
            counterText: '',
            prefixIcon: widget.prefixIcon == null
                ? null
                : Icon(widget.prefixIcon, size: 20)
                    .animate(target: _fokus ? 1 : 0)
                    .tint(color: scheme.primary, duration: 200.ms),
            suffixIcon: _suffix(scheme),
          ),
        ),
      ],
    );
  }

  Widget? _suffix(ColorScheme scheme) {
    if (widget.obscure) {
      return IconButton(
        onPressed: () => setState(() => _tersembunyi = !_tersembunyi),
        icon: Icon(
          _tersembunyi ? Icons.visibility_outlined : Icons.visibility_off_outlined,
          size: 20,
        ),
        tooltip: _tersembunyi ? 'Tampilkan password' : 'Sembunyikan password',
      );
    }

    return widget.suffix;
  }
}

/// Input rupiah. Nilai mentah dibaca lewat [nilai].
class RupiahField extends StatelessWidget {
  const RupiahField({
    super.key,
    required this.controller,
    required this.label,
    this.helper,
    this.errorText,
    this.enabled = true,
    this.onChanged,
    this.wajib = false,
    this.hint = '0',
  });

  final TextEditingController controller;
  final String label;
  final String? helper;
  final String? errorText;
  final bool enabled;
  final ValueChanged<int>? onChanged;
  final bool wajib;
  final String hint;

  static int nilai(TextEditingController c) => RupiahInputFormatter.nilai(c.text);

  static void setNilai(TextEditingController c, int value) =>
      c.text = RupiahInputFormatter.format(value);

  @override
  Widget build(BuildContext context) {
    return AppTextField(
      controller: controller,
      label: label,
      hint: hint,
      helper: helper,
      errorText: errorText,
      enabled: enabled,
      wajib: wajib,
      keyboardType: TextInputType.number,
      inputFormatters: [RupiahInputFormatter()],
      prefixIcon: Icons.payments_outlined,
      // widthFactor 1.0 WAJIB di sini. Tanpanya (mis. memakai Center),
      // penanda ini mengembang selebar sisa kolom dan area teks tersisa nol
      // piksel -- angka yang diketik tetap tersimpan tetapi tidak terlihat.
      suffix: Align(
        widthFactor: 1,
        child: Padding(
          padding: const EdgeInsets.only(right: AppSpacing.md),
          child: Text(
            'Rp',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
          ),
        ),
      ),
      onChanged: onChanged == null
          ? null
          : (text) => onChanged!(RupiahInputFormatter.nilai(text)),
    );
  }
}

/// Input persen 0-100 dengan maksimal 3 desimal.
class PersenField extends StatelessWidget {
  const PersenField({
    super.key,
    required this.controller,
    required this.label,
    this.helper,
    this.errorText,
    this.enabled = true,
    this.onChanged,
  });

  final TextEditingController controller;
  final String label;
  final String? helper;
  final String? errorText;
  final bool enabled;
  final ValueChanged<double>? onChanged;

  static double nilai(TextEditingController c) => PersenInputFormatter.nilai(c.text);

  static void setNilai(TextEditingController c, num value) =>
      c.text = PersenInputFormatter.format(value);

  @override
  Widget build(BuildContext context) {
    return AppTextField(
      controller: controller,
      label: label,
      hint: '0',
      helper: helper,
      errorText: errorText,
      enabled: enabled,
      keyboardType: const TextInputType.numberWithOptions(decimal: true),
      inputFormatters: const [PersenInputFormatter()],
      // Lihat catatan widthFactor pada RupiahField.
      suffix: Align(
        widthFactor: 1,
        child: Padding(
          padding: const EdgeInsets.only(right: AppSpacing.md),
          child: Text(
            '%',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
          ),
        ),
      ),
      onChanged: onChanged == null
          ? null
          : (text) => onChanged!(PersenInputFormatter.nilai(text)),
    );
  }
}

/// Pemilih tanggal yang tampil seperti kolom teks biasa.
class TanggalField extends StatelessWidget {
  const TanggalField({
    super.key,
    required this.label,
    required this.value,
    required this.onChanged,
    this.helper,
    this.errorText,
    this.wajib = false,
    this.pertama,
    this.terakhir,
    this.bolehKosong = true,
  });

  final String label;
  final DateTime? value;
  final ValueChanged<DateTime?> onChanged;
  final String? helper;
  final String? errorText;
  final bool wajib;
  final DateTime? pertama;
  final DateTime? terakhir;
  final bool bolehKosong;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final terisi = value != null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              label,
              style: Theme.of(context).textTheme.labelLarge?.copyWith(
                    color: scheme.onSurfaceVariant,
                  ),
            ),
            if (wajib)
              Text(' *', style: TextStyle(color: scheme.error, fontWeight: FontWeight.w700)),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        InkWell(
          onTap: () => _pilih(context),
          borderRadius: AppRadius.field,
          child: Container(
            height: 56,
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
            decoration: BoxDecoration(
              color: Theme.of(context).inputDecorationTheme.fillColor,
              borderRadius: AppRadius.field,
              border: Border.all(
                color: errorText != null ? scheme.error : scheme.outline,
              ),
            ),
            child: Row(
              children: [
                Icon(Icons.event_outlined, size: 20, color: scheme.onSurfaceVariant),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Text(
                    terisi ? Fmt.tanggal(value) : 'Pilih tanggal',
                    style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                          color: terisi ? scheme.onSurface : scheme.onSurfaceVariant,
                        ),
                  ),
                ),
                if (terisi && bolehKosong)
                  IconButton(
                    onPressed: () => onChanged(null),
                    icon: const Icon(Icons.close, size: 18),
                    tooltip: 'Kosongkan',
                    visualDensity: VisualDensity.compact,
                  ),
              ],
            ),
          ),
        ),
        if (errorText != null)
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.xs, left: AppSpacing.md),
            child: Text(
              errorText!,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: scheme.error),
            ),
          )
        else if (helper != null)
          Padding(
            padding: const EdgeInsets.only(top: AppSpacing.xs, left: AppSpacing.md),
            child: Text(
              helper!,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: scheme.onSurfaceVariant,
                  ),
            ),
          ),
      ],
    );
  }

  Future<void> _pilih(BuildContext context) async {
    final hasil = await showDatePicker(
      context: context,
      initialDate: value ?? DateTime.now(),
      firstDate: pertama ?? DateTime(2015),
      lastDate: terakhir ?? DateTime(DateTime.now().year + 10),
      locale: const Locale('id', 'ID'),
      helpText: label,
      cancelText: 'Batal',
      confirmText: 'Pilih',
    );

    if (hasil != null) onChanged(hasil);
  }
}
