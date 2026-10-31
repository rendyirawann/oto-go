import '../../../core/utils/json.dart';

/// Pengguna yang sedang masuk (`GET /me`).
class AppUser {
  const AppUser({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.avatarUrl,
    this.roles = const [],
    this.createdAt,
  });

  /// UUID dari server -- disimpan sebagai teks.
  final String id;
  final String name;
  final String email;
  final String? phone;
  final String? avatarUrl;
  final List<String> roles;
  final DateTime? createdAt;

  /// Nama depan untuk sapaan di beranda.
  String get namaDepan {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty);

    return parts.isEmpty ? 'Pengguna' : parts.first;
  }

  /// Inisial untuk avatar, maksimal dua huruf.
  String get initials {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();

    if (parts.isEmpty) return '?';
    if (parts.length == 1) return parts.first.substring(0, 1).toUpperCase();

    return '${parts.first.substring(0, 1)}${parts.last.substring(0, 1)}'.toUpperCase();
  }

  bool get isCustomer => roles.isEmpty || roles.contains('customer');

  factory AppUser.fromJson(Map<String, dynamic> json) => AppUser(
        id: J.str(json['id']),
        name: J.str(json['name'], 'Pengguna'),
        email: J.str(json['email']),
        phone: J.strOrNull(json['phone']),
        avatarUrl: J.strOrNull(json['avatar_url']),
        roles: (json['roles'] as List?)?.map((e) => e.toString()).toList() ?? const [],
        createdAt: J.date(json['created_at']),
      );

  AppUser copyWith({String? name, String? phone}) => AppUser(
        id: id,
        name: name ?? this.name,
        email: email,
        phone: phone ?? this.phone,
        avatarUrl: avatarUrl,
        roles: roles,
        createdAt: createdAt,
      );
}

/// Payload hasil login / daftar: `{token, token_type, user}`.
class AuthSession {
  const AuthSession({required this.token, required this.user, this.tokenType = 'Bearer'});

  final String token;
  final String tokenType;
  final AppUser user;

  factory AuthSession.fromJson(Map<String, dynamic> json) => AuthSession(
        token: J.str(json['token']),
        tokenType: J.str(json['token_type'], 'Bearer'),
        user: AppUser.fromJson(J.map(json['user'])),
      );
}
