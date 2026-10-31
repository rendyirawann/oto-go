import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_markdown_plus/flutter_markdown_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/links.dart';
import '../../../core/widgets/brand_logo.dart';
import '../../shell/presentation/app_shell.dart';
import '../domain/chat.dart';
import 'assistant_controller.dart';

class AssistantScreen extends ConsumerStatefulWidget {
  const AssistantScreen({super.key});

  @override
  ConsumerState<AssistantScreen> createState() => _AssistantScreenState();
}

class _AssistantScreenState extends ConsumerState<AssistantScreen> {
  final _input = TextEditingController();
  final _scroll = ScrollController();
  final _fokus = FocusNode();

  static const _saran = [
    'Berapa kisaran biaya servis berkala mobil MPV di Medan?',
    'Kapan oli motor matic harus diganti?',
    'Apa bedanya cuci salju dan cuci biasa?',
    'Tanda-tanda kampas rem sudah habis?',
  ];

  @override
  void dispose() {
    _input.dispose();
    _scroll.dispose();
    _fokus.dispose();
    super.dispose();
  }

  Future<void> _kirim([String? teks]) async {
    final isi = (teks ?? _input.text).trim();

    if (isi.isEmpty) return;

    _input.clear();
    setState(() {});

    final kirim = ref.read(assistantControllerProvider.notifier).kirim(isi);

    _gulirKeBawah();
    await kirim;
    _gulirKeBawah();
  }

  void _gulirKeBawah() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scroll.hasClients) return;

      _scroll.animateTo(
        _scroll.position.maxScrollExtent,
        duration: const Duration(milliseconds: 320),
        curve: Curves.easeOutCubic,
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(assistantControllerProvider);
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return SafeArea(
      bottom: false,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          TabHeader(
            judul: 'Asisten',
            subjudul: 'Tanya soal perawatan & kisaran harga',
            aksi: state.kosong
                ? null
                : IconButton(
                    tooltip: 'Percakapan baru',
                    onPressed: state.mengirim
                        ? null
                        : () => ref.read(assistantControllerProvider.notifier).percakapanBaru(),
                    icon: const Icon(Icons.add_comment_outlined),
                  ),
          ),
          Expanded(
            child: state.kosong
                ? _Sambutan(saran: _saran, onPilih: _kirim)
                : ListView.builder(
                    controller: _scroll,
                    padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.lg),
                    itemCount: state.pesan.length + (state.mengirim ? 1 : 0),
                    itemBuilder: (context, i) {
                      if (i >= state.pesan.length) return const _Mengetik();

                      final p = state.pesan[i];
                      final terakhir = i == state.pesan.length - 1;

                      return _Gelembung(
                        pesan: p,
                        onCobaLagi: p.galat && !p.dariPengguna && terakhir
                            ? () {
                                ref.read(assistantControllerProvider.notifier).cobaLagi();
                                _gulirKeBawah();
                              }
                            : null,
                      );
                    },
                  ),
          ),

          // ------------------------------------------------ kolom ketik
          Container(
            padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.sm, AppSpacing.sm),
            decoration: BoxDecoration(
              color: scheme.surface,
              border: Border(top: BorderSide(color: scheme.outline)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Expanded(
                  child: CallbackShortcuts(
                    // Enter mengirim di keyboard fisik; Shift+Enter baris baru.
                    bindings: {
                      const SingleActivator(LogicalKeyboardKey.enter): () => _kirim(),
                    },
                    child: TextField(
                      controller: _input,
                      focusNode: _fokus,
                      minLines: 1,
                      maxLines: 5,
                      maxLength: 2000,
                      enabled: !state.mengirim,
                      textInputAction: TextInputAction.newline,
                      onChanged: (_) => setState(() {}),
                      decoration: const InputDecoration(
                        hintText: 'Tulis pertanyaan...',
                        counterText: '',
                        contentPadding: EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                IconButton.filled(
                  tooltip: 'Kirim',
                  onPressed: state.mengirim || _input.text.trim().isEmpty ? null : () => _kirim(),
                  style: IconButton.styleFrom(
                    backgroundColor: AppColors.orange,
                    foregroundColor: Colors.white,
                    minimumSize: const Size(48, 48),
                  ),
                  icon: const Icon(Icons.arrow_upward_rounded),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Sambutan extends StatelessWidget {
  const _Sambutan({required this.saran, required this.onPilih});

  final List<String> saran;
  final ValueChanged<String> onPilih;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        const SizedBox(height: AppSpacing.lg),
        const Center(child: BrandLogo(size: 56)),
        const SizedBox(height: AppSpacing.lg),
        Text(
          'Asisten OtoGo',
          textAlign: TextAlign.center,
          style: theme.textTheme.titleLarge,
        ),
        const SizedBox(height: AppSpacing.xs),
        Text(
          'Jawaban dibuat AI dan bisa keliru. Untuk kerusakan serius, '
          'tetap periksakan kendaraan ke mekanik.',
          textAlign: TextAlign.center,
          style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
        ),
        const SizedBox(height: AppSpacing.xxl),
        for (final (i, s) in saran.indexed)
          Padding(
            padding: const EdgeInsets.only(bottom: AppSpacing.sm),
            child: Material(
              color: scheme.surface,
              shape: RoundedRectangleBorder(
                borderRadius: AppRadius.field,
                side: BorderSide(color: scheme.outline),
              ),
              clipBehavior: Clip.antiAlias,
              child: InkWell(
                onTap: () => onPilih(s),
                child: Padding(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  child: Row(
                    children: [
                      Expanded(child: Text(s, style: theme.textTheme.bodyMedium)),
                      Icon(Icons.north_east_rounded, size: 16, color: scheme.onSurfaceVariant),
                    ],
                  ),
                ),
              ),
            ),
          ).animate(delay: (60 * i).ms).fadeIn(duration: 300.ms).slideY(begin: 0.15, end: 0),
      ],
    );
  }
}

class _Gelembung extends StatelessWidget {
  const _Gelembung({required this.pesan, this.onCobaLagi});

  final PesanChat pesan;
  final VoidCallback? onCobaLagi;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final pengguna = pesan.dariPengguna;

    final Color latar;
    final Color teks;

    if (pengguna) {
      latar = scheme.inverseSurface;
      teks = scheme.onInverseSurface;
    } else if (pesan.galat) {
      latar = scheme.errorContainer;
      teks = scheme.onErrorContainer;
    } else {
      latar = scheme.surface;
      teks = scheme.onSurface;
    }

    final isi = pengguna || pesan.galat
        ? Text(pesan.isi, style: theme.textTheme.bodyMedium?.copyWith(color: teks))
        : MarkdownBody(
            data: pesan.isi,
            selectable: true,
            onTapLink: (text, href, title) {
              if (href != null) bukaTautan(context, href);
            },
            styleSheet: MarkdownStyleSheet.fromTheme(theme).copyWith(
              p: theme.textTheme.bodyMedium?.copyWith(color: teks),
              listBullet: theme.textTheme.bodyMedium?.copyWith(color: teks),
              a: theme.textTheme.bodyMedium?.copyWith(
                color: scheme.onPrimaryContainer,
                decoration: TextDecoration.underline,
              ),
              code: AppTheme.money(context, fontSize: 13, weight: FontWeight.w500),
              blockquoteDecoration: BoxDecoration(
                border: const Border(left: BorderSide(color: AppColors.orange, width: 3)),
                color: scheme.surfaceContainerHigh,
              ),
            ),
          );

    return Align(
      alignment: pengguna ? Alignment.centerRight : Alignment.centerLeft,
      child: ConstrainedBox(
        constraints: BoxConstraints(maxWidth: MediaQuery.sizeOf(context).width * 0.86),
        child: Container(
          margin: const EdgeInsets.only(bottom: AppSpacing.md),
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: AppSpacing.md),
          decoration: BoxDecoration(
            color: latar,
            borderRadius: BorderRadius.only(
              topLeft: const Radius.circular(AppRadius.lg),
              topRight: const Radius.circular(AppRadius.lg),
              bottomLeft: Radius.circular(pengguna ? AppRadius.lg : 4),
              bottomRight: Radius.circular(pengguna ? 4 : AppRadius.lg),
            ),
            border: pengguna ? null : Border.all(color: scheme.outline),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              isi,
              if (pesan.sumber.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.md),
                Text('Sumber', style: theme.textTheme.labelSmall?.copyWith(color: scheme.onSurfaceVariant)),
                const SizedBox(height: AppSpacing.xs),
                Wrap(
                  spacing: AppSpacing.xs,
                  runSpacing: AppSpacing.xs,
                  children: [
                    for (final (i, s) in pesan.sumber.indexed)
                      ActionChip(
                        visualDensity: VisualDensity.compact,
                        avatar: Text('${i + 1}', style: theme.textTheme.labelSmall),
                        label: Text(s.domain.isEmpty ? s.title : s.domain, overflow: TextOverflow.ellipsis),
                        tooltip: s.title,
                        onPressed: () => bukaTautan(context, s.url),
                      ),
                  ],
                ),
              ],
              if (onCobaLagi != null) ...[
                const SizedBox(height: AppSpacing.sm),
                TextButton.icon(
                  onPressed: onCobaLagi,
                  icon: const Icon(Icons.refresh_rounded, size: 18),
                  label: const Text('Coba lagi'),
                ),
              ],
            ],
          ),
        ),
      ),
    ).animate().fadeIn(duration: 220.ms).slideY(begin: 0.12, end: 0, curve: Curves.easeOutCubic);
  }
}

/// Indikator "sedang mengetik" tiga titik.
class _Mengetik extends StatelessWidget {
  const _Mengetik();

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Align(
      alignment: Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: AppSpacing.md),
        padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg, vertical: 14),
        decoration: BoxDecoration(
          color: scheme.surface,
          borderRadius: BorderRadius.circular(AppRadius.lg),
          border: Border.all(color: scheme.outline),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            for (var i = 0; i < 3; i++)
              Container(
                width: 7,
                height: 7,
                margin: const EdgeInsets.symmetric(horizontal: 2),
                decoration: BoxDecoration(color: scheme.onSurfaceVariant, shape: BoxShape.circle),
              )
                  .animate(onPlay: (c) => c.repeat(reverse: true), delay: (140 * i).ms)
                  .fadeIn(begin: 0.25, duration: 420.ms),
          ],
        ),
      ),
    );
  }
}
