import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

/// Pemformat angka & tanggal gaya Indonesia.
class Fmt {
  const Fmt._();

  static final NumberFormat _ribuan = NumberFormat.decimalPattern('id_ID');
  static final NumberFormat _desimal = NumberFormat('#,##0.##', 'id_ID');

  /// 400000000 -> "Rp400.000.000"  |  -1000 -> "-Rp1.000"
  static String rupiah(num? value, {bool prefix = true}) {
    final n = (value ?? 0).round();
    final sign = n < 0 ? '-' : '';

    return '$sign${prefix ? 'Rp' : ''}${_ribuan.format(n.abs())}';
  }

  /// Bentuk pendek untuk grafik & kartu sempit.
  /// 1625000000 -> "Rp1,63 M"  |  400000000 -> "Rp400 jt"  |  9500 -> "Rp9.500"
  static String rupiahSingkat(num? value, {bool prefix = true}) {
    final n = (value ?? 0).round();
    final abs = n.abs();
    final sign = n < 0 ? '-' : '';
    final p = prefix ? 'Rp' : '';

    if (abs >= 1000000000000) return '$sign$p${_desimal.format(abs / 1000000000000)} T';
    if (abs >= 1000000000) return '$sign$p${_desimal.format(abs / 1000000000)} M';
    if (abs >= 1000000) return '$sign$p${_desimal.format(abs / 1000000)} jt';
    if (abs >= 1000) return '$sign$p${_desimal.format(abs / 1000)} rb';

    return '$sign$p${_ribuan.format(abs)}';
  }

  /// 11 -> "11%"  |  1.75 -> "1,75%"
  static String persen(num? value, {int maxDecimals = 2}) {
    final v = (value ?? 0).toDouble();
    final f = NumberFormat.decimalPatternDigits(locale: 'id_ID', decimalDigits: maxDecimals);
    var s = f.format(v);

    // buang nol di belakang: "1,750" -> "1,75", "11,00" -> "11"
    if (s.contains(',')) {
      s = s.replaceAll(RegExp(r'0+$'), '');
      s = s.replaceAll(RegExp(r',$'), '');
    }

    return '$s%';
  }

  static String angka(num? value) => _ribuan.format((value ?? 0).round());

  /// 45 -> "45 mnt"  |  90 -> "1 jam 30 mnt"  |  120 -> "2 jam"
  static String durasi(int? menit) {
    final m = menit ?? 0;

    if (m <= 0) return '-';
    if (m < 60) return '$m mnt';

    final jam = m ~/ 60;
    final sisa = m % 60;

    return sisa == 0 ? '$jam jam' : '$jam jam $sisa mnt';
  }

  /// Merapikan nomor polisi: "bk1234abc" -> "BK 1234 ABC".
  ///
  /// Format pelat Indonesia: 1-2 huruf kode wilayah, 1-4 angka, 0-3 huruf
  /// seri. Teks yang tidak cocok dikembalikan dalam huruf besar apa adanya.
  static String plat(String? raw) {
    final s = (raw ?? '').toUpperCase().replaceAll(RegExp(r'[^A-Z0-9]'), '');
    final m = RegExp(r'^([A-Z]{1,2})(\d{1,4})([A-Z]{0,3})$').firstMatch(s);

    if (m == null) return (raw ?? '').toUpperCase().trim().replaceAll(RegExp(r'\s+'), ' ');

    return [m.group(1), m.group(2), m.group(3)].where((g) => g != null && g.isNotEmpty).join(' ');
  }

  /// Sapaan menurut jam setempat.
  static String sapaan([DateTime? waktu]) {
    final jam = (waktu ?? DateTime.now()).hour;

    if (jam < 11) return 'Selamat pagi';
    if (jam < 15) return 'Selamat siang';
    if (jam < 19) return 'Selamat sore';

    return 'Selamat malam';
  }

  // ---------------------------------------------------------------- tanggal
  static final DateFormat _tglPanjang = DateFormat('d MMMM y', 'id_ID');
  static final DateFormat _tglPendek = DateFormat('d MMM y', 'id_ID');
  static final DateFormat _tglNumerik = DateFormat('dd/MM/yyyy', 'id_ID');
  static final DateFormat _bulanTahun = DateFormat('MMMM y', 'id_ID');
  static final DateFormat _iso = DateFormat('yyyy-MM-dd');

  static String tanggal(DateTime? d) => d == null ? '-' : _tglPanjang.format(d);

  static String tanggalPendek(DateTime? d) => d == null ? '-' : _tglPendek.format(d);

  static String tanggalNumerik(DateTime? d) => d == null ? '-' : _tglNumerik.format(d);

  static String bulanTahun(DateTime? d) => d == null ? '-' : _bulanTahun.format(d);

  static String iso(DateTime? d) => d == null ? '' : _iso.format(d);

  static DateTime? parseTanggal(String? s) {
    if (s == null || s.isEmpty) return null;

    return DateTime.tryParse(s);
  }

  /// "3 hari lagi", "2 hari lalu", "hari ini"
  static String relatif(DateTime? d) {
    if (d == null) return '-';

    final now = DateTime.now();
    final hari = DateTime(d.year, d.month, d.day)
        .difference(DateTime(now.year, now.month, now.day))
        .inDays;

    if (hari == 0) return 'hari ini';
    if (hari == 1) return 'besok';
    if (hari == -1) return 'kemarin';
    if (hari > 0) return '$hari hari lagi';

    return '${hari.abs()} hari lalu';
  }
}

/// Formatter input rupiah: pengguna mengetik angka, pemisah ribuan muncul
/// otomatis. Nilai mentahnya diambil lewat [RupiahInputFormatter.nilai].
class RupiahInputFormatter extends TextInputFormatter {
  RupiahInputFormatter({this.maksimal = 999999999999999});

  final int maksimal;

  static final NumberFormat _f = NumberFormat.decimalPattern('id_ID');

  /// Ambil nilai numerik dari teks yang sudah diformat.
  static int nilai(String text) {
    final digits = text.replaceAll(RegExp(r'[^0-9]'), '');

    return digits.isEmpty ? 0 : int.parse(digits);
  }

  static String format(int value) => value == 0 ? '' : _f.format(value);

  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    if (newValue.text.isEmpty) return newValue;

    final digits = newValue.text.replaceAll(RegExp(r'[^0-9]'), '');

    if (digits.isEmpty) {
      return const TextEditingValue(text: '');
    }

    var angka = int.tryParse(digits);

    if (angka == null) return oldValue;
    if (angka > maksimal) return oldValue;

    final teks = _f.format(angka);

    return TextEditingValue(
      text: teks,
      // kursor selalu di akhir: input uang praktis selalu diketik dari kanan
      selection: TextSelection.collapsed(offset: teks.length),
    );
  }
}

/// Formatter input persen: maksimal 100, maksimal 3 angka desimal, koma atau
/// titik sama-sama diterima lalu dinormalkan jadi titik untuk parsing.
class PersenInputFormatter extends TextInputFormatter {
  const PersenInputFormatter();

  static double nilai(String text) {
    final bersih = text.replaceAll(',', '.').trim();

    return double.tryParse(bersih) ?? 0;
  }

  static String format(num value) {
    // Nol berarti "belum diisi", jadi kolomnya dibiarkan kosong -- sama
    // seperti RupiahInputFormatter. Menampilkan "0" membuat isian baru
    // terlihat seolah persentasenya sudah ditentukan seseorang, padahal
    // justru itu yang harus diisi pengguna.
    if (value == 0) return '';

    var s = value.toStringAsFixed(3);
    s = s.replaceAll(RegExp(r'0+$'), '');
    s = s.replaceAll(RegExp(r'\.$'), '');

    return s.replaceAll('.', ',');
  }

  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    final text = newValue.text;

    if (text.isEmpty) return newValue;
    if (!RegExp(r'^[0-9]{0,3}([.,][0-9]{0,3})?$').hasMatch(text)) return oldValue;

    final v = double.tryParse(text.replaceAll(',', '.'));

    if (v != null && v > 100) return oldValue;

    return newValue;
  }
}
