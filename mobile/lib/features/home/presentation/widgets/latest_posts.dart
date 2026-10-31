import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/common.dart';
import '../../../../core/widgets/net_image.dart';
import '../../../../core/widgets/skeletons.dart';
import '../../../blog/presentation/blog_providers.dart';
import '../../../shell/presentation/app_shell.dart';

/// Tiga artikel terbaru dalam satu baris geser. Disembunyikan bila gagal
/// dimuat -- beranda tidak boleh terlihat rusak hanya karena blog.
class LatestPosts extends ConsumerWidget {
  const LatestPosts({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(latestPostsProvider);
    final theme = Theme.of(context);

    if (async.hasError || (async.hasValue && async.value!.isEmpty)) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
          child: GroupHeader(
            title: 'Bacaan perawatan',
            icon: Icons.menu_book_outlined,
            action: TextButton(
              onPressed: () => ref.read(tabAktifProvider.notifier).state = TabIndeks.blog,
              child: const Text('Lihat semua'),
            ),
          ),
        ),
        SizedBox(
          height: 214,
          child: async.when(
            loading: () => ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              children: const [
                KotakTiruan(tinggi: 210, lebar: 240),
                SizedBox(width: AppSpacing.md),
                KotakTiruan(tinggi: 210, lebar: 240),
              ],
            ),
            error: (_, _) => const SizedBox.shrink(),
            data: (posts) => ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
              itemCount: posts.length,
              separatorBuilder: (_, _) => const SizedBox(width: AppSpacing.md),
              itemBuilder: (context, i) {
                final p = posts[i];

                return SizedBox(
                  width: 240,
                  child: SectionCard(
                    padding: EdgeInsets.zero,
                    onTap: () => context.push('/blog/${p.slug}'),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        NetImage(p.coverUrl, height: 112, width: double.infinity),
                        Padding(
                          padding: const EdgeInsets.all(AppSpacing.md),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (p.categoryName != null)
                                Text(
                                  p.categoryName!.toUpperCase(),
                                  style: theme.textTheme.labelSmall?.copyWith(
                                    color: theme.colorScheme.onPrimaryContainer,
                                    letterSpacing: 0.8,
                                  ),
                                ),
                              const SizedBox(height: 2),
                              Text(
                                p.title,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ),
      ],
    );
  }
}
