import '../../../core/utils/json.dart';

enum PeranPesan { user, assistant }

class SumberJawaban {
  const SumberJawaban({required this.title, required this.url});

  final String title;
  final String url;

  /// Domain singkat untuk label chip: "https://www.contoh.id/a" -> "contoh.id".
  String get domain {
    final host = Uri.tryParse(url)?.host ?? '';

    return host.startsWith('www.') ? host.substring(4) : host;
  }

  factory SumberJawaban.fromJson(Map<String, dynamic> json) => SumberJawaban(
        title: J.str(json['title'], J.str(json['url'])),
        url: J.str(json['url']),
      );
}

class PesanChat {
  const PesanChat({
    required this.peran,
    required this.isi,
    this.sumber = const [],
    this.model,
    this.galat = false,
  });

  final PeranPesan peran;

  /// Teks markdown (asisten) atau teks biasa (pengguna).
  final String isi;
  final List<SumberJawaban> sumber;
  final String? model;

  /// Pesan galat lokal -- ditampilkan di layar, tidak dikirim ke server.
  final bool galat;

  bool get dariPengguna => peran == PeranPesan.user;

  Map<String, dynamic> toJson() => {
        'role': peran == PeranPesan.user ? 'user' : 'assistant',
        'content': isi,
      };
}

/// Balasan `POST /assistant/chat`.
class BalasanAsisten {
  const BalasanAsisten({
    required this.ok,
    this.reply,
    this.sumber = const [],
    this.model,
    this.conversationId,
    this.pesan,
  });

  final bool ok;
  final String? reply;
  final List<SumberJawaban> sumber;
  final String? model;
  final String? conversationId;

  /// Pesan ramah dari server bila `ok == false` (mis. AI sedang sibuk).
  final String? pesan;

  factory BalasanAsisten.fromEnvelope(Map<String, dynamic> envelope) {
    final ok = J.bool_(envelope['ok'], envelope.containsKey('data'));
    final data = J.map(envelope['data']);

    return BalasanAsisten(
      ok: ok && J.strOrNull(data['reply']) != null,
      reply: J.strOrNull(data['reply']),
      sumber: J.list(data['sources']).map(SumberJawaban.fromJson).where((s) => s.url.isNotEmpty).toList(),
      model: J.strOrNull(data['model']),
      conversationId: J.strOrNull(data['conversation_id']),
      pesan: J.strOrNull(envelope['message']),
    );
  }
}
