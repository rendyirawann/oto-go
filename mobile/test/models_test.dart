import 'package:flutter_test/flutter_test.dart';
import 'package:otogo_mobile/features/assistant/domain/chat.dart';
import 'package:otogo_mobile/features/auth/domain/user.dart';
import 'package:otogo_mobile/features/blog/domain/post.dart';
import 'package:otogo_mobile/features/catalog/domain/catalog.dart';
import 'package:otogo_mobile/features/vehicles/domain/vehicle.dart';

/// Parsing JSON mengikuti kontrak API OtoGo v1. Pembaca harus toleran:
/// angka bisa datang sebagai string, field opsional bisa null.
void main() {
  group('AuthSession', () {
    test('membaca token dan user dari /auth/login', () {
      final sesi = AuthSession.fromJson({
        'token': '1|abc',
        'token_type': 'Bearer',
        'user': {
          'id': '9b1d7a3e-0000-4000-8000-000000000001',
          'name': 'Rina Siregar',
          'email': 'rina@contoh.id',
          'phone': null,
          'avatar_url': null,
          'roles': ['customer'],
          'created_at': '2026-10-31T10:00:00+07:00',
        },
      });

      expect(sesi.token, '1|abc');
      expect(sesi.user.namaDepan, 'Rina');
      expect(sesi.user.initials, 'RS');
      expect(sesi.user.isCustomer, isTrue);
      expect(sesi.user.createdAt, isNotNull);
    });
  });

  group('Vehicle', () {
    test('kategori & model bersarang terbaca, judul dari merek + model', () {
      final v = Vehicle.fromJson({
        'id': 12,
        'plate_number': 'BK 1234 ABC',
        'nickname': null,
        'year': '2019',
        'color': 'Putih',
        'category': {'id': 7, 'code': 'car_mpv', 'name': 'MPV (Avanza, Xenia, Ertiga, dll)', 'vehicle_type': 'mobil'},
        'model': {'id': 3, 'name': 'Avanza', 'brand_name': 'Toyota'},
      });

      expect(v.id, '12');
      expect(v.year, 2019);
      expect(v.judul, 'Toyota Avanza');
      expect(v.keterangan, 'MPV · 2019 · Putih');
      expect(v.isMotor, isFalse);
    });

    test('tanpa model, judul memakai nama pendek kategori', () {
      final v = Vehicle.fromJson({
        'id': 'a1',
        'plate_number': 'BK 5 XY',
        'category': {'id': 1, 'code': 'motor_matic', 'name': 'Motor Matic'},
        'model': null,
      });

      expect(v.judul, 'Motor Matic');
      expect(v.isMotor, isTrue);
    });

    test('VehicleInput mengirim ID numerik sebagai angka, ULID sebagai teks', () {
      const angka = VehicleInput(vehicleCategoryId: '7', plateNumber: 'BK 1 A', vehicleModelId: '3', color: '  ');
      const ulid = VehicleInput(vehicleCategoryId: '01HZX9ABCDEF', plateNumber: 'BK 1 A');

      expect(angka.toJson()['vehicle_category_id'], 7);
      expect(angka.toJson()['vehicle_model_id'], 3);
      expect(angka.toJson()['color'], isNull, reason: 'isian kosong dikirim null');
      expect(ulid.toJson()['vehicle_category_id'], '01HZX9ABCDEF');
      expect(ulid.toJson()['vehicle_model_id'], isNull);
    });
  });

  group('PriceEstimates', () {
    test('dikelompokkan per group dengan urutan kemunculan', () {
      final hasil = PriceEstimates.fromEnvelope({
        'data': [
          {'module': 'carwash', 'group': 'Cuci', 'service': 'Cuci reguler', 'price': 45000, 'duration_minutes': 30},
          {'module': 'carwash', 'group': 'Interior', 'service': 'Salon interior', 'price': '350000'},
          {'module': 'carwash', 'group': 'Cuci', 'service': 'Cuci kolong', 'price': 30000, 'is_addon': true},
        ],
        'meta': {'template': 'Tarif Pasar Medan 2026', 'disclaimer': 'Estimasi.'},
      });

      expect(hasil.template, 'Tarif Pasar Medan 2026');
      expect(hasil.perKelompok.keys.toList(), ['Cuci', 'Interior']);
      expect(hasil.perKelompok['Cuci']!.length, 2);
      expect(hasil.items[1].price, 350000);
      expect(hasil.items[2].isAddon, isTrue);
    });

    test('nama pendek kategori membuang keterangan dalam kurung', () {
      const k = VehicleCategory(id: '1', code: 'car_suv', name: 'SUV (Fortuner, Pajero, CR-V, dll)');

      expect(k.namaPendek, 'SUV');
    });
  });

  group('Blog', () {
    test('detail artikel membawa ringkasan, konten, dan artikel terkait', () {
      final d = PostDetail.fromJson({
        'id': 1,
        'slug': 'ganti-oli-matic',
        'title': 'Kapan ganti oli motor matic?',
        'excerpt': 'Patokan km dan tanda-tandanya.',
        'cover_url': null,
        'category': {'name': 'Motor', 'slug': 'motor'},
        'reading_minutes': 5,
        'published_at': '2026-10-01T08:00:00+07:00',
        'content_html': '<p>Isi</p>',
        'author_name': 'Tim OtoGo',
        'related': [
          {'id': 2, 'slug': 'cvt', 'title': 'Servis CVT'},
        ],
      });

      expect(d.summary.categorySlug, 'motor');
      expect(d.summary.readingMinutes, 5);
      expect(d.contentHtml, '<p>Isi</p>');
      expect(d.related.single.slug, 'cvt');
    });
  });

  group('Asisten', () {
    test('balasan sukses membaca reply, sumber, dan conversation_id', () {
      final b = BalasanAsisten.fromEnvelope({
        'ok': true,
        'data': {
          'reply': 'Ganti oli tiap **2.000 km**.',
          'sources': [
            {'title': 'Panduan', 'url': 'https://www.contoh.id/oli'},
            {'title': 'Tanpa URL', 'url': ''},
          ],
          'model': 'qwen/qwen3.8-27b',
          'conversation_id': 'c-1',
        },
      });

      expect(b.ok, isTrue);
      expect(b.sumber, hasLength(1), reason: 'sumber tanpa URL dibuang');
      expect(b.sumber.single.domain, 'contoh.id');
      expect(b.conversationId, 'c-1');
    });

    test('ok=false membawa pesan ramah dari server', () {
      final b = BalasanAsisten.fromEnvelope({'ok': false, 'message': 'Asisten sedang sibuk.'});

      expect(b.ok, isFalse);
      expect(b.pesan, 'Asisten sedang sibuk.');
    });

    test('pesan dikirim dengan role user/assistant', () {
      const p = PesanChat(peran: PeranPesan.assistant, isi: 'Halo');

      expect(p.toJson(), {'role': 'assistant', 'content': 'Halo'});
    });
  });
}
