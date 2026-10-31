import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/app_button.dart';
import '../../../core/widgets/app_text_field.dart';
import '../../../core/widgets/feedback.dart';
import '../../../core/widgets/plat_nomor.dart';
import '../../catalog/data/catalog_repository.dart';
import '../../catalog/domain/catalog.dart';
import '../domain/vehicle.dart';
import 'vehicle_providers.dart';

/// Lembar tambah / ubah kendaraan.
///
/// Urutannya mengikuti cara orang mengenali kendaraannya: jenis (motor /
/// mobil) -> merek & model (opsional) -> kategori tarif. Memilih model
/// mengisi kategori secara otomatis, tetapi kategori tetap bisa dikoreksi.
class VehicleFormSheet extends ConsumerStatefulWidget {
  const VehicleFormSheet({super.key, this.awal});

  final Vehicle? awal;

  static Future<void> tampil(BuildContext context, {Vehicle? awal}) {
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (context) => VehicleFormSheet(awal: awal),
    );
  }

  @override
  ConsumerState<VehicleFormSheet> createState() => _VehicleFormSheetState();
}

class _VehicleFormSheetState extends ConsumerState<VehicleFormSheet> {
  late String _tipe;
  String? _kategoriId;
  String? _merekId;
  String? _modelId;

  late final TextEditingController _plat;
  late final TextEditingController _tahun;
  late final TextEditingController _warna;
  late final TextEditingController _panggilan;

  bool _menyimpan = false;
  Map<String, String> _galat = const {};

  bool get _ubah => widget.awal != null;

  @override
  void initState() {
    super.initState();

    final v = widget.awal;

    _tipe = (v?.isMotor ?? false) ? 'motor' : 'mobil';
    _kategoriId = v?.category?.id;
    _modelId = v?.modelId;
    _plat = TextEditingController(text: v == null ? '' : Fmt.plat(v.plateNumber))
      ..addListener(() => setState(() {}));
    _tahun = TextEditingController(text: v?.year?.toString() ?? '');
    _warna = TextEditingController(text: v?.color ?? '');
    _panggilan = TextEditingController(text: v?.nickname ?? '');
  }

  @override
  void dispose() {
    _plat.dispose();
    _tahun.dispose();
    _warna.dispose();
    _panggilan.dispose();
    super.dispose();
  }

  void _gantiTipe(String tipe) {
    if (tipe == _tipe) return;

    setState(() {
      _tipe = tipe;
      _kategoriId = null;
      _merekId = null;
      _modelId = null;
    });
  }

  void _pilihModel(VehicleModel? model, List<VehicleCategory> kategori) {
    setState(() {
      _modelId = model?.id;

      final bawaan = model?.defaultCategory;

      if (bawaan != null && kategori.any((k) => k.id == bawaan.id)) {
        _kategoriId = bawaan.id;
      }
    });
  }

  Map<String, String> _periksa() {
    final g = <String, String>{};
    final plat = _plat.text.replaceAll(RegExp(r'[^A-Za-z0-9]'), '');

    if (_kategoriId == null) g['vehicle_category_id'] = 'Pilih kategori kendaraan.';

    if (!RegExp(r'^[A-Za-z]{1,2}[0-9]{1,4}[A-Za-z]{0,3}$').hasMatch(plat)) {
      g['plate_number'] = 'Format pelat belum benar, mis. BK 1234 ABC.';
    }

    final tahun = _tahun.text.trim();

    if (tahun.isNotEmpty) {
      final t = int.tryParse(tahun);
      final maks = DateTime.now().year + 1;

      if (t == null || t < 1970 || t > maks) g['year'] = 'Tahun antara 1970 dan $maks.';
    }

    return g;
  }

  Future<void> _simpan() async {
    FocusScope.of(context).unfocus();

    final galat = _periksa();

    setState(() => _galat = galat);

    if (galat.isNotEmpty) return;

    final input = VehicleInput(
      vehicleCategoryId: _kategoriId!,
      vehicleModelId: _modelId,
      plateNumber: Fmt.plat(_plat.text),
      year: int.tryParse(_tahun.text.trim()),
      color: _warna.text,
      nickname: _panggilan.text,
    );

    setState(() => _menyimpan = true);

    try {
      final ctrl = ref.read(vehiclesProvider.notifier);

      if (_ubah) {
        await ctrl.ubah(widget.awal!.id, input);
      } else {
        await ctrl.tambah(input);
      }

      if (!mounted) return;

      Navigator.of(context).pop();
      AppSnack.sukses(context, _ubah ? 'Data kendaraan diperbarui.' : 'Kendaraan ditambahkan ke garasi.');
    } on ApiException catch (e) {
      if (!mounted) return;

      setState(() {
        _menyimpan = false;
        _galat = e.fieldErrors;
      });

      if (e.fieldErrors.isEmpty) AppSnack.galat(context, e.message);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final insets = MediaQuery.viewInsetsOf(context).bottom;

    final kategoriAsync = ref.watch(vehicleCategoriesProvider(_tipe));
    final merekAsync = ref.watch(vehicleBrandsProvider(_tipe));
    final modelAsync = _merekId == null ? null : ref.watch(vehicleModelsProvider(_merekId!));
    final kategori = kategoriAsync.valueOrNull ?? const <VehicleCategory>[];

    return Padding(
      padding: EdgeInsets.only(bottom: insets),
      child: DraggableScrollableSheet(
        initialChildSize: 0.9,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (context, scroll) => Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(AppSpacing.xl, 0, AppSpacing.sm, AppSpacing.sm),
              child: Row(
                children: [
                  Expanded(
                    child: Text(_ubah ? 'Ubah kendaraan' : 'Tambah kendaraan', style: theme.textTheme.titleLarge),
                  ),
                  IconButton(
                    onPressed: _menyimpan ? null : () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.close_rounded),
                    tooltip: 'Tutup',
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            Expanded(
              child: ListView(
                controller: scroll,
                padding: const EdgeInsets.all(AppSpacing.xl),
                children: [
                  // Pratinjau pelat
                  Center(child: PlatNomor(_plat.text.isEmpty ? 'BK 0000 XX' : _plat.text, ukuran: 1.4)),
                  const SizedBox(height: AppSpacing.xl),

                  SegmentedButton<String>(
                    showSelectedIcon: false,
                    segments: const [
                      ButtonSegment(value: 'motor', label: Text('Motor'), icon: Icon(Icons.two_wheeler_rounded, size: 18)),
                      ButtonSegment(value: 'mobil', label: Text('Mobil'), icon: Icon(Icons.directions_car_outlined, size: 18)),
                    ],
                    selected: {_tipe},
                    onSelectionChanged: _menyimpan ? null : (s) => _gantiTipe(s.first),
                  ),
                  const SizedBox(height: AppSpacing.xl),

                  AppTextField(
                    controller: _plat,
                    label: 'Nomor polisi',
                    hint: 'BK 1234 ABC',
                    prefixIcon: Icons.badge_outlined,
                    wajib: true,
                    enabled: !_menyimpan,
                    errorText: _galat['plate_number'],
                    inputFormatters: [
                      FilteringTextInputFormatter.allow(RegExp(r'[A-Za-z0-9 ]')),
                      _HurufBesar(),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.lg),

                  // ------------------------------------------- merek & model
                  _Pilihan<String>(
                    label: 'Merek (opsional)',
                    ikon: Icons.factory_outlined,
                    memuat: merekAsync.isLoading,
                    galatMuat: merekAsync.hasError,
                    onCobaLagi: () => ref.invalidate(vehicleBrandsProvider(_tipe)),
                    nilai: _merekId,
                    opsi: [
                      for (final m in merekAsync.valueOrNull ?? const <VehicleBrand>[]) (nilai: m.id, label: m.name),
                    ],
                    onChanged: _menyimpan
                        ? null
                        : (v) => setState(() {
                              _merekId = v;
                              _modelId = null;
                            }),
                  ),
                  if (_merekId != null) ...[
                    const SizedBox(height: AppSpacing.lg),
                    _Pilihan<String>(
                      label: 'Model (opsional)',
                      ikon: Icons.directions_car_filled_outlined,
                      memuat: modelAsync?.isLoading ?? false,
                      galatMuat: modelAsync?.hasError ?? false,
                      onCobaLagi: () => ref.invalidate(vehicleModelsProvider(_merekId!)),
                      nilai: _modelId,
                      opsi: [
                        for (final m in modelAsync?.valueOrNull ?? const <VehicleModel>[]) (nilai: m.id, label: m.name),
                      ],
                      onChanged: _menyimpan
                          ? null
                          : (v) => _pilihModel(
                                (modelAsync?.valueOrNull ?? const <VehicleModel>[])
                                    .where((m) => m.id == v)
                                    .firstOrNull,
                                kategori,
                              ),
                    ),
                  ],
                  const SizedBox(height: AppSpacing.lg),

                  _Pilihan<String>(
                    label: 'Kategori tarif',
                    ikon: Icons.category_outlined,
                    wajib: true,
                    memuat: kategoriAsync.isLoading,
                    galatMuat: kategoriAsync.hasError,
                    onCobaLagi: () => ref.invalidate(vehicleCategoriesProvider(_tipe)),
                    galat: _galat['vehicle_category_id'],
                    nilai: _kategoriId,
                    opsi: [for (final k in kategori) (nilai: k.id, label: k.name)],
                    onChanged: _menyimpan ? null : (v) => setState(() => _kategoriId = v),
                    bantuan: 'Menentukan tarif servis & cuci. Pilih model untuk mengisinya otomatis.',
                  ),
                  const SizedBox(height: AppSpacing.lg),

                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: AppTextField(
                          controller: _tahun,
                          label: 'Tahun',
                          hint: '2019',
                          keyboardType: TextInputType.number,
                          inputFormatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(4)],
                          enabled: !_menyimpan,
                          errorText: _galat['year'],
                        ),
                      ),
                      const SizedBox(width: AppSpacing.md),
                      Expanded(
                        child: AppTextField(
                          controller: _warna,
                          label: 'Warna',
                          hint: 'Putih',
                          enabled: !_menyimpan,
                          errorText: _galat['color'],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  AppTextField(
                    controller: _panggilan,
                    label: 'Nama panggilan',
                    hint: 'mis. Mobil kantor',
                    prefixIcon: Icons.label_outline_rounded,
                    enabled: !_menyimpan,
                    errorText: _galat['nickname'],
                  ),
                  const SizedBox(height: AppSpacing.md),
                  Text(
                    'Data kendaraan hanya dipakai untuk menghitung tarif dan dibagikan '
                    'ke bengkel saat Anda melakukan booking.',
                    style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                  ),
                ],
              ),
            ),
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.all(AppSpacing.xl),
                child: AppButton(
                  label: _ubah ? 'Simpan perubahan' : 'Tambah ke garasi',
                  icon: Icons.check_rounded,
                  loading: _menyimpan,
                  loadingLabel: 'Menyimpan...',
                  onPressed: _simpan,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Dropdown dengan label di luar kotak, keadaan memuat, dan coba lagi.
class _Pilihan<T> extends StatelessWidget {
  const _Pilihan({
    required this.label,
    required this.opsi,
    required this.nilai,
    required this.onChanged,
    this.ikon,
    this.wajib = false,
    this.memuat = false,
    this.galatMuat = false,
    this.onCobaLagi,
    this.galat,
    this.bantuan,
  });

  final String label;
  final List<({T nilai, String label})> opsi;
  final T? nilai;
  final ValueChanged<T?>? onChanged;
  final IconData? ikon;
  final bool wajib;
  final bool memuat;
  final bool galatMuat;
  final VoidCallback? onCobaLagi;
  final String? galat;
  final String? bantuan;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final ada = opsi.any((o) => o.nilai == nilai);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text.rich(
          TextSpan(
            text: label,
            children: [
              if (wajib) TextSpan(text: ' *', style: TextStyle(color: theme.colorScheme.error)),
            ],
          ),
          style: theme.textTheme.labelLarge,
        ),
        const SizedBox(height: AppSpacing.sm),
        if (galatMuat)
          OutlinedButton.icon(
            onPressed: onCobaLagi,
            icon: const Icon(Icons.refresh_rounded, size: 18),
            label: const Text('Gagal memuat - coba lagi'),
          )
        else
          DropdownButtonFormField<T>(
            // Nilai yang tidak ada di daftar (mis. daftar masih dimuat)
            // dikosongkan agar dropdown tidak gagal assert.
            initialValue: ada ? nilai : null,
            key: ValueKey('$label-${ada ? nilai : null}-${opsi.length}'),
            isExpanded: true,
            items: [
              for (final o in opsi)
                DropdownMenuItem<T>(value: o.nilai, child: Text(o.label, overflow: TextOverflow.ellipsis)),
            ],
            onChanged: memuat ? null : onChanged,
            hint: Text(memuat ? 'Memuat...' : 'Pilih'),
            decoration: InputDecoration(
              prefixIcon: ikon == null ? null : Icon(ikon, size: 20),
              errorText: galat,
              helperText: bantuan,
              helperMaxLines: 2,
            ),
          ),
      ],
    );
  }
}

/// Mengubah ketikan menjadi huruf besar (nomor polisi).
class _HurufBesar extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) =>
      newValue.copyWith(text: newValue.text.toUpperCase());
}
