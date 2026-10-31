import '../../../core/utils/json.dart';

class PostCategory {
  const PostCategory({required this.id, required this.name, required this.slug, this.postsCount = 0});

  final String id;
  final String name;
  final String slug;
  final int postsCount;

  factory PostCategory.fromJson(Map<String, dynamic> json) => PostCategory(
        id: J.str(json['id']),
        name: J.str(json['name']),
        slug: J.str(json['slug']),
        postsCount: J.int_(json['posts_count']),
      );
}

/// Ringkasan artikel untuk daftar.
class PostSummary {
  const PostSummary({
    required this.id,
    required this.slug,
    required this.title,
    this.excerpt,
    this.coverUrl,
    this.categoryName,
    this.categorySlug,
    this.readingMinutes,
    this.publishedAt,
  });

  final String id;
  final String slug;
  final String title;
  final String? excerpt;
  final String? coverUrl;
  final String? categoryName;
  final String? categorySlug;
  final int? readingMinutes;
  final DateTime? publishedAt;

  factory PostSummary.fromJson(Map<String, dynamic> json) {
    final cat = json['category'];
    final c = cat is Map ? J.map(cat) : const <String, dynamic>{};

    return PostSummary(
      id: J.str(json['id']),
      slug: J.str(json['slug']),
      title: J.str(json['title']),
      excerpt: J.strOrNull(json['excerpt']),
      coverUrl: J.strOrNull(json['cover_url']),
      categoryName: J.strOrNull(c['name']),
      categorySlug: J.strOrNull(c['slug']),
      readingMinutes: J.intOrNull(json['reading_minutes']),
      publishedAt: J.date(json['published_at']),
    );
  }
}

/// Artikel lengkap.
class PostDetail {
  const PostDetail({
    required this.summary,
    required this.contentHtml,
    this.authorName,
    this.related = const [],
  });

  final PostSummary summary;
  final String contentHtml;
  final String? authorName;
  final List<PostSummary> related;

  factory PostDetail.fromJson(Map<String, dynamic> json) => PostDetail(
        summary: PostSummary.fromJson(json),
        contentHtml: J.str(json['content_html']),
        authorName: J.strOrNull(json['author_name']),
        related: J.list(json['related']).map(PostSummary.fromJson).toList(),
      );
}
