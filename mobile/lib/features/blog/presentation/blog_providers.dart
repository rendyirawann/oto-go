import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/domain/paginated.dart';
import '../../../core/network/api_exception.dart';
import '../data/blog_repository.dart';
import '../domain/post.dart';

/// State daftar artikel dengan pencarian, filter kategori, dan muat-lebih.
class BlogListState {
  const BlogListState({
    this.halaman = const Paginated.empty(),
    this.q = '',
    this.kategori,
    this.memuat = true,
    this.memuatLagi = false,
    this.error,
  });

  final Paginated<PostSummary> halaman;
  final String q;
  final String? kategori;
  final bool memuat;
  final bool memuatLagi;
  final String? error;

  BlogListState copyWith({
    Paginated<PostSummary>? halaman,
    String? q,
    String? kategori,
    bool hapusKategori = false,
    bool? memuat,
    bool? memuatLagi,
    String? error,
    bool hapusError = false,
  }) =>
      BlogListState(
        halaman: halaman ?? this.halaman,
        q: q ?? this.q,
        kategori: hapusKategori ? null : (kategori ?? this.kategori),
        memuat: memuat ?? this.memuat,
        memuatLagi: memuatLagi ?? this.memuatLagi,
        error: hapusError ? null : (error ?? this.error),
      );
}

class BlogListController extends StateNotifier<BlogListState> {
  BlogListController(this._repo) : super(const BlogListState()) {
    muat();
  }

  final BlogRepository _repo;

  /// Nomor permintaan terakhir: balasan yang datang terlambat (pengguna
  /// sudah mengetik kata kunci lain) diabaikan.
  int _permintaan = 0;

  Future<void> muat() async {
    final nomor = ++_permintaan;

    state = state.copyWith(memuat: true, hapusError: true);

    try {
      final hasil = await _repo.posts(q: state.q, category: state.kategori);

      if (nomor != _permintaan || !mounted) return;

      state = state.copyWith(halaman: hasil, memuat: false);
    } on ApiException catch (e) {
      if (nomor != _permintaan || !mounted) return;

      state = state.copyWith(memuat: false, error: e.message);
    }
  }

  Future<void> muatLagi() async {
    if (state.memuat || state.memuatLagi || !state.halaman.hasMore) return;

    state = state.copyWith(memuatLagi: true);

    try {
      final hasil = await _repo.posts(page: state.halaman.nextPage, q: state.q, category: state.kategori);

      if (!mounted) return;

      state = state.copyWith(halaman: state.halaman.merge(hasil), memuatLagi: false);
    } on ApiException {
      if (!mounted) return;

      state = state.copyWith(memuatLagi: false);
    }
  }

  void cari(String q) {
    if (q.trim() == state.q) return;

    state = state.copyWith(q: q.trim());
    muat();
  }

  void pilihKategori(String? slug) {
    if (slug == state.kategori) return;

    state = slug == null ? state.copyWith(hapusKategori: true) : state.copyWith(kategori: slug);
    muat();
  }
}

final blogListProvider = StateNotifierProvider.autoDispose<BlogListController, BlogListState>(
  (ref) => BlogListController(ref.watch(blogRepositoryProvider)),
);

final blogCategoriesProvider = FutureProvider<List<PostCategory>>(
  (ref) => ref.watch(blogRepositoryProvider).categories(),
);

final postDetailProvider = FutureProvider.autoDispose.family<PostDetail, String>(
  (ref, slug) => ref.watch(blogRepositoryProvider).detail(slug),
);

/// Tiga artikel terbaru untuk beranda.
final latestPostsProvider = FutureProvider.autoDispose<List<PostSummary>>((ref) async {
  final hasil = await ref.watch(blogRepositoryProvider).posts(perPage: 3);

  return hasil.items;
});
