import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_config.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../../../core/widgets/feedback.dart';
import '../../auth/presentation/auth_controller.dart';

/// Mengubah alamat API tanpa membangun ulang aplikasi.
///
/// Berguna saat server pindah, saat menguji ke server lokal dari perangkat
/// fisik, atau saat satu APK dipakai untuk beberapa lingkungan.
class AlamatApiSheet extends ConsumerStatefulWidget {
  const AlamatApiSheet({super.key});

  static Future<void> tampil(BuildContext context) {
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (context) => const AlamatApiSheet(),
    );
  }

  @override
  ConsumerState<AlamatApiSheet> createState() => _AlamatApiSheetState();
}

class _AlamatApiSheetState extends ConsumerState<AlamatApiSheet> {
  late final TextEditingController _url =
      TextEditingController(text: AppConfig.baseUrl);

  String? _galat;
  String? _hasilUji;
  bool _ujiBerhasil = false;
  bool _menguji = false;
  bool _menyimpan = false;

  @override
  void dispose() {
    _url.dispose();
    super.dispose();
  }

  /// Memanggil `/api/health` pada alamat yang diketik.
  ///
  /// Dipakai sebelum menyimpan supaya salah ketik ketahuan di sini, bukan
  /// nanti sebagai "gagal masuk" yang tidak jelas sebabnya.
  Future<void> _uji() async {
    final galat = AppConfig.galatAlamat(_url.text);

    if (galat != null) {
      setState(() {
        _galat = galat;
        _hasilUji = null;
        _ujiBerhasil = false;
      });

      return;
    }

    final alamat = AppConfig.normalkan(_url.text);

    setState(() {
      _galat = null;
      _menguji = true;
      _hasilUji = null;
      _ujiBerhasil = false;
    });

    final hasil = await ref.read(apiClientProvider).cekAlamat(alamat);

    if (!mounted) return;

    setState(() {
      _menguji = false;
      _ujiBerhasil = hasil.berhasil;
      _hasilUji = hasil.pesan;
    });
  }

  Future<void> _simpan() async {
    final galat = AppConfig.galatAlamat(_url.text);

    if (galat != null) {
      setState(() => _galat = galat);

      return;
    }

    final alamat = AppConfig.normalkan(_url.text);

    if (alamat == AppConfig.baseUrl) {
      Navigator.of(context).pop();

      return;
    }

    setState(() => _menyimpan = true);

    // Token yang dipegang aplikasi diterbitkan server LAMA; server baru pasti
    // menolaknya. Sesinya dibersihkan supaya pengguna langsung diminta masuk
    // ulang, bukan menemui 401 yang membingungkan di layar pertama.
    await ref.read(authControllerProvider.notifier).gantiAlamatApi(alamat);

    if (!mounted) return;

    Navigator.of(context).pop();
    AppSnack.sukses(context, 'Alamat API diubah. Silakan masuk kembali.');
  }

  Future<void> _kembalikanBawaan() async {
    setState(() => _menyimpan = true);

    await ref.read(authControllerProvider.notifier).gantiAlamatApi(null);

    if (!mounted) return;

    Navigator.of(context).pop();
    AppSnack.info(context, 'Alamat dikembalikan ke ${AppConfig.baseUrlBawaan}.');
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final insets = MediaQuery.viewInsetsOf(context).bottom;
    final sibuk = _menguji || _menyimpan;

    return Padding(
      padding: EdgeInsets.only(bottom: insets),
      child: DraggableScrollableSheet(
        initialChildSize: 0.8,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scrollController) => Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(
                AppSpacing.xl,
                AppSpacing.lg,
                AppSpacing.sm,
                AppSpacing.sm,
              ),
              child: Row(
                children: [
                  Expanded(
                    child: Text('Alamat API', style: theme.textTheme.titleMedium),
                  ),
                  IconButton(
                    onPressed: sibuk ? null : () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.close_rounded),
                    tooltip: 'Tutup',
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: ListView(
                controller: scrollController,
                padding: const EdgeInsets.all(AppSpacing.xl),
                children: [
                  AppTextField(
                    controller: _url,
                    label: 'Alamat Server',
                    hint: 'http://192.168.1.10:8822',
                    helper: 'Tanpa /api di belakang - ditambahkan otomatis.',
                    prefixIcon: Icons.dns_outlined,
                    keyboardType: TextInputType.url,
                    enabled: !sibuk,
                    errorText: _galat,
                    onChanged: (_) => setState(() {
                      _galat = null;
                      _hasilUji = null;
                      _ujiBerhasil = false;
                    }),
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  AppButton(
                    label: 'Uji Koneksi',
                    icon: Icons.wifi_tethering_rounded,
                    variant: AppButtonVariant.outline,
                    loading: _menguji,
                    loadingLabel: 'Menghubungi...',
                    onPressed: sibuk ? null : _uji,
                  ),
                  if (_hasilUji != null) ...[
                    const SizedBox(height: AppSpacing.lg),
                    Container(
                      padding: const EdgeInsets.all(AppSpacing.lg),
                      decoration: BoxDecoration(
                        color: _ujiBerhasil
                            ? AppColors.successSoft
                            : AppColors.dangerSoft,
                        borderRadius: BorderRadius.circular(AppRadius.md),
                      ),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(
                            _ujiBerhasil
                                ? Icons.check_circle_outline_rounded
                                : Icons.error_outline_rounded,
                            size: 18,
                            color: _ujiBerhasil
                                ? AppColors.success
                                : AppColors.danger,
                          ),
                          const SizedBox(width: AppSpacing.md),
                          Expanded(
                            child: Text(
                              _hasilUji!,
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: _ujiBerhasil
                                    ? AppColors.success
                                    : AppColors.danger,
                                height: 1.45,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                  const SizedBox(height: AppSpacing.xl),
                  const Divider(height: 1),
                  const SizedBox(height: AppSpacing.lg),
                  Text(
                    'Alamat bawaan',
                    style: theme.textTheme.labelLarge
                        ?.copyWith(color: scheme.onSurfaceVariant),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    AppConfig.baseUrlBawaan,
                    style: theme.textTheme.bodyMedium,
                  ),
                  if (AppConfig.pakaiAlamatKustom) ...[
                    const SizedBox(height: AppSpacing.md),
                    AppButton(
                      label: 'Kembalikan ke Bawaan',
                      icon: Icons.restart_alt_rounded,
                      variant: AppButtonVariant.ghost,
                      onPressed: sibuk ? null : _kembalikanBawaan,
                    ),
                  ],
                  const SizedBox(height: AppSpacing.lg),
                  Container(
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    decoration: BoxDecoration(
                      color: AppColors.warningSoft,
                      borderRadius: BorderRadius.circular(AppRadius.md),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Icon(
                          Icons.info_outline_rounded,
                          size: 17,
                          color: AppColors.warning,
                        ),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                          child: Text(
                            'Mengubah alamat akan mengeluarkan Anda dari sesi '
                            'ini, karena token yang dipegang aplikasi hanya '
                            'berlaku di server sebelumnya.',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: AppColors.warning,
                              height: 1.45,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.all(AppSpacing.xl),
                child: AppButton(
                  label: 'Simpan Alamat',
                  icon: Icons.save_outlined,
                  loading: _menyimpan,
                  loadingLabel: 'Menyimpan...',
                  onPressed: sibuk ? null : _simpan,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
