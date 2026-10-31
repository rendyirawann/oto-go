/// Pembaca JSON yang toleran.
///
/// Angka dari PostgreSQL bisa sampai sebagai int, double, atau string
/// (tergantung driver dan cast), jadi setiap pembacaan dinormalkan di sini
/// alih-alih menebak tipenya di setiap model.
class J {
  const J._();

  static Map<String, dynamic> map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return value.cast<String, dynamic>();

    return <String, dynamic>{};
  }

  static List<Map<String, dynamic>> list(dynamic value) {
    if (value is! List) return const [];

    return value.map(map).toList();
  }

  static String str(dynamic value, [String fallback = '']) {
    if (value == null) return fallback;

    return value.toString();
  }

  static String? strOrNull(dynamic value) {
    if (value == null) return null;

    final s = value.toString().trim();

    return s.isEmpty ? null : s;
  }

  static int int_(dynamic value, [int fallback = 0]) {
    if (value == null) return fallback;
    if (value is int) return value;
    if (value is num) return value.round();

    return int.tryParse(value.toString()) ?? double.tryParse(value.toString())?.round() ?? fallback;
  }

  static int? intOrNull(dynamic value) {
    if (value == null) return null;
    if (value is int) return value;
    if (value is num) return value.round();

    final s = value.toString();

    return int.tryParse(s) ?? double.tryParse(s)?.round();
  }

  static double dbl(dynamic value, [double fallback = 0]) {
    if (value == null) return fallback;
    if (value is double) return value;
    if (value is num) return value.toDouble();

    return double.tryParse(value.toString()) ?? fallback;
  }

  static bool bool_(dynamic value, [bool fallback = false]) {
    if (value == null) return fallback;
    if (value is bool) return value;
    if (value is num) return value != 0;

    final s = value.toString().toLowerCase();

    return s == 'true' || s == '1' || s == 'yes';
  }

  static DateTime? date(dynamic value) {
    final s = strOrNull(value);

    return s == null ? null : DateTime.tryParse(s);
  }
}
