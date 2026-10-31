import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../../../core/widgets/feedback.dart';
import '../data/auth_repository.dart';
import 'auth_controller.dart';
import 'login_screen.dart';
import 'widgets/auth_scaffold.dart';

class RegisterScreen extends ConsumerStatefulWidget {
  const RegisterScreen({super.key});

  @override
  ConsumerState<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends ConsumerState<RegisterScreen> {
  final _nama = TextEditingController();
  final _email = TextEditingController();
  final _hp = TextEditingController();
  final _password = TextEditingController();
  final _konfirmasi = TextEditingController();

  /// Galat yang diperiksa di perangkat sebelum dikirim.
  Map<String, String> _lokal = const {};

  @override
  void initState() {
    super.initState();
    _password.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _nama.dispose();
    _email.dispose();
    _hp.dispose();
    _password.dispose();
    _konfirmasi.dispose();
    super.dispose();
  }

  // Mengikuti aturan server: minimal 8 karakter, ada huruf dan angka.
  bool get _cukupPanjang => _password.text.length >= 8;
  bool get _adaHuruf => RegExp('[A-Za-z]').hasMatch(_password.text);
  bool get _adaAngka => RegExp('[0-9]').hasMatch(_password.text);

  Map<String, String> _periksa() {
    final galat = <String, String>{};
    final email = _email.text.trim();

    if (_nama.text.trim().length < 2) galat['name'] = 'Isi nama lengkap Anda.';
    if (!RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(email)) galat['email'] = 'Format email belum benar.';

    final hp = _hp.text.trim();

    if (hp.isNotEmpty && !RegExp(r'^(\+?62|0)8[0-9]{7,12}$').hasMatch(hp)) {
      galat['phone'] = 'Nomor HP diawali 08 atau +628, 10-14 digit.';
    }

    if (!(_cukupPanjang && _adaHuruf && _adaAngka)) galat['password'] = 'Password belum memenuhi syarat.';
    if (_konfirmasi.text != _password.text) galat['password_confirmation'] = 'Konfirmasi password tidak cocok.';

    return galat;
  }

  Future<void> _daftar() async {
    FocusScope.of(context).unfocus();

    final galat = _periksa();

    setState(() => _lokal = galat);

    if (galat.isNotEmpty) {
      AppSnack.peringatan(context, 'Periksa kembali isian yang ditandai.');

      return;
    }

    await ref.read(authControllerProvider.notifier).daftar(
          DaftarInput(
            name: _nama.text,
            email: _email.text,
            phone: _hp.text,
            password: _password.text,
            konfirmasi: _konfirmasi.text,
          ),
        );
  }

  String? _galat(AuthState state, String field) => _lokal[field] ?? state.fieldErrors[field];

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(authControllerProvider);
    final theme = Theme.of(context);
    final sibuk = state.sedangProses;

    return AuthScaffold(
      judul: 'Buat akun',
      subjudul: 'Gratis. Simpan data kendaraan sekali, pakai untuk semua layanan.',
      leading: Padding(
        padding: const EdgeInsets.only(right: AppSpacing.sm),
        child: IconButton(
          onPressed: sibuk ? null : () => context.go('/login'),
          icon: const Icon(Icons.arrow_back_rounded, color: AppColors.background),
          tooltip: 'Kembali ke halaman masuk',
        ),
      ),
      form: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AppTextField(
            controller: _nama,
            label: 'Nama lengkap',
            hint: 'mis. Rina Siregar',
            prefixIcon: Icons.person_outline_rounded,
            textInputAction: TextInputAction.next,
            errorText: _galat(state, 'name'),
            enabled: !sibuk,
            wajib: true,
          ),
          const SizedBox(height: AppSpacing.lg),
          AppTextField(
            controller: _email,
            label: 'Email',
            hint: 'nama@email.com',
            prefixIcon: Icons.alternate_email_rounded,
            keyboardType: TextInputType.emailAddress,
            textInputAction: TextInputAction.next,
            errorText: _galat(state, 'email'),
            enabled: !sibuk,
            wajib: true,
          ),
          const SizedBox(height: AppSpacing.lg),
          AppTextField(
            controller: _hp,
            label: 'Nomor HP / WhatsApp',
            hint: '08xxxxxxxxxx',
            helper: 'Opsional. Dipakai bengkel untuk konfirmasi jadwal.',
            prefixIcon: Icons.phone_iphone_rounded,
            keyboardType: TextInputType.phone,
            inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9+]'))],
            textInputAction: TextInputAction.next,
            errorText: _galat(state, 'phone'),
            enabled: !sibuk,
          ),
          const SizedBox(height: AppSpacing.lg),
          AppTextField(
            controller: _password,
            label: 'Password',
            prefixIcon: Icons.lock_outline_rounded,
            obscure: true,
            textInputAction: TextInputAction.next,
            errorText: _galat(state, 'password'),
            enabled: !sibuk,
            wajib: true,
          ),
          const SizedBox(height: AppSpacing.sm),
          SyaratPassword(cukupPanjang: _cukupPanjang, adaHuruf: _adaHuruf, adaAngka: _adaAngka),
          const SizedBox(height: AppSpacing.lg),
          AppTextField(
            controller: _konfirmasi,
            label: 'Ulangi password',
            prefixIcon: Icons.check_circle_outline_rounded,
            obscure: true,
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => _daftar(),
            errorText: _galat(state, 'password_confirmation'),
            enabled: !sibuk,
            wajib: true,
          ),
          if (state.error != null && state.fieldErrors.isEmpty) ...[
            const SizedBox(height: AppSpacing.lg),
            PeringatanForm(pesan: state.error!),
          ],
          const SizedBox(height: AppSpacing.xl),
          AppButton(
            label: 'Daftar & Masuk',
            icon: Icons.arrow_forward_rounded,
            loading: sibuk,
            loadingLabel: 'Membuat akun...',
            onPressed: _daftar,
          ),
          const SizedBox(height: AppSpacing.md),
          Text(
            'Dengan mendaftar, Anda menyetujui syarat layanan dan kebijakan privasi OtoGo.',
            textAlign: TextAlign.center,
            style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.onSurfaceVariant),
          ),
        ],
      ),
    );
  }
}

/// Daftar syarat password yang tercentang saat terpenuhi.
class SyaratPassword extends StatelessWidget {
  const SyaratPassword({super.key, required this.cukupPanjang, required this.adaHuruf, required this.adaAngka});

  final bool cukupPanjang;
  final bool adaHuruf;
  final bool adaAngka;

  @override
  Widget build(BuildContext context) {
    Widget baris(bool ok, String teks) {
      final warna = ok ? AppColors.success : Theme.of(context).colorScheme.onSurfaceVariant;

      return Padding(
        padding: const EdgeInsets.only(top: 2),
        child: Row(
          children: [
            AnimatedSwitcher(
              duration: const Duration(milliseconds: 200),
              child: Icon(
                ok ? Icons.check_circle_rounded : Icons.radio_button_unchecked_rounded,
                key: ValueKey(ok),
                size: 15,
                color: warna,
              ),
            ),
            const SizedBox(width: 6),
            Flexible(child: Text(teks, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: warna))),
          ],
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        baris(cukupPanjang, 'Minimal 8 karakter'),
        baris(adaHuruf, 'Mengandung huruf'),
        baris(adaAngka, 'Mengandung angka'),
      ],
    );
  }
}
