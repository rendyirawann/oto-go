import '../../../core/utils/json.dart';

/// `motor` | `mobil`
class VehicleType {
  const VehicleType({required this.id, required this.code, required this.name});

  final String id;
  final String code;
  final String name;

  factory VehicleType.fromJson(Map<String, dynamic> json) => VehicleType(
        id: J.str(json['id']),
        code: J.str(json['code']),
        name: J.str(json['name'], J.str(json['code'])),
      );
}

/// Kategori kendaraan penentu tarif, mis. `car_mpv` "MPV (Avanza, Xenia, ...)".
class VehicleCategory {
  const VehicleCategory({
    required this.id,
    required this.code,
    required this.name,
    this.vehicleType,
  });

  final String id;
  final String code;
  final String name;

  /// `motor` | `mobil` (null bila server tidak menyertakannya).
  final String? vehicleType;

  bool get isMotor => vehicleType == 'motor' || code.startsWith('motor_');

  /// Nama tanpa keterangan dalam kurung: "MPV (Avanza, ...)" -> "MPV".
  String get namaPendek {
    final i = name.indexOf('(');

    return (i > 0 ? name.substring(0, i) : name).trim();
  }

  factory VehicleCategory.fromJson(Map<String, dynamic> json) => VehicleCategory(
        id: J.str(json['id']),
        code: J.str(json['code']),
        name: J.str(json['name'], J.str(json['code'])),
        vehicleType: J.strOrNull(json['vehicle_type']),
      );

  @override
  bool operator ==(Object other) => other is VehicleCategory && other.id == id;

  @override
  int get hashCode => id.hashCode;
}

class VehicleBrand {
  const VehicleBrand({required this.id, required this.name, this.vehicleType});

  final String id;
  final String name;
  final String? vehicleType;

  factory VehicleBrand.fromJson(Map<String, dynamic> json) => VehicleBrand(
        id: J.str(json['id']),
        name: J.str(json['name']),
        vehicleType: J.strOrNull(json['vehicle_type']),
      );
}

class VehicleModel {
  const VehicleModel({
    required this.id,
    required this.name,
    this.brandId,
    this.brandName,
    this.defaultCategory,
  });

  final String id;
  final String name;
  final String? brandId;
  final String? brandName;
  final VehicleCategory? defaultCategory;

  String get namaLengkap => [brandName, name].whereType<String>().where((s) => s.isNotEmpty).join(' ');

  factory VehicleModel.fromJson(Map<String, dynamic> json) {
    final cat = json['default_category'];

    return VehicleModel(
      id: J.str(json['id']),
      name: J.str(json['name']),
      brandId: J.strOrNull(json['brand_id']),
      brandName: J.strOrNull(json['brand_name']),
      defaultCategory: cat is Map ? VehicleCategory.fromJson(J.map(cat)) : null,
    );
  }
}

class ServiceArea {
  const ServiceArea({required this.id, required this.city, required this.district});

  final String id;
  final String city;
  final String district;

  factory ServiceArea.fromJson(Map<String, dynamic> json) => ServiceArea(
        id: J.str(json['id']),
        city: J.str(json['city'], 'Medan'),
        district: J.str(json['district']),
      );
}

/// Kode modul usaha.
class Modul {
  const Modul._();

  static const String workshop = 'workshop';
  static const String carwash = 'carwash';
  static const String sparepart = 'sparepart';

  static String label(String code) => switch (code) {
        workshop => 'Bengkel',
        carwash => 'Doorsmeer',
        sparepart => 'Sparepart',
        _ => code,
      };
}

/// Satu baris estimasi harga dari template tarif pasar Medan.
class PriceEstimate {
  const PriceEstimate({
    required this.module,
    required this.group,
    required this.service,
    required this.price,
    this.vehicleCategoryCode,
    this.durationMinutes,
    this.isAddon = false,
  });

  final String module;
  final String group;
  final String service;
  final int price;
  final String? vehicleCategoryCode;
  final int? durationMinutes;
  final bool isAddon;

  factory PriceEstimate.fromJson(Map<String, dynamic> json) => PriceEstimate(
        module: J.str(json['module']),
        group: J.str(json['group'], 'Lainnya'),
        service: J.str(json['service']),
        price: J.int_(json['price']),
        vehicleCategoryCode: J.strOrNull(json['vehicle_category_code']),
        durationMinutes: J.intOrNull(json['duration_minutes']),
        isAddon: J.bool_(json['is_addon']),
      );
}

class PriceEstimates {
  const PriceEstimates({required this.items, this.template, this.disclaimer});

  final List<PriceEstimate> items;
  final String? template;
  final String? disclaimer;

  /// Dikelompokkan per `group`, urutan kemunculan dipertahankan.
  Map<String, List<PriceEstimate>> get perKelompok {
    final out = <String, List<PriceEstimate>>{};

    for (final item in items) {
      out.putIfAbsent(item.group, () => []).add(item);
    }

    return out;
  }

  factory PriceEstimates.fromEnvelope(Map<String, dynamic> envelope) {
    final meta = J.map(envelope['meta']);

    return PriceEstimates(
      items: J.list(envelope['data']).map(PriceEstimate.fromJson).toList(),
      template: J.strOrNull(meta['template']),
      disclaimer: J.strOrNull(meta['disclaimer']),
    );
  }
}
