import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../auth/presentation/auth_controller.dart';
import '../data/vehicle_repository.dart';
import '../domain/vehicle.dart';

/// Daftar kendaraan pengguna. Perubahan (tambah/ubah/hapus) langsung
/// diterapkan ke state dari balasan server, tanpa memuat ulang semuanya.
class VehiclesController extends AsyncNotifier<List<Vehicle>> {
  VehicleRepository get _repo => ref.read(vehicleRepositoryProvider);

  @override
  Future<List<Vehicle>> build() {
    // Bergantung pada identitas pengguna: ganti akun = daftar baru.
    ref.watch(authControllerProvider.select((s) => s.user?.id));

    return _repo.list();
  }

  Future<void> muatUlang() async {
    state = const AsyncLoading<List<Vehicle>>().copyWithPrevious(state);
    state = await AsyncValue.guard(_repo.list);
  }

  Future<Vehicle> tambah(VehicleInput input) async {
    final baru = await _repo.create(input);

    state = AsyncData([baru, ...state.valueOrNull ?? const []]);

    return baru;
  }

  Future<Vehicle> ubah(String id, VehicleInput input) async {
    final hasil = await _repo.update(id, input);

    state = AsyncData([
      for (final v in state.valueOrNull ?? const <Vehicle>[]) v.id == id ? hasil : v,
    ]);

    return hasil;
  }

  Future<void> hapus(String id) async {
    await _repo.delete(id);

    state = AsyncData([
      for (final v in state.valueOrNull ?? const <Vehicle>[])
        if (v.id != id) v,
    ]);
  }
}

final vehiclesProvider = AsyncNotifierProvider<VehiclesController, List<Vehicle>>(VehiclesController.new);

/// Kendaraan utama = yang paling awal didaftarkan. Tidak bergantung pada
/// urutan dari server; bila tanggal tidak ada, ambil yang pertama.
final primaryVehicleProvider = Provider<Vehicle?>((ref) {
  final list = ref.watch(vehiclesProvider).valueOrNull;

  if (list == null || list.isEmpty) return null;

  final bertanggal = list.where((v) => v.createdAt != null).toList()
    ..sort((a, b) => a.createdAt!.compareTo(b.createdAt!));

  return bertanggal.isNotEmpty ? bertanggal.first : list.first;
});
