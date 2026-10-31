import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/utils/json.dart';
import '../domain/user.dart';

/// Data pendaftaran akun pelanggan.
class DaftarInput {
  const DaftarInput({
    required this.name,
    required this.email,
    required this.password,
    required this.konfirmasi,
    this.phone,
  });

  final String name;
  final String email;
  final String? phone;
  final String password;
  final String konfirmasi;
}

class AuthRepository {
  const AuthRepository(this._api);

  final ApiClient _api;

  Future<AuthSession> login({
    required String email,
    required String password,
    required String deviceName,
  }) async {
    final data = await _api.post('/auth/login', body: {
      'email': email.trim(),
      'password': password,
      'device_name': deviceName,
    });

    return AuthSession.fromJson(J.map(data));
  }

  Future<AuthSession> daftar(DaftarInput input, {required String deviceName}) async {
    final phone = input.phone?.trim();
    final data = await _api.post('/auth/register', body: {
      'name': input.name.trim(),
      'email': input.email.trim(),
      if (phone != null && phone.isNotEmpty) 'phone': phone,
      'password': input.password,
      'password_confirmation': input.konfirmasi,
      'device_name': deviceName,
    });

    return AuthSession.fromJson(J.map(data));
  }

  Future<AppUser> profil() async => AppUser.fromJson(J.map(await _api.get('/me')));

  Future<AppUser> updateProfil({required String name, String? phone}) async {
    final data = await _api.put('/me', body: {
      'name': name.trim(),
      'phone': (phone ?? '').trim().isEmpty ? null : phone!.trim(),
    });

    return AppUser.fromJson(J.map(data));
  }

  Future<void> gantiPassword({
    required String passwordSaatIni,
    required String passwordBaru,
    required String konfirmasi,
  }) =>
      _api.put('/me/password', body: {
        'current_password': passwordSaatIni,
        'password': passwordBaru,
        'password_confirmation': konfirmasi,
      });

  Future<void> logout() => _api.post('/auth/logout');
}

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(ref.watch(apiClientProvider)),
);
