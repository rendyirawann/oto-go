import '../utils/json.dart';

/// Hasil berhalaman dari API: data[] + meta pagination.
class Paginated<T> {
  const Paginated({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  const Paginated.empty()
      : items = const [],
        currentPage = 1,
        lastPage = 1,
        perPage = 0,
        total = 0;

  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  bool get hasMore => currentPage < lastPage;

  int get nextPage => currentPage + 1;

  bool get isEmpty => items.isEmpty;

  factory Paginated.fromEnvelope(
    Map<String, dynamic> envelope,
    T Function(Map<String, dynamic>) parse,
  ) {
    final meta = J.map(envelope['meta']);

    return Paginated<T>(
      items: J.list(envelope['data']).map(parse).toList(),
      currentPage: J.int_(meta['current_page'], 1),
      lastPage: J.int_(meta['last_page'], 1),
      perPage: J.int_(meta['per_page']),
      total: J.int_(meta['total']),
    );
  }

  /// Gabungkan halaman berikutnya ke daftar yang sudah ada (infinite scroll).
  Paginated<T> merge(Paginated<T> next) => Paginated<T>(
        items: [...items, ...next.items],
        currentPage: next.currentPage,
        lastPage: next.lastPage,
        perPage: next.perPage,
        total: next.total,
      );
}
