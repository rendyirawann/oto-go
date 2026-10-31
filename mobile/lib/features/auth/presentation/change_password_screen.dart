import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../../../core/widgets/common.dart';
import '../../../core/widgets/feedback.dart';
import 'auth_controller.dart';
import 'register_screen.dart';

class ChangePasswordScreen extends ConsumerStatefulWidget {
  const ChangePasswordScreen({super.key});

  @override
  ConsumerState<ChangePasswordScreen> createState() => _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends ConsumerState<ChangePasswordScreen> {
  final _lama = TextEditingController();
  final _baru = TextEditingController();
  final _konfirmasi = TextEditingController();

  @override
  void initState() {
    super.initState();
    _baru.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _lama.dispose();
    _baru.dispose();
    _konfirmasi.dispose();
    super.dispose();
  }

  bool get _cukupPanjang => _baru.text.length >= 8;
  bool get _adaHuruf => RegExp('[A-Za-z]').hasMatch(_baru.text);
  bool get _adaAngka => RegExp('[0-9]').hasMatch(_baru.text);

  Future<void> _simpan() async {
    FocusScope.of(context).unfocus();

    if (_lama.text.isEmpty) {
      AppSnack.peringatan(context, 'Password saat ini wajib diisi.');

      return;
    }

    if (!(_cukupPanjang && _adaHuruf && _adaAngka)) {
      AppSnack.peringatan(context, 'Password baru belum memenuhi semua syarat.');

      return;
    }

    if (_baru.text != _konfirmasi.text) {
      AppSnack.peringatan(context, 'Konfirmasi password tidak cocok.');

      return;
    }

    final galat = await ref.read(authControllerProvider.notifier).gantiPassword(
          passwordSaatIni: _lama.text,
          passwordBaru: _baru.text,
          konfirmasi: _konfirmasi.text,
        );

    if (!mounted) return;

    if (galat == null) {
      context.pop();
      AppSnack.sukses(context, 'Password berhasil diganti.');
    } else if (galat.fieldErrors.isEmpty) {
      AppSnack.galat(context, galat.message);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Ganti Password')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xxxl),
        children: [
          SectionCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                AppTextField(
                  controller: _lama,
                  label: 'Password saat ini',
                  obscure: true,
                  wajib: true,
                  prefixIcon: Icons.lock_outline_rounded,
                  errorText: auth.fieldErrors['current_password'],
                  enabled: !auth.sedangProses,
                ),
                const SizedBox(height: AppSpacing.xl),
                AppTextField(
                  controller: _baru,
                  label: 'Password baru',
                  obscure: true,
                  wajib: true,
                  prefixIcon: Icons.lock_reset_rounded,
                  errorText: auth.fieldErrors['password'],
                  enabled: !auth.sedangProses,
                ),
                const SizedBox(height: AppSpacing.sm),
                SyaratPassword(cukupPanjang: _cukupPanjang, adaHuruf: _adaHuruf, adaAngka: _adaAngka),
                const SizedBox(height: AppSpacing.xl),
                AppTextField(
                  controller: _konfirmasi,
                  label: 'Ulangi password baru',
                  obscure: true,
                  wajib: true,
                  prefixIcon: Icons.check_circle_outline_rounded,
                  errorText: auth.fieldErrors['password_confirmation'],
                  enabled: !auth.sedangProses,
                  onSubmitted: (_) => _simpan(),
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.xl),
          AppButton(
            label: 'Simpan Password',
            icon: Icons.check_rounded,
            loading: auth.sedangProses,
            loadingLabel: 'Menyimpan...',
            onPressed: _simpan,
          ),
        ],
      ),
    );
  }
}
