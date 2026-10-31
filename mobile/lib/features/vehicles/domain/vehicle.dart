import '../../../core/utils/json.dart';
import '../../catalog/domain/catalog.dart';

/// Kendaraan milik pelanggan (`/me/vehicles`).
class Vehicle {
  const Vehicle({
    required this.id,
    required this.plateNumber,
    this.nickname,
    this.year,
    this.color,
    this.category,
    this.modelId,
    this.modelName,
    this.brandName,
    this.createdAt,
  });

  final String id;
  final String plateNumber;
  final String? nickname;
  final int? year;
  final String? color;
  final VehicleCategory? category;
  final String? modelId;
  final String? modelName;
  final String? brandName;
  final DateTime? createdAt;

  bool get isMotor => category?.isMotor ?? false;

  /// "Toyota Avanza" / kategori bila model tidak diisi.
  String get judul {
    final model = [brandName, modelName].whereType<String>().where((s) => s.isNotEmpty).join(' ');

    if (model.isNotEmpty) return model;

    return category?.namaPendek ?? 'Kendaraan';
  }

  /// Baris keterangan: "MPV · 2019 · Putih".
  String get keterangan => [
        if (category != null && judul != category!.namaPendek) category!.namaPendek,
        if (year != null) '$year',
        if (color != null && color!.isNotEmpty) color!,
      ].join(' · ');

  factory Vehicle.fromJson(Map<String, dynamic> json) {
    final cat = json['category'];
    final model = json['model'];
    final m = model is Map ? J.map(model) : const <String, dynamic>{};

    return Vehicle(
      id: J.str(json['id']),
      plateNumber: J.str(json['plate_number']),
      nickname: J.strOrNull(json['nickname']),
      year: J.intOrNull(json['year']),
      color: J.strOrNull(json['color']),
      category: cat is Map ? VehicleCategory.fromJson(J.map(cat)) : null,
      modelId: J.strOrNull(m['id']),
      modelName: J.strOrNull(m['name']),
      brandName: J.strOrNull(m['brand_name']),
      createdAt: J.date(json['created_at']),
    );
  }
}

/// Isi formulir tambah / ubah kendaraan.
class VehicleInput {
  const VehicleInput({
    required this.vehicleCategoryId,
    required this.plateNumber,
    this.vehicleModelId,
    this.year,
    this.color,
    this.nickname,
  });

  final String vehicleCategoryId;
  final String? vehicleModelId;
  final String plateNumber;
  final int? year;
  final String? color;
  final String? nickname;

  Map<String, dynamic> toJson() => {
        'vehicle_category_id': _id(vehicleCategoryId),
        'vehicle_model_id': vehicleModelId == null ? null : _id(vehicleModelId!),
        'plate_number': plateNumber,
        'year': year,
        'color': _kosongJadiNull(color),
        'nickname': _kosongJadiNull(nickname),
      };

  /// ID dikirim sebagai angka bila memang angka, supaya validasi `integer`
  /// di server lolos; selain itu (ULID/UUID) dikirim apa adanya.
  static Object _id(String id) => int.tryParse(id) ?? id;

  static String? _kosongJadiNull(String? s) {
    final t = s?.trim();

    return (t == null || t.isEmpty) ? null : t;
  }
}
