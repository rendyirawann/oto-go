import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/utils/json.dart';
import '../domain/catalog.dart';

/// Data master publik (tanpa login). Server meng-cache-nya, jadi aman
/// dipanggil ulang saat layar dibuka.
class CatalogRepository {
  const CatalogRepository(this._api);

  final ApiClient _api;

  Future<List<VehicleType>> types() async =>
      J.list(await _api.get('/vehicle-types')).map(VehicleType.fromJson).toList();

  Future<List<VehicleCategory>> categories({String? type}) async =>
      J.list(await _api.get('/vehicle-categories', query: {'type': type}))
          .map(VehicleCategory.fromJson)
          .toList();

  Future<List<VehicleBrand>> brands({String? type}) async =>
      J.list(await _api.get('/vehicle-brands', query: {'type': type})).map(VehicleBrand.fromJson).toList();

  Future<List<VehicleModel>> models({required String brandId, String? q}) async =>
      J.list(await _api.get('/vehicle-models', query: {'brand_id': brandId, 'q': q}))
          .map(VehicleModel.fromJson)
          .toList();

  Future<List<ServiceArea>> serviceAreas() async =>
      J.list(await _api.get('/service-areas')).map(ServiceArea.fromJson).toList();

  Future<PriceEstimates> priceEstimates({required String vehicleCategory, String? module}) async {
    final envelope = await _api.getEnvelope(
      '/price-estimates',
      query: {'vehicle_category': vehicleCategory, 'module': module},
    );

    return PriceEstimates.fromEnvelope(envelope);
  }
}

final catalogRepositoryProvider = Provider<CatalogRepository>(
  (ref) => CatalogRepository(ref.watch(apiClientProvider)),
);

// ------------------------------------------------------------------ providers
//
// keepAlive bawaan FutureProvider: data master jarang berubah, jadi sekali
// dimuat dipakai bersama oleh beranda dan formulir kendaraan.

final vehicleTypesProvider = FutureProvider<List<VehicleType>>(
  (ref) => ref.watch(catalogRepositoryProvider).types(),
);

/// Semua kategori (type = null) atau per tipe `motor` / `mobil`.
final vehicleCategoriesProvider = FutureProvider.family<List<VehicleCategory>, String?>(
  (ref, type) => ref.watch(catalogRepositoryProvider).categories(type: type),
);

final vehicleBrandsProvider = FutureProvider.family<List<VehicleBrand>, String?>(
  (ref, type) => ref.watch(catalogRepositoryProvider).brands(type: type),
);

final vehicleModelsProvider = FutureProvider.family<List<VehicleModel>, String>(
  (ref, brandId) => ref.watch(catalogRepositoryProvider).models(brandId: brandId),
);

final serviceAreasProvider = FutureProvider<List<ServiceArea>>(
  (ref) => ref.watch(catalogRepositoryProvider).serviceAreas(),
);

/// Kunci estimasi: (kode kategori, modul).
typedef KunciEstimasi = ({String kategori, String modul});

final priceEstimatesProvider = FutureProvider.autoDispose.family<PriceEstimates, KunciEstimasi>(
  (ref, kunci) => ref
      .watch(catalogRepositoryProvider)
      .priceEstimates(vehicleCategory: kunci.kategori, module: kunci.modul),
);
