import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/widgets/common.dart';
import '../../../core/widgets/net_image.dart';
import '../../../core/widgets/skeletons.dart';
import '../../shell/presentation/app_shell.dart';
import '../domain/post.dart';
import 'blog_providers.dart';

class BlogListScreen extends ConsumerStatefulWidget {
  const BlogListScreen({super.key});

  @override
  ConsumerState<BlogListScreen> createState() => _BlogListScreenState();
}

class _BlogListScreenState extends ConsumerState<BlogListScreen> {
  final _cari = TextEditingController();
  final _scroll = ScrollController();
  Timer? _jeda;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_cekUjung);
  }

  @override
  void dispose() {
    _jeda?.cancel();
    _cari.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _cekUjung() {
    if (_scroll.position.pixels > _scroll.position.maxScrollExtent - 320) {
      ref.read(blogListProvider.notifier).muatLagi();
    }
  }

  /// Pencarian menunggu pengguna berhenti mengetik sebentar, supaya tidak
  /// mengirim satu permintaan per huruf.
  void _ketik(String q) {
    _jeda?.cancel();
    _jeda = Timer(const Duration(milliseconds: 400), () => ref.read(blogListProvider.notifier).cari(q));
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(blogListProvider);
    final kategori = ref.watch(blogCategoriesProvider).valueOrNull ?? const <PostCategory>[];
    final theme = Theme.of(context);

    return SafeArea(
      bottom: false,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const TabHeader(judul: 'Blog', subjudul: 'Panduan merawat kendaraan di Medan'),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
            child: TextField(
              controller: _cari,
              onChanged: _ketik,
              textInputAction: TextInputAction.search,
              decoration: InputDecoration(
                hintText: 'Cari artikel, mis. ganti oli',
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: _cari.text.isEmpty
                    ? null
                    : IconButton(
                        tooltip: 'Hapus pencarian',
                        icon: const Icon(Icons.close_rounded),
                        onPressed: () {
                          _cari.clear();
                          _ketik('');
                        },
                      ),
              ),
            ),
          ),
          if (kategori.isNotEmpty)
            SizedBox(
              height: 52,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.md, AppSpacing.lg, 0),
                children: [
                  _Chip(
                    label: 'Semua',
                    aktif: state.kategori == null,
                    onTap: () => ref.read(blogListProvider.notifier).pilihKategori(null),
                  ),
                  for (final k in kategori)
                    _Chip(
                      label: k.name,
                      aktif: state.kategori == k.slug,
                      onTap: () => ref.read(blogListProvider.notifier).pilihKategori(k.slug),
                    ),
                ],
              ),
            ),
          const SizedBox(height: AppSpacing.sm),
          Expanded(
            child: Builder(
              builder: (context) {
                if (state.memuat && state.halaman.isEmpty) {
                  return const ListSkeleton(tinggi: 112);
                }

                if (state.error != null && state.halaman.isEmpty) {
                  return ErrorState(
                    message: state.error!,
                    onRetry: () => ref.read(blogListProvider.notifier).muat(),
                  );
                }

                if (state.halaman.isEmpty) {
                  return ListView(
                    children: [
                      const SizedBox(height: 40),
                      EmptyState(
                        icon: Icons.search_off_rounded,
                        title: 'Artikel tidak ditemukan',
                        message: state.q.isEmpty
                            ? 'Belum ada artikel di kategori ini.'
                            : 'Tidak ada artikel yang cocok dengan "${state.q}".',
                        compact: true,
                      ),
                    ],
                  );
                }

                final items = state.halaman.items;

                return RefreshIndicator(
                  onRefresh: () => ref.read(blogListProvider.notifier).muat(),
                  child: ListView.separated(
                    controller: _scroll,
                    padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.sm, AppSpacing.lg, AppSpacing.xxxl),
                    itemCount: items.length + (state.halaman.hasMore ? 1 : 0),
                    separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.md),
                    itemBuilder: (context, i) {
                      if (i >= items.length) {
                        return const Padding(
                          padding: EdgeInsets.all(AppSpacing.lg),
                          child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                        );
                      }

                      return PostTile(post: items[i])
                          .animate(delay: (i < 6 ? 40 * i : 0).ms)
                          .fadeIn(duration: 280.ms);
                    },
                  ),
                );
              },
            ),
          ),
          if (state.memuat && !state.halaman.isEmpty)
            LinearProgressIndicator(minHeight: 2, color: theme.colorScheme.primary),
        ],
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({required this.label, required this.aktif, required this.onTap});

  final String label;
  final bool aktif;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: AppSpacing.sm),
      child: ChoiceChip(label: Text(label), selected: aktif, showCheckmark: false, onSelected: (_) => onTap()),
    );
  }
}

/// Baris artikel: sampul kecil di kiri, judul & ringkasan di kanan.
class PostTile extends StatelessWidget {
  const PostTile({super.key, required this.post});

  final PostSummary post;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final meta = [
      if (post.categoryName != null) post.categoryName!,
      if (post.readingMinutes != null) '${post.readingMinutes} mnt baca',
      if (post.publishedAt != null) Fmt.tanggalPendek(post.publishedAt),
    ].join(' · ');

    return SectionCard(
      padding: const EdgeInsets.all(AppSpacing.md),
      onTap: () => context.push('/blog/${post.slug}'),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          NetImage(post.coverUrl, width: 96, height: 88, borderRadius: BorderRadius.circular(AppRadius.md)),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  post.title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700, height: 1.3),
                ),
                if (post.excerpt != null) ...[
                  const SizedBox(height: 4),
                  Text(
                    post.excerpt!,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant),
                  ),
                ],
                if (meta.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(meta, style: theme.textTheme.labelSmall?.copyWith(color: scheme.onSurfaceVariant)),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}
