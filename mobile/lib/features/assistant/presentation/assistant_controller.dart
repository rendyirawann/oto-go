import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_exception.dart';
import '../../auth/presentation/auth_controller.dart';
import '../data/assistant_repository.dart';
import '../domain/chat.dart';

class AssistantState {
  const AssistantState({this.pesan = const [], this.mengirim = false, this.conversationId});

  final List<PesanChat> pesan;
  final bool mengirim;
  final String? conversationId;

  bool get kosong => pesan.isEmpty;

  AssistantState copyWith({List<PesanChat>? pesan, bool? mengirim, String? conversationId}) => AssistantState(
        pesan: pesan ?? this.pesan,
        mengirim: mengirim ?? this.mengirim,
        conversationId: conversationId ?? this.conversationId,
      );
}

class AssistantController extends StateNotifier<AssistantState> {
  AssistantController(this._repo) : super(const AssistantState());

  final AssistantRepository _repo;

  /// Teks terakhir yang gagal terkirim, untuk tombol "Coba lagi".
  String? _gagal;

  Future<void> kirim(String teks) async {
    final isi = teks.trim();

    if (isi.isEmpty || state.mengirim) return;

    // Gelembung galat sebelumnya dibuang supaya tidak ikut ke riwayat.
    final riwayat = [
      ...state.pesan.where((p) => !p.galat),
      PesanChat(peran: PeranPesan.user, isi: isi),
    ];

    state = state.copyWith(pesan: riwayat, mengirim: true);

    try {
      final balasan = await _repo.kirim(riwayat, conversationId: state.conversationId);

      if (!mounted) return;

      if (balasan.ok) {
        _gagal = null;
        state = state.copyWith(
          mengirim: false,
          conversationId: balasan.conversationId,
          pesan: [
            ...riwayat,
            PesanChat(
              peran: PeranPesan.assistant,
              isi: balasan.reply!,
              sumber: balasan.sumber,
              model: balasan.model,
            ),
          ],
        );
      } else {
        _tandaiGagal(
          riwayat,
          isi,
          balasan.pesan ?? 'Asisten sedang tidak bisa menjawab. Coba beberapa saat lagi.',
        );
      }
    } on ApiException catch (e) {
      if (!mounted) return;

      _tandaiGagal(riwayat, isi, e.message);
    }
  }

  void _tandaiGagal(List<PesanChat> riwayat, String isi, String pesan) {
    _gagal = isi;

    // Pesan pengguna yang gagal dikeluarkan dari riwayat; isinya disimpan
    // untuk dikirim ulang lewat tombol "Coba lagi".
    final tanpaTerakhir = riwayat.sublist(0, riwayat.length - 1);

    state = state.copyWith(
      mengirim: false,
      pesan: [
        ...tanpaTerakhir,
        PesanChat(peran: PeranPesan.user, isi: isi, galat: true),
        PesanChat(peran: PeranPesan.assistant, isi: pesan, galat: true),
      ],
    );
  }

  String? get teksGagal => _gagal;

  Future<void> cobaLagi() async {
    final teks = _gagal;

    if (teks == null) return;

    await kirim(teks);
  }

  void percakapanBaru() {
    _gagal = null;
    state = const AssistantState();
  }
}

final assistantControllerProvider = StateNotifierProvider<AssistantController, AssistantState>((ref) {
  // Percakapan direset saat pengguna berganti akun.
  ref.watch(authControllerProvider.select((s) => s.user?.id));

  return AssistantController(ref.watch(assistantRepositoryProvider));
});
