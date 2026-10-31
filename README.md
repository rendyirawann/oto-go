# OtoGo

Platform SaaS otomotif untuk **bengkel**, **toko sparepart**, dan **doorsmeer (cuci kendaraan)**, lengkap dengan **booking**, **antar jemput**, dan **layanan di rumah pelanggan**.

Pemilik usaha mendaftarkan usaha dan outletnya, memilih modul yang ingin dibuka, mengatur tarif per kategori kendaraan, dan menerima pembayaran online melalui platform. Pelanggan cukup memakai satu aplikasi untuk mencari, memesan, dan membayar semua layanan perawatan kendaraan.

> Target awal: **Kota Medan, Sumatera Utara** (zona waktu WIB, mata uang IDR).

---

## Daftar Isi

- [Fitur Utama](#fitur-utama)
- [Peran Pengguna](#peran-pengguna)
- [Tech Stack](#tech-stack)
- [Arsitektur Singkat](#arsitektur-singkat)
- [Persyaratan Sistem](#persyaratan-sistem)
- [Instalasi Lokal](#instalasi-lokal)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Testing](#testing)
- [Struktur Folder](#struktur-folder)
- [Roadmap](#roadmap)
- [Lisensi](#lisensi)

---

## Fitur Utama

### Platform (SaaS)
- Registrasi usaha (bengkel / sparepart / doorsmeer) dengan verifikasi oleh superadmin.
- Satu usaha bisa punya banyak outlet / cabang.
- **Modul bisa dibuka-tutup per usaha**: Bengkel, Sparepart, Doorsmeer, atau semuanya.
- Paket langganan per modul dan komisi transaksi yang bisa diatur superadmin.
- Pembayaran terpusat melalui **Tripay** (VA, QRIS, e-wallet, retail).
- Saldo usaha (ledger), riwayat transaksi, dan permintaan pencairan dana.

### Modul Bengkel
- Katalog jasa servis dengan **tarif per kategori kendaraan** (matic, sport, sedan, SUV, MPV, dll).
- Booking dengan slot waktu dan kapasitas bay/mekanik.
- Mode layanan: **datang ke bengkel**, **antar jemput kendaraan**, **servis di rumah** (bisa dibuka-tutup oleh owner).
- Work order, penugasan mekanik, status pengerjaan real-time.
- Gabungkan jasa servis dengan pembelian sparepart dalam satu invoice.

### Modul Sparepart
- Katalog produk dengan kompatibilitas kategori/model kendaraan.
- Manajemen stok per outlet, stok minimum, dan mutasi stok.
- Pesan antar atau ambil di toko.
- Opsi "beli + pasang" yang terhubung ke modul bengkel.

### Modul Doorsmeer
- Paket cuci (reguler, salju, cuci + vakum, detailing, dll) dengan **tarif per kategori kendaraan**.
- Pelanggan memilih: **cuci di tempat**, **antar jemput**, atau **cuci di rumah**.
- Antrean dan booking slot.
- Owner bisa mengaktifkan atau menonaktifkan setiap mode layanan.

### Antar Jemput & Layanan di Rumah
- Radius layanan dan tarif (biaya dasar + per km) diatur per outlet.
- Penugasan driver/petugas, status penjemputan, dan foto kondisi kendaraan (serah terima).

---

## Peran Pengguna

| Peran | Akses |
|---|---|
| **Superadmin** | Mengelola seluruh platform: verifikasi usaha, paket, komisi, master data, semua transaksi. |
| **Owner** | Mengelola usaha miliknya saja: outlet, modul, tarif, staf, laporan, pencairan dana. |
| **Staf usaha** | Kasir, mekanik, petugas cuci, driver. Akses dibatasi sesuai peran dan outlet. |
| **Customer** | Mendaftarkan kendaraan, booking, belanja sparepart, bayar, memberi ulasan. |

---

## Tech Stack

| Komponen | Teknologi |
|---|---|
| Framework | Laravel 13 (PHP 8.3+) |
| Database | PostgreSQL 16+ (dengan ekstensi PostGIS untuk lokasi) |
| Cache / Queue / Session / Lock | Redis 7+ |
| App server | Laravel Octane (FrankenPHP atau Swoole) |
| Queue monitor | Laravel Horizon |
| Auth API | Laravel Sanctum |
| Role & permission | spatie/laravel-permission (mode teams) |
| Panel admin & owner | Filament (versi yang kompatibel dengan Laravel 13) |
| Payment gateway | Tripay (closed payment) |
| Testing | Pest |

---

## Arsitektur Singkat

- **Multi-tenant shared database**: semua usaha berada dalam satu database PostgreSQL, data dipisahkan dengan kolom `business_id` dan global scope. Pendekatan ini dipilih karena pelanggan perlu mencari layanan lintas usaha (model marketplace).
- **Modular per domain**: kode dipisah per domain (Business, Catalog, Booking, Sparepart, CarWash, Payment, dll) di dalam `app/Domains`.
- **API-first**: aplikasi customer dan driver mengonsumsi REST API (`/api/v1`). Panel superadmin dan owner memakai Filament.
- **Octane-safe**: tidak ada state statis antar-request. Konteks tenant disimpan di binding `scoped` dan direset setiap request.

Detail lengkap ada di [`PLAN.md`](PLAN.md).

---

## Persyaratan Sistem

- PHP 8.3 atau lebih baru, dengan ekstensi `pdo_pgsql`, `redis`, `intl`, `bcmath`, `gd`, `zip` (dan `swoole` bila memakai Swoole)
- Composer 2
- Node.js 20+ dan npm
- PostgreSQL 16+ dengan PostGIS
- Redis 7+

---

## Instalasi Lokal

```bash
git clone https://github.com/<username>/oto-go.git
cd oto-go

composer install
npm install

cp .env.example .env
php artisan key:generate

# buat database dulu, lalu aktifkan PostGIS:
#   CREATE DATABASE otogo;
#   \c otogo
#   CREATE EXTENSION IF NOT EXISTS postgis;

php artisan migrate --seed
php artisan storage:link
npm run build
```

Seeder default membuat akun superadmin, master kategori kendaraan, 21 kecamatan Kota Medan, dan contoh tarif pasar Medan.

---

## Konfigurasi Environment

```dotenv
APP_NAME=OtoGo
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=otogo
DB_USERNAME=postgres
DB_PASSWORD=

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

OCTANE_SERVER=frankenphp

TRIPAY_MODE=sandbox
TRIPAY_MERCHANT_CODE=
TRIPAY_API_KEY=
TRIPAY_PRIVATE_KEY=

PLATFORM_DEFAULT_COMMISSION_PERCENT=5
```

---

## Menjalankan Aplikasi

```bash
# development (auto reload)
php artisan octane:start --watch

# worker queue
php artisan horizon

# scheduler (expire pembayaran, pengingat booking, dll)
php artisan schedule:work
```

Callback Tripay diarahkan ke `POST /api/v1/payments/tripay/callback`. Untuk uji lokal, gunakan tunnel (misalnya ngrok atau Cloudflare Tunnel) lalu daftarkan URL-nya di dashboard Tripay sandbox.

---

## Testing

```bash
php artisan test
# atau
./vendor/bin/pest --parallel
```

Wajib ada test untuk: isolasi data antar-tenant, toggle modul dan mode layanan, kalkulasi tarif, validasi signature callback Tripay, dan pencegahan double booking.

---

## Struktur Folder

```
app/
  Domains/
    Business/      # usaha, outlet, modul, pengaturan
    Catalog/       # kategori kendaraan, jasa, harga
    Workshop/      # bengkel: work order, mekanik
    Sparepart/     # produk, stok, pesanan
    CarWash/       # doorsmeer: paket, antrean
    Booking/       # booking, slot, mode layanan
    Logistics/     # antar jemput, layanan di rumah, driver
    Payment/       # invoice, Tripay, ledger, payout
    Subscription/  # paket langganan SaaS
    Customer/      # profil, kendaraan, alamat
  Http/
    Controllers/Api/V1/
  Support/
    Tenancy/       # TenantContext, scope, middleware
database/
  migrations/
  seeders/
tests/
PLAN.md            # rencana teknis lengkap
```

---

## Roadmap

- [ ] Fase 0: Setup project, Octane, Redis, PostgreSQL, CI
- [ ] Fase 1: Auth, peran, tenancy, registrasi usaha
- [ ] Fase 2: Master data, katalog, matriks tarif
- [ ] Fase 3: Booking bengkel dan doorsmeer
- [ ] Fase 4: Sparepart dan stok
- [ ] Fase 5: Pembayaran Tripay dan ledger
- [ ] Fase 6: Antar jemput dan layanan di rumah
- [ ] Fase 7: Langganan SaaS dan komisi
- [ ] Fase 8: Notifikasi, ulasan, laporan
- [ ] Fase 9: Hardening, performa, deploy produksi

---

## Lisensi

Proprietary. Hak cipta © Core Technology Indonesia. Seluruh hak dilindungi.
