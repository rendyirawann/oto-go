import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../../../core/widgets/feedback.dart';
import 'auth_controller.dart';
import 'widgets/auth_scaffold.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();

  @override
  void initState() {
    super.initState();

    // Isi email terakhir agar pengguna cukup mengetik password.
    final tersimpan = ref.read(authControllerProvider).savedEmail;

    if (tersimpan != null) _email.text = tersimpan;
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _masuk() async {
    FocusScope.of(context).unfocus();

    final email = _email.text.trim();

    if (email.isEmpty || _password.text.isEmpty) {
      AppSnack.peringatan(context, 'Email dan password wajib diisi.');

      return;
    }

    await ref.read(authControllerProvider.notifier).login(email: email, password: _password.text);
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(authControllerProvider);
    final theme = Theme.of(context);

    return AuthScaffold(
      judul: 'Masuk',
      subjudul: 'Pantau kendaraan, cek kisaran harga servis, dan tanya asisten OtoGo.',
      form: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          AppTextField(
            controller: _email,
            label: 'Email',
            hint: 'nama@email.com',
            prefixIcon: Icons.alternate_email_rounded,
            keyboardType: TextInputType.emailAddress,
            textInputAction: TextInputAction.next,
            errorText: state.fieldErrors['email'],
            enabled: !state.sedangProses,
            onChanged: (_) => _bersihkan(state),
          ),
          const SizedBox(height: AppSpacing.lg),
          AppTextField(
            controller: _password,
            label: 'Password',
            hint: 'Masukkan password',
            prefixIcon: Icons.lock_outline_rounded,
            obscure: true,
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => _masuk(),
            errorText: state.fieldErrors['password'],
            enabled: !state.sedangProses,
            onChanged: (_) => _bersihkan(state),
          ),
          // Pesan yang sudah tampil di bawah kolomnya tidak diulang di sini.
          if (state.error != null && !state.fieldErrors.values.contains(state.error)) ...[
            const SizedBox(height: AppSpacing.lg),
            PeringatanForm(pesan: state.error!),
          ],
          const SizedBox(height: AppSpacing.xl),
          AppButton(
            label: 'Masuk',
            icon: Icons.arrow_forward_rounded,
            loading: state.sedangProses,
            loadingLabel: 'Memeriksa...',
            onPressed: _masuk,
          ),
          const SizedBox(height: AppSpacing.lg),
          // Wrap, bukan Row: di layar sempit / font besar tombolnya turun ke
          // baris berikut alih-alih terpotong.
          Wrap(
            alignment: WrapAlignment.center,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              Text('Belum punya akun?', style: theme.textTheme.bodyMedium?.copyWith(color: theme.colorScheme.onSurfaceVariant)),
              TextButton(
                onPressed: state.sedangProses
                    ? null
                    : () {
                        ref.read(authControllerProvider.notifier).bersihkanError();
                        context.go('/daftar');
                      },
                child: const Text('Daftar gratis'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  void _bersihkan(AuthState state) {
    if (state.error != null || state.fieldErrors.isNotEmpty) {
      ref.read(authControllerProvider.notifier).bersihkanError();
    }
  }
}

/// Kotak galat di dalam form, bergetar halus saat muncul.
class PeringatanForm extends StatelessWidget {
  const PeringatanForm({super.key, required this.pesan});

  final String pesan;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Container(
      padding: const EdgeInsets.all(AppSpacing.md),
      decoration: BoxDecoration(
        color: scheme.errorContainer,
        borderRadius: AppRadius.field,
        border: Border.all(color: AppColors.danger.withValues(alpha: 0.3)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Icon(Icons.error_outline_rounded, size: 18, color: AppColors.danger),
          const SizedBox(width: AppSpacing.sm),
          Expanded(
            child: Text(
              pesan,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(color: scheme.onErrorContainer),
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 250.ms).shake(hz: 3, offset: const Offset(1.5, 0));
  }
}
