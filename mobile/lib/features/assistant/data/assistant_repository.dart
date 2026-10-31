import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../domain/chat.dart';

class AssistantRepository {
  const AssistantRepository(this._api);

  final ApiClient _api;

  /// Server menerima maksimal 20 pesan, dan pesan terakhir harus dari
  /// pengguna. Riwayat dipangkas di sini supaya percakapan panjang tidak
  /// ditolak validasi.
  static const int maksRiwayat = 20;

  Future<BalasanAsisten> kirim(List<PesanChat> riwayat, {String? conversationId}) async {
    final bersih = riwayat.where((p) => !p.galat && p.isi.trim().isNotEmpty).toList();
    final potong = bersih.length > maksRiwayat ? bersih.sublist(bersih.length - maksRiwayat) : bersih;

    // Riwayat yang dipotong tidak boleh diawali jawaban asisten.
    while (potong.isNotEmpty && !potong.first.dariPengguna) {
      potong.removeAt(0);
    }

    final envelope = await _api.postEnvelope(
      '/assistant/chat',
      body: {
        'messages': potong.map((p) => p.toJson()).toList(),
        'conversation_id': ?conversationId,
      },
      receiveTimeout: AppConfig.assistantTimeout,
    );

    return BalasanAsisten.fromEnvelope(envelope);
  }
}

final assistantRepositoryProvider = Provider<AssistantRepository>(
  (ref) => AssistantRepository(ref.watch(apiClientProvider)),
);
