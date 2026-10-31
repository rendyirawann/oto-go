# OtoGo Mobile

Aplikasi pelanggan OtoGo (Flutter): simpan data kendaraan, cek kisaran harga
servis & cuci di Medan, baca panduan perawatan, dan tanya Asisten AI. Booking
bengkel / doorsmeer / sparepart ditandai **Segera hadir** sampai modul booking
di API siap.

API-nya ada di proyek Laravel induk (`../`, satu `php artisan serve` untuk web,
admin, dan API). Dokumentasi endpoint: `http://127.0.0.1:8822/api/documentation`.

---

## Menjalankan

Cara termudah: jalankan `start-dev.bat` di akar proyek. Launcher itu menyalakan
API dan server web Flutter sekaligus.

Manual:

```bash
cd mobile
flutter pub get

# Web (dipakai launcher) - buka http://127.0.0.1:5180
flutter run -d web-server --web-hostname 127.0.0.1 --web-port 5180 \
  --dart-define=API_BASE_URL=http://127.0.0.1:8822

# Emulator Android (API otomatis ke http://10.0.2.2:8822)
flutter run -d emulator-5554
```

`-d web-server` tidak mengikat diri ke satu browser, jadi bisa dibuka di Chrome,
Edge, Firefox, atau browser HP di Wi-Fi yang sama (pakai `--web-hostname 0.0.0.0`).

Pastikan API sudah menyala dan sudah di-seed (`php artisan migrate --seed`),
karena kategori kendaraan, wilayah, dan estimasi harga diambil dari sana.

## Alamat API

Urutan prioritas (yang paling atas menang):

1. **Disimpan pengguna** lewat *Profil → Alamat API* (atau tombol "Server: ..."
   di halaman masuk). Ada tombol **Uji Koneksi** yang memanggil `/api/health`.
2. **`--dart-define=API_BASE_URL=...`** saat `flutter run` / `flutter build`.
   Launcher memakai `http://127.0.0.1:8822`.
3. **Bawaan menurut platform** (build debug):
   - web / desktop → `http://127.0.0.1:8822`
   - emulator Android → `http://10.0.2.2:8822` (alias localhost komputer)
   - build rilis → `AppConfig.baseUrlProduksi` (ganti sebelum rilis).

Isi alamat **tanpa** `/api` di belakang; `/api/v1` ditambahkan otomatis.
Perangkat fisik: pakai IP komputer, mis. `http://192.168.1.10:8822`, dan jalankan
API dengan `php artisan serve --host=0.0.0.0 --port=8822`.

Mengganti alamat API mengakhiri sesi (token hanya berlaku di server penerbitnya).

## Fitur

| Tab | Isi |
|---|---|
| Beranda | Sapaan, kendaraan utama bergaya pelat "BK", kartu modul (Segera hadir), estimasi harga per kategori kendaraan, artikel terbaru, wilayah layanan |
| Kendaraan | Daftar, tambah/ubah (jenis → merek → model → kategori tarif + pelat), hapus |
| Blog | Cari, filter kategori, muat-lebih, detail artikel (HTML) + artikel terkait |
| Asisten | Chat AI (markdown + sumber), saran pertanyaan, coba lagi bila gagal |
| Profil | Ubah profil, ganti password, tema (ikuti HP / terang / gelap), alamat API, Swagger, keluar |

## Arsitektur

Feature-first, sama seperti proyek o-mobile asalnya:

```
lib/
  core/        config, network (Dio + ApiException), storage, theme, utils, widgets
  features/
    auth/        login, daftar, profil, ganti password
    catalog/     master kendaraan, wilayah, estimasi harga (read-only)
    vehicles/    garasi pelanggan (CRUD)
    blog/        daftar & detail artikel
    assistant/   chat Asisten AI
    home/        beranda
    profile/     tab profil
    settings/    lembar alamat API
    shell/       bottom nav (google_nav_bar)
  routing/     go_router + splash
```

- State: Riverpod (`StateNotifier` / `AsyncNotifier` / `FutureProvider`).
- Navigasi: go_router dengan penjaga auth (splash → masuk → beranda).
- HTTP: Dio. Respons `{data, meta}`; galat `{message, errors}` dipetakan ke
  `ApiException` (pesan per kolom untuk form). 401 → otomatis kembali ke login.
- Token Sanctum disimpan di `flutter_secure_storage`; password tidak pernah disimpan.
- Tema: palet OtoGo (tinta `#111418`, kertas `#F5F3EE`, oranye `#FF5A1F`),
  Bricolage Grotesque (judul), Plus Jakarta Sans (teks), JetBrains Mono (harga & pelat).
  Logo digambar sebagai vektor (`core/widgets/brand_logo.dart`).
- Gambar: `NetImage` menangani SVG (flutter_svg di native, `<img>` di web) dan CORS di web.

## Test

```bash
flutter analyze
flutter test                     # unit + widget (tanpa jaringan)

# Uji kontrak ke API yang sedang berjalan (membuat 1 akun uji):
flutter test test/live_api_test.dart --dart-define=OTOGO_LIVE_API=http://127.0.0.1:8822
```

## Ikon & logo

Sumber logo: `../resources/brand` (dibuat `make_brand.py`). Setelah mengganti
`assets/logo/app_icon*.png`, jalankan `dart run flutter_launcher_icons`.
Ikon web (`web/icons`, `web/favicon.png`) disalin langsung dari brand kit.

## Build

```bash
flutter build web --release --dart-define=API_BASE_URL=https://api.domain-anda.id
flutter build apk --release --dart-define=API_BASE_URL=https://api.domain-anda.id
```

Sebelum rilis Android: atur signing config (`android/app/build.gradle.kts`) dan
matikan cleartext HTTP di `android/app/src/main/res/xml/network_security_config.xml`.
