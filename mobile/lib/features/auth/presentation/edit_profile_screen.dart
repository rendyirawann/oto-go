import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../../../core/widgets/common.dart';
import '../../../core/widgets/feedback.dart';
import 'auth_controller.dart';

class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({super.key});

  @override
  ConsumerState<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  late final TextEditingController _nama;
  late final TextEditingController _hp;

  @override
  void initState() {
    super.initState();

    final user = ref.read(authControllerProvider).user;

    _nama = TextEditingController(text: user?.name ?? '');
    _hp = TextEditingController(text: user?.phone ?? '');
  }

  @override
  void dispose() {
    _nama.dispose();
    _hp.dispose();
    super.dispose();
  }

  Future<void> _simpan() async {
    FocusScope.of(context).unfocus();

    if (_nama.text.trim().length < 2) {
      AppSnack.peringatan(context, 'Nama minimal 2 huruf.');

      return;
    }

    final galat = await ref.read(authControllerProvider.notifier).simpanProfil(name: _nama.text, phone: _hp.text);

    if (!mounted) return;

    if (galat == null) {
      context.pop();
      AppSnack.sukses(context, 'Profil diperbarui.');
    } else if (galat.fieldErrors.isEmpty) {
      AppSnack.galat(context, galat.message);
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Ubah Profil')),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xxxl),
        children: [
          SectionCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                AppTextField(
                  controller: _nama,
                  label: 'Nama lengkap',
                  prefixIcon: Icons.person_outline_rounded,
                  wajib: true,
                  errorText: auth.fieldErrors['name'],
                  enabled: !auth.sedangProses,
                ),
                const SizedBox(height: AppSpacing.xl),
                AppTextField(
                  controller: _hp,
                  label: 'Nomor HP / WhatsApp',
                  hint: '08xxxxxxxxxx',
                  prefixIcon: Icons.phone_iphone_rounded,
                  keyboardType: TextInputType.phone,
                  inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9+]'))],
                  errorText: auth.fieldErrors['phone'],
                  enabled: !auth.sedangProses,
                  onSubmitted: (_) => _simpan(),
                ),
                const SizedBox(height: AppSpacing.lg),
                InfoRow(label: 'Email', value: auth.user?.email ?? '-', icon: Icons.alternate_email_rounded),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.xl),
          AppButton(
            label: 'Simpan',
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
