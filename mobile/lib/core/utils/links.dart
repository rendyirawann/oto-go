import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../widgets/feedback.dart';

/// Membuka tautan di browser/aplikasi luar. Hanya http(s), mailto, dan tel
/// yang diizinkan -- tautan dari konten server tidak boleh memicu skema lain.
///
/// Selalu mengembalikan true agar paket HTML tidak mencoba membukanya sendiri.
Future<bool> bukaTautan(BuildContext context, String url) async {
  final uri = Uri.tryParse(url.trim());
  const aman = {'http', 'https', 'mailto', 'tel'};

  if (uri == null || !aman.contains(uri.scheme)) {
    AppSnack.peringatan(context, 'Tautan ini tidak bisa dibuka.');

    return true;
  }

  try {
    final ok = await launchUrl(uri, mode: LaunchMode.externalApplication);

    if (!ok && context.mounted) AppSnack.galat(context, 'Gagal membuka tautan.');
  } catch (_) {
    if (context.mounted) AppSnack.galat(context, 'Gagal membuka tautan.');
  }

  return true;
}
