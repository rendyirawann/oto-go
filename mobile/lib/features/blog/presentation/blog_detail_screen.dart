import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_widget_from_html_core/flutter_widget_from_html_core.dart';

import '../../../core/network/api_exception.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/utils/formatters.dart';
import '../../../core/utils/links.dart';
import '../../../core/widgets/common.dart';
import '../../../core/widgets/net_image.dart';
import '../../../core/widgets/skeletons.dart';
import '../domain/post.dart';
import 'blog_list_screen.dart';
import 'blog_providers.dart';

class BlogDetailScreen extends ConsumerWidget {
  const BlogDetailScreen({super.key, required this.slug});

  final String slug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(postDetailProvider(slug));

    return Scaffold(
      appBar: AppBar(title: const Text('Artikel')),
      body: async.when(
        loading: () => const Padding(
          padding: EdgeInsets.all(AppSpacing.lg),
          child: Column(
            children: [
              KotakTiruan(tinggi: 200),
              SizedBox(height: AppSpacing.lg),
              BarisSkeleton(jumlah: 8),
            ],
          ),
        ),
        error: (e, _) => ErrorState(
          title: e is ApiException && e.isNotFound ? 'Artikel tidak ditemukan' : 'Gagal memuat artikel',
          message: e is ApiException ? e.message : 'Terjadi kesalahan tak terduga.',
          onRetry: () => ref.invalidate(postDetailProvider(slug)),
        ),
        data: (post) => _Isi(post: post),
      ),
    );
  }
}

class _Isi extends StatelessWidget {
  const _Isi({required this.post});

  final PostDetail post;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final scheme = theme.colorScheme;
    final s = post.summary;
    final meta = [
      if (post.authorName != null) post.authorName!,
      if (s.publishedAt != null) Fmt.tanggal(s.publishedAt),
      if (s.readingMinutes != null) '${s.readingMinutes} menit baca',
    ].join(' · ');

    return ListView(
      padding: const EdgeInsets.only(bottom: AppSpacing.xxxl),
      children: [
        if (s.coverUrl != null) AspectRatio(aspectRatio: 16 / 9, child: NetImage(s.coverUrl)),
        Padding(
          padding: const EdgeInsets.fromLTRB(AppSpacing.lg, AppSpacing.xl, AppSpacing.lg, 0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (s.categoryName != null)
                Text(
                  s.categoryName!.toUpperCase(),
                  style: theme.textTheme.labelSmall?.copyWith(color: scheme.onPrimaryContainer, letterSpacing: 1),
                ),
              const SizedBox(height: AppSpacing.xs),
              Text(s.title, style: theme.textTheme.headlineSmall),
              if (meta.isNotEmpty) ...[
                const SizedBox(height: AppSpacing.sm),
                Text(meta, style: theme.textTheme.bodySmall?.copyWith(color: scheme.onSurfaceVariant)),
              ],
              const SizedBox(height: AppSpacing.lg),
              const Divider(),
              const SizedBox(height: AppSpacing.md),
              HtmlWidget(
                post.contentHtml,
                textStyle: theme.textTheme.bodyLarge,
                onTapUrl: (url) => bukaTautan(context, url),
                customStylesBuilder: (el) => switch (el.localName) {
                  'h2' => {'margin-top': '1.2em', 'font-size': '1.25em', 'font-weight': '700'},
                  'h3' => {'margin-top': '1em', 'font-size': '1.1em', 'font-weight': '700'},
                  'blockquote' => {'margin': '0', 'padding-left': '12px', 'border-left': '3px solid #FF5A1F'},
                  'table' => {'border-collapse': 'collapse'},
                  'td' || 'th' => {'padding': '6px 8px', 'border': '1px solid #DEDAD0'},
                  _ => null,
                },
                // Gambar di dalam artikel lewat NetImage: SVG & CORS web ditangani.
                customWidgetBuilder: (el) {
                  final src = el.localName == 'img' ? el.attributes['src'] : null;

                  if (src == null || src.isEmpty) return null;

                  return Padding(
                    padding: const EdgeInsets.symmetric(vertical: AppSpacing.sm),
                    child: NetImage(src, fit: BoxFit.contain, borderRadius: AppRadius.field),
                  );
                },
                onErrorBuilder: (context, el, err) => const SizedBox.shrink(),
              ),
            ],
          ),
        ),
        if (post.related.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.xxl),
          const Padding(
            padding: EdgeInsets.symmetric(horizontal: AppSpacing.lg),
            child: GroupHeader(title: 'Baca juga', icon: Icons.auto_stories_outlined),
          ),
          for (final r in post.related)
            Padding(
              padding: const EdgeInsets.fromLTRB(AppSpacing.lg, 0, AppSpacing.lg, AppSpacing.md),
              child: PostTile(post: r),
            ),
        ],
      ],
    );
  }
}
