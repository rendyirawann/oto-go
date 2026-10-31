import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/utils/json.dart';
import '../domain/vehicle.dart';

class VehicleRepository {
  const VehicleRepository(this._api);

  final ApiClient _api;

  Future<List<Vehicle>> list() async => J.list(await _api.get('/me/vehicles')).map(Vehicle.fromJson).toList();

  Future<Vehicle> create(VehicleInput input) async =>
      Vehicle.fromJson(J.map(await _api.post('/me/vehicles', body: input.toJson())));

  Future<Vehicle> update(String id, VehicleInput input) async =>
      Vehicle.fromJson(J.map(await _api.put('/me/vehicles/$id', body: input.toJson())));

  Future<void> delete(String id) => _api.delete('/me/vehicles/$id');
}

final vehicleRepositoryProvider = Provider<VehicleRepository>(
  (ref) => VehicleRepository(ref.watch(apiClientProvider)),
);
