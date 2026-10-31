import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/domain/paginated.dart';
import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/utils/json.dart';
import '../domain/post.dart';

class BlogRepository {
  const BlogRepository(this._api);

  final ApiClient _api;

  Future<Paginated<PostSummary>> posts({int page = 1, int perPage = 10, String? q, String? category}) async {
    final envelope = await _api.getEnvelope('/posts', query: {
      'page': page,
      'per_page': perPage,
      'q': q,
      'category': category,
    });

    return Paginated.fromEnvelope(envelope, PostSummary.fromJson);
  }

  Future<PostDetail> detail(String slug) async =>
      PostDetail.fromJson(J.map(await _api.get('/posts/${Uri.encodeComponent(slug)}')));

  Future<List<PostCategory>> categories() async =>
      J.list(await _api.get('/post-categories')).map(PostCategory.fromJson).toList();
}

final blogRepositoryProvider = Provider<BlogRepository>(
  (ref) => BlogRepository(ref.watch(apiClientProvider)),
);
