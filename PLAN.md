# OtoGo — Project Plan

Dokumen ini adalah rencana teknis lengkap untuk membangun OtoGo. Ditulis agar bisa langsung dipakai oleh AI coding agent (Claude Code, Antigravity, dll) maupun developer.

## Cara Memakai Dokumen Ini (untuk AI Agent)

1. Baca seluruh dokumen sebelum menulis kode.
2. Kerjakan **per fase** sesuai urutan di bagian [Fase Pengerjaan](#11-fase-pengerjaan). Jangan lompat fase.
3. Setiap fase punya **kriteria selesai**. Fase dianggap selesai hanya jika semua kriteria terpenuhi dan `php artisan test` hijau.
4. Patuhi [Aturan Teknis Wajib](#0-aturan-teknis-wajib) di setiap baris kode.
5. Jika ada keputusan yang tidak diatur di dokumen ini, pilih opsi paling sederhana yang konsisten dengan arsitektur, lalu catat di `docs/DECISIONS.md`.
6. Commit kecil dan deskriptif per tugas (format: `feat(booking): ...`, `fix(payment): ...`).

---

## 0. Aturan Teknis Wajib

- **Laravel 13, PHP 8.3+**, PostgreSQL 16+ (PostGIS + btree_gist), Redis 7+, Octane.
- **Uang selalu integer rupiah** (`bigInteger`, tanpa desimal). Jangan pakai float. Nama kolom berakhiran `_amount` atau `price`.
- **Timestamp** pakai `timestampTz`. Zona waktu aplikasi `Asia/Jakarta` (WIB, Medan).
- **Status** memakai PHP backed enum (`app/Enums` atau `app/Domains/*/Enums`), disimpan sebagai string.
- **Tenancy**: setiap model milik usaha memakai trait `BelongsToBusiness` (global scope `business_id`). Query lintas tenant hanya boleh di konteks superadmin atau pencarian publik customer, dan harus eksplisit memakai `withoutGlobalScope`.
- **Octane-safe**: dilarang menyimpan state request di properti statis atau singleton. Konteks tenant dan user disimpan di binding `scoped`. Jangan inject `Request` ke constructor singleton.
- **Struktur kode**: Controller tipis → Form Request (validasi) → Action class (logika bisnis, satu aksi satu class) → Model. Otorisasi lewat Policy.
- **ID publik** memakai ULID (`$table->ulid('id')->primary()` atau kolom `public_id`) agar ID tidak bisa ditebak.
- **API** di bawah `/api/v1`, response konsisten memakai API Resource, error memakai format JSON standar.
- **Snapshot harga**: item booking dan pesanan menyimpan salinan nama dan harga saat transaksi, bukan referensi harga live.
- **Test wajib (Pest)** untuk setiap Action, Policy, dan endpoint. Gunakan factory dan database `RefreshDatabase` pada PostgreSQL (bukan SQLite, karena memakai fitur khusus Postgres).
- Semua teks UI berbahasa Indonesia. Kode, nama tabel, dan nama variabel berbahasa Inggris.

---

## 1. Ringkasan Produk

OtoGo adalah platform SaaS multi-usaha (marketplace) untuk perawatan kendaraan:

- **Usaha** (bengkel, toko sparepart, doorsmeer) mendaftar, membuat outlet, memilih modul, mengatur tarif, dan menerima pembayaran.
- **Customer** mencari layanan terdekat, booking, belanja sparepart, membayar online lewat Tripay atau bayar di tempat (jika diizinkan owner).
- **Superadmin** mengelola seluruh platform, paket langganan, komisi, dan verifikasi usaha.

Tiga modul usaha:

| Kode modul | Nama | Isi |
|---|---|---|
| `workshop` | Bengkel | Jasa servis, booking, work order, mekanik |
| `sparepart` | Sparepart | Produk, stok, pesanan, beli + pasang |
| `carwash` | Doorsmeer | Paket cuci, antrean, booking |

Tiga mode layanan (berlaku untuk `workshop` dan `carwash`):

| Kode mode | Arti |
|---|---|
| `on_site` | Customer datang ke outlet |
| `pickup_delivery` | Kendaraan dijemput dari alamat customer, dikerjakan di outlet, lalu diantar kembali |
| `home_service` | Petugas datang dan mengerjakan di alamat customer |

Untuk `sparepart`, mode pemenuhan pesanan: `store_pickup` (ambil di toko) dan `store_delivery` (diantar kurir toko).

---

## 2. Keputusan Arsitektur

### 2.1 Multi-tenancy: shared database + `business_id`

Dipilih karena customer harus bisa mencari dan membandingkan layanan lintas usaha. Database-per-tenant akan menyulitkan pencarian, laporan platform, dan pembayaran terpusat.

Komponen:

- `App\Support\Tenancy\TenantContext` — menyimpan `business_id` dan `outlet_id` aktif. Didaftarkan sebagai `$this->app->scoped(TenantContext::class)`.
- Middleware `ResolveTenant` — mengisi `TenantContext` dari user owner/staf yang login atau dari route panel Filament.
- Trait `BelongsToBusiness` — menambahkan global scope dan otomatis mengisi `business_id` saat create.
- Opsional tahap lanjut: PostgreSQL Row Level Security sebagai lapisan pengaman kedua.

### 2.2 Panel dan aplikasi

| Pengguna | Antarmuka |
|---|---|
| Superadmin | Filament panel `/admin` |
| Owner dan staf | Filament panel `/business` dengan fitur tenancy Filament (tenant = Business) |
| Customer | REST API `/api/v1` (untuk aplikasi mobile atau web) |
| Driver / mekanik lapangan | REST API `/api/v1/staff` |

### 2.3 Redis dipakai untuk

- Cache (master data, tarif, daftar outlet terdekat)
- Queue + Horizon (notifikasi, pemrosesan callback, laporan)
- Session
- Rate limiting (login, OTP, pembuatan transaksi)
- **Atomic lock** saat membuat booking agar slot tidak dobel (`Cache::lock("slot:{resource}:{start}")`)

### 2.4 Lokasi

- PostGIS kolom `geography(Point, 4326)` di `outlets` dan `customer_addresses`.
- Jarak dihitung dengan `ST_Distance`, pencarian outlet terdekat dengan `ST_DWithin`.
- Radius layanan antar jemput dan home service dicek di server, bukan hanya di aplikasi.

### 2.5 Pencegahan double booking

- Lapisan 1: Redis lock saat proses booking.
- Lapisan 2: constraint PostgreSQL `EXCLUDE USING gist (resource_id WITH =, time_range WITH &&)` pada tabel `resource_reservations` (butuh ekstensi `btree_gist`).

---

## 3. Peran dan Permission

Pakai `spatie/laravel-permission` dengan fitur **teams** (`team_id` = `business_id`).

| Peran | Lingkup | Kemampuan utama |
|---|---|---|
| `superadmin` | Platform | Semua akses, verifikasi usaha, paket, komisi, master data, payout |
| `platform_staff` | Platform | Verifikasi usaha, bantuan customer, lihat transaksi (tanpa ubah komisi) |
| `owner` | Usaha | Semua pengaturan usahanya: outlet, modul, mode layanan, tarif, staf, laporan, pencairan |
| `manager` | Usaha / outlet | Kelola booking, stok, staf outlet |
| `cashier` | Outlet | Terima pembayaran di tempat, buat transaksi walk-in |
| `mechanic` | Outlet | Lihat dan update work order yang ditugaskan |
| `washer` | Outlet | Lihat dan update antrean cuci yang ditugaskan |
| `driver` | Outlet | Tugas jemput/antar dan home service |
| `customer` | Pribadi | Kendaraan, alamat, booking, pesanan, ulasan |

Satu user bisa menjadi customer sekaligus staf usaha.

---

## 4. Modul dan Fitur

### 4.1 Business (Usaha)

- Registrasi usaha: nama, jenis usaha, NIB (opsional), NPWP (opsional), KTP pemilik, rekening pencairan, foto usaha.
- Status usaha: `draft` → `pending_review` → `active` / `rejected` → `suspended`.
- Outlet: alamat, titik peta, jam operasional per hari, hari libur, foto, kontak.
- Staf: undang via nomor HP/email, tetapkan peran dan outlet.

### 4.2 Modul dan Toggle Fitur

Aturan efektif suatu fitur aktif (semua harus `true`):

```
platform_modules.is_enabled          (superadmin, global)
AND subscription aktif mencakup modul (atau masa trial)
AND business_modules.is_enabled       (owner)
AND outlet_modules.is_enabled         (owner, per outlet)
AND outlet_module_settings.<mode>     (owner, per mode layanan)
```

Pengaturan yang bisa dibuka-tutup owner per outlet per modul (`outlet_module_settings`):

| Field | Default | Keterangan |
|---|---|---|
| `on_site_enabled` | true | Booking datang ke outlet |
| `walk_in_enabled` | true | Terima customer tanpa booking |
| `pickup_delivery_enabled` | false | Antar jemput |
| `home_service_enabled` | false | Dikerjakan di rumah customer |
| `online_payment_enabled` | true | Bayar via Tripay |
| `pay_at_location_enabled` | true | Bayar di tempat (tunai/transfer langsung) |
| `require_prepayment` | false | Wajib bayar sebelum booking terkonfirmasi |
| `auto_confirm_booking` | true | Booking otomatis terkonfirmasi tanpa persetujuan |
| `min_lead_minutes` | 60 | Minimal jarak waktu pemesanan dari sekarang |
| `max_days_ahead` | 14 | Maksimal hari ke depan untuk booking |
| `slot_interval_minutes` | 30 | Interval slot booking |
| `cancellation_cutoff_minutes` | 120 | Batas pembatalan gratis |

Untuk modul `sparepart`: `store_pickup_enabled`, `store_delivery_enabled`, `install_service_enabled` (beli + pasang, butuh modul `workshop` aktif).

### 4.3 Master Kendaraan dan Kategori

Dikelola superadmin, berlaku seluruh platform.

`vehicle_types`: `motor`, `mobil`.

`vehicle_categories`:

| Tipe | Kode | Nama |
|---|---|---|
| motor | `motor_matic` | Motor Matic |
| motor | `motor_bebek` | Motor Bebek |
| motor | `motor_sport` | Motor Sport / Naked (≤250cc) |
| motor | `motor_big` | Motor Besar (>250cc) |
| mobil | `car_city` | City Car / Hatchback |
| mobil | `car_sedan` | Sedan |
| mobil | `car_mpv` | MPV (Avanza, Xenia, Ertiga, dll) |
| mobil | `car_suv` | SUV (Fortuner, Pajero, CR-V, dll) |
| mobil | `car_pickup` | Pickup / Double Cabin |
| mobil | `car_van` | Minibus / Van (HiAce, Elf, dll) |
| mobil | `car_premium` | Premium / Eropa / Mewah |

`vehicle_brands` dan `vehicle_models` (setiap model punya `default_category_id`). Customer bisa memilih model, kategori terisi otomatis, tetapi owner bisa mengoreksi kategori saat check-in.

### 4.4 Katalog Jasa dan Matriks Tarif

Berlaku untuk `workshop` dan `carwash`.

- `service_categories` per modul (contoh workshop: Servis Berkala, Mesin, Rem, Kaki-kaki, Kelistrikan, AC; carwash: Cuci, Interior, Detailing, Coating).
- `services` milik usaha: nama, deskripsi, modul, kategori, foto, aktif/tidak, mode layanan yang didukung (`supported_modes` jsonb, contoh: `["on_site","home_service"]`).
- `service_prices`: harga dan estimasi durasi **per kategori kendaraan**. Jika baris harga untuk kategori tertentu tidak ada, jasa tidak tersedia untuk kategori tersebut.
- Opsi `outlet_service_prices` untuk menimpa harga di outlet tertentu (opsional, fase lanjut).
- Owner bisa menyalin tarif default Medan dari template platform saat onboarding.

### 4.5 Modul Bengkel (Workshop)

- Booking jasa servis (lihat 4.8).
- Work order: dibuat dari booking atau walk-in. Berisi keluhan, hasil pemeriksaan, jasa, sparepart terpakai, mekanik, foto sebelum/sesudah.
- Tambahan pekerjaan saat pengerjaan harus disetujui customer di aplikasi sebelum dikerjakan.
- Sparepart yang dipakai otomatis mengurangi stok (jika modul sparepart aktif) atau dicatat sebagai item manual.
- Garansi servis (jumlah hari) per jasa, tercatat di riwayat kendaraan.

### 4.6 Modul Sparepart

- `products`: SKU, nama, merek, kategori produk, satuan, harga jual, harga modal (hanya owner), berat, foto.
- `product_compatibilities`: produk ↔ `vehicle_model` atau `vehicle_category`.
- `inventory_items`: stok per outlet, stok minimum, lokasi rak.
- `stock_movements`: masuk, keluar, penyesuaian, transfer antar outlet, terpakai di work order. Stok tidak boleh diubah langsung tanpa movement.
- Pesanan: keranjang per outlet (satu pesanan = satu outlet), mode `store_pickup` / `store_delivery`.
- Stok dipesan (reserved) saat checkout, dilepas jika pembayaran kedaluwarsa.
- Beli + pasang: membuat pesanan sparepart sekaligus booking workshop dalam satu invoice.

### 4.7 Modul Doorsmeer (CarWash)

- Paket cuci memakai katalog jasa (4.4) dengan modul `carwash`.
- Add-on (vakum interior, cuci mesin, cuci kolong, wax) sebagai jasa dengan flag `is_addon`.
- Antrean: walk-in masuk antrean, booking masuk slot. Layar antrean di outlet (halaman Filament sederhana).
- Customer memilih mode: `on_site`, `pickup_delivery`, atau `home_service` sesuai yang dibuka owner.
- Home service membutuhkan info tambahan: ketersediaan air dan listrik di lokasi customer (checkbox), jenis lahan parkir.

### 4.8 Booking dan Slot

- `outlet_resources`: sumber daya terbatas per outlet dan modul (bay bengkel, bay cuci, tim home service, driver). Masing-masing punya kapasitas paralel = 1.
- Ketersediaan slot dihitung dari jam operasional, durasi jasa (dari `service_prices`), dan reservasi yang sudah ada.
- Pembuatan booking (Action `CreateBooking`):
  1. Validasi modul, mode, dan kategori kendaraan aktif.
  2. Validasi radius (untuk `pickup_delivery` / `home_service`).
  3. Hitung harga (lihat bagian 8).
  4. Ambil Redis lock slot, buat `resource_reservations`, lepas lock.
  5. Buat invoice jika pembayaran online atau `require_prepayment`.
- Booking yang belum dibayar dalam batas waktu (default 30 menit) otomatis dibatalkan oleh scheduler dan slot dilepas.

### 4.9 Logistik: Antar Jemput dan Home Service

- `outlet_logistics_rates` per outlet, modul, mode, tipe kendaraan: `base_fee`, `included_km`, `per_km_fee`, `max_radius_km`.
- Tugas logistik (`logistics_tasks`): `pickup`, `return_delivery`, `home_visit`. Ditugaskan ke driver/petugas.
- Serah terima: foto kondisi kendaraan (minimal 4 sisi), odometer, level bensin, tanda tangan/konfirmasi customer di aplikasi.
- Lokasi driver dikirim berkala ke API dan disimpan sementara di Redis (bukan database) untuk ditampilkan ke customer.

### 4.10 Pembayaran (Tripay)

- Platform bertindak sebagai merchant Tripay tunggal; dana customer masuk ke platform, lalu dicatat sebagai saldo usaha di ledger.
- Mode transaksi: **closed payment** (nominal pasti).
- Siapa menanggung biaya Tripay diatur superadmin: `customer`, `merchant` (usaha), atau `platform`.
- Komisi platform dipotong saat transaksi dinyatakan `PAID`.
- Bayar di tempat: dicatat kasir; komisinya menjadi piutang usaha yang dipotong dari saldo berikutnya atau ditagihkan bulanan.
- Pencairan (payout): owner mengajukan, superadmin memproses transfer ke rekening usaha, status dicatat. Otomatisasi payout lewat API disbursement dilakukan di fase lanjut setelah provider dan legalitasnya dikonfirmasi.

Detail alur di bagian 7.

### 4.11 Langganan SaaS

- `plans`: kode, nama, modul yang termasuk, harga bulanan dan tahunan, maksimal outlet, maksimal staf, persentase komisi.
- Trial 14 hari untuk usaha baru, semua modul.
- Tagihan langganan dibayar lewat Tripay (invoice dengan `payable` = subscription).
- Masa tenggang 7 hari setelah jatuh tempo; setelah itu modul berbayar dinonaktifkan otomatis (data tetap tersimpan).

### 4.12 Notifikasi

- Kanal: database (in-app), email, push notification (FCM), WhatsApp (provider lokal, ditentukan kemudian, dibungkus interface `WhatsAppGateway` agar bisa diganti).
- Event utama: booking dibuat, terkonfirmasi, pengingat H-1 dan 1 jam sebelum, status pengerjaan berubah, driver berangkat/tiba, pembayaran berhasil/kedaluwarsa, stok menipis (owner), langganan akan habis (owner).

### 4.13 Ulasan

- Customer memberi rating 1–5 dan komentar setelah booking/pesanan `completed`.
- Owner bisa membalas sekali. Superadmin bisa menyembunyikan ulasan yang melanggar.
- Rata-rata rating outlet di-cache dan diperbarui lewat queue.

### 4.14 Laporan

- Owner: pendapatan per modul/outlet/periode, jasa terlaris, utilisasi bay, kinerja mekanik/washer, nilai stok, produk menipis, saldo dan riwayat payout.
- Superadmin: GMV, komisi, usaha aktif, pertumbuhan customer, transaksi per metode pembayaran, langganan jatuh tempo.

---

## 5. Skema Database

Kolom standar di semua tabel: `id`, `created_at`, `updated_at` (timestampTz). Tabel milik usaha punya `business_id` (indexed). Soft delete untuk master data penting.

### Platform & Identitas
- `users` — name, email (nullable, unique), phone (unique), password, phone_verified_at, avatar
- `platform_modules` — code (`workshop`/`sparepart`/`carwash`), name, is_enabled
- `platform_settings` — key, value (jsonb)
- `service_areas` — city, district (kecamatan), polygon (geography, nullable), is_active

### Usaha
- `businesses` — owner_id, name, slug, business_types (jsonb), status, legal docs, bank_name, bank_account_no, bank_account_name, verified_at, commission_percent_override
- `business_modules` — business_id, module_code, is_enabled
- `outlets` — business_id, name, address, district, city, location (geography Point), phone, is_active
- `outlet_opening_hours` — outlet_id, day_of_week, opens_at, closes_at, is_closed
- `outlet_holidays` — outlet_id, date, note
- `outlet_modules` — outlet_id, module_code, is_enabled
- `outlet_module_settings` — outlet_id, module_code, kolom-kolom toggle di 4.2
- `outlet_logistics_rates` — outlet_id, module_code, mode, vehicle_type, base_fee, included_km, per_km_fee, max_radius_km
- `outlet_resources` — outlet_id, module_code, type (`bay`/`wash_bay`/`home_team`/`driver`), name, is_active
- `business_members` — business_id, user_id, outlet_id (nullable = semua outlet), status

### Kendaraan & Customer
- `vehicle_types`, `vehicle_categories`, `vehicle_brands`, `vehicle_models`
- `customer_vehicles` — user_id, vehicle_model_id, vehicle_category_id, plate_number, year, color, nickname
- `customer_addresses` — user_id, label, address, district, location (geography Point), notes

### Katalog
- `service_categories` — module_code, name, sort
- `services` — business_id, module_code, service_category_id, name, description, supported_modes (jsonb), is_addon, warranty_days, is_active
- `service_prices` — service_id, vehicle_category_id, price, duration_minutes (unique: service_id + vehicle_category_id)
- `price_templates` + `price_template_items` — template tarif default dari superadmin (contoh: "Tarif Pasar Medan 2026")

### Sparepart
- `product_categories`
- `products` — business_id, sku, name, brand, product_category_id, unit, price, cost_price, weight_gram, is_active
- `product_compatibilities` — product_id, vehicle_model_id (nullable), vehicle_category_id (nullable)
- `inventory_items` — outlet_id, product_id, quantity, reserved_quantity, min_quantity
- `stock_movements` — inventory_item_id, type, quantity (positif/negatif), reference_type, reference_id, note, user_id

### Transaksi
- `bookings` — public_id (ULID), business_id, outlet_id, customer_id, customer_vehicle_id, vehicle_category_id (snapshot), module_code, service_mode, scheduled_start, scheduled_end, customer_address_id, distance_km, status, payment_method_type (`online`/`pay_at_location`), subtotal, logistics_fee, discount_amount, platform_fee, total_amount, notes, cancelled_reason
- `booking_items` — booking_id, service_id, name_snapshot, price_snapshot, duration_minutes, quantity
- `booking_status_histories` — booking_id, from_status, to_status, user_id, note
- `resource_reservations` — outlet_resource_id, booking_id, time_range (tstzrange) + EXCLUDE constraint
- `work_orders` — booking_id (nullable), business_id, outlet_id, mechanic_id, complaint, inspection_notes, status
- `work_order_items` — work_order_id, type (`service`/`part`), reference_id, name_snapshot, price_snapshot, quantity, approved_by_customer_at
- `orders` — public_id, business_id, outlet_id, customer_id, fulfillment_mode, customer_address_id, status, subtotal, delivery_fee, platform_fee, total_amount
- `order_items` — order_id, product_id, name_snapshot, price_snapshot, quantity
- `logistics_tasks` — business_id, outlet_id, booking_id / order_id, type, assigned_to, status, scheduled_at, started_at, completed_at
- `handover_records` — logistics_task_id, photos (jsonb), odometer, fuel_level, notes, confirmed_by_customer_at

### Pembayaran & Keuangan
- `invoices` — public_id, number (unik, contoh `OTG-20261031-000123`), payable_type, payable_id, business_id (nullable untuk tagihan langganan), customer_id, amount, fee_bearer, status, due_at, paid_at
- `payment_transactions` — invoice_id, provider (`tripay`), merchant_ref, provider_reference, method, amount, fee_merchant, fee_customer, total_paid, status, checkout_url, pay_code, expired_at, raw_response (jsonb)
- `payment_callbacks` — provider, event, signature_valid, payload (jsonb), processed_at, error
- `ledger_accounts` — owner_type (`platform`/`business`), owner_id, type (`available`/`pending`/`receivable`)
- `ledger_entries` — ledger_account_id, amount (positif kredit / negatif debit), balance_after, reference_type, reference_id, description
- `payouts` — business_id, amount, bank snapshot, status, processed_by, processed_at, proof_file

### Langganan
- `plans` — code, name, modules (jsonb), monthly_price, yearly_price, max_outlets, max_staff, commission_percent
- `business_subscriptions` — business_id, plan_id, status, trial_ends_at, current_period_start, current_period_end, grace_ends_at

### Lainnya
- `reviews` — reviewable_type, reviewable_id, outlet_id, user_id, rating, comment, reply, replied_at, is_hidden
- `notifications` (bawaan Laravel)
- `activity_logs` — siapa mengubah apa (tarif, toggle, stok, status)

---

## 6. Status (State Machine)

Implementasikan transisi yang diizinkan di dalam enum (method `canTransitionTo()`), dan setiap perubahan dicatat di tabel history.

**Booking**
```
pending_payment → confirmed → (pickup_assigned → picked_up →) checked_in → in_progress
→ ready → (delivering →) completed
pending_payment → expired
pending_payment | confirmed → cancelled
completed → refunded (oleh superadmin)
```
Untuk `home_service`: `confirmed → on_the_way → arrived → in_progress → completed`.
Jika `auto_confirm_booking = false`: `pending_approval` berada di antara pembuatan dan `confirmed`.

**Order sparepart**
```
pending_payment → paid → processing → ready_for_pickup | out_for_delivery → completed
pending_payment → expired | cancelled
```

**Invoice / pembayaran** (mengikuti status Tripay): `UNPAID`, `PAID`, `EXPIRED`, `FAILED`, `REFUND`.

**Business**: `draft → pending_review → active | rejected`, `active ↔ suspended`.

---

## 7. Alur Pembayaran Tripay

Konfigurasi di `config/tripay.php` dari env: `TRIPAY_MODE` (`sandbox`/`production`), `TRIPAY_MERCHANT_CODE`, `TRIPAY_API_KEY`, `TRIPAY_PRIVATE_KEY`. Base URL sandbox dan produksi diambil dari dokumentasi resmi Tripay (tripay.co.id/developer). Bungkus semua akses dalam `App\Domains\Payment\Gateways\TripayGateway` yang mengimplementasikan interface `PaymentGateway`.

### 7.1 Membuat transaksi
1. Customer checkout → buat `invoice` (status `UNPAID`).
2. Ambil daftar channel pembayaran aktif (cache Redis 1 jam).
3. Hitung biaya channel (endpoint fee calculator) dan tentukan nominal sesuai `fee_bearer`.
4. Buat closed transaction: `merchant_ref` = nomor invoice, `amount`, `order_items`, `customer_*`, `callback_url`, `return_url`, `expired_time`, dan `signature` = HMAC-SHA256 dari `merchant_code + merchant_ref + amount` dengan private key.
5. Simpan `payment_transactions` (reference, pay_code, checkout_url, expired_at). Kirim ke customer.

### 7.2 Callback
- Route: `POST /api/v1/payments/tripay/callback` (tanpa CSRF, tanpa auth, dengan rate limit).
- Validasi header `X-Callback-Signature` = HMAC-SHA256 dari raw body dengan private key (bandingkan memakai `hash_equals`). Header `X-Callback-Event` harus `payment_status`.
- Simpan payload ke `payment_callbacks` lebih dulu, lalu proses lewat job queue.
- **Idempoten**: jika invoice sudah `PAID`, abaikan callback ganda. Gunakan `SELECT ... FOR UPDATE` pada invoice.
- Cocokkan nominal dan `merchant_ref`. Untuk keamanan tambahan, panggil API detail transaksi sebelum menandai lunas.
- Saat `PAID`: update invoice → update booking/order/subscription → catat ledger (saldo usaha `pending`, komisi ke akun platform) → kirim notifikasi.
- Balas `{"success": true}` agar Tripay tidak mengirim ulang.

### 7.3 Ledger
- Dana masuk ke akun `pending` milik usaha.
- Setelah booking/order `completed` (atau H+1 setelah selesai, untuk antisipasi komplain), dana pindah dari `pending` ke `available`.
- Payout hanya dari saldo `available`.
- Refund mengurangi saldo usaha dan mengembalikan komisi platform secara proporsional.

### 7.4 Scheduler terkait pembayaran
- Setiap 5 menit: tandai invoice kedaluwarsa, batalkan booking/order terkait, lepas slot dan stok reserved.
- Setiap jam: rekonsiliasi transaksi `UNPAID` yang sudah lewat waktu dengan API detail Tripay.
- Harian: pindahkan saldo `pending` → `available` yang sudah memenuhi syarat.

---

## 8. Kalkulasi Harga

Implementasikan di `App\Domains\Booking\Actions\CalculateBookingPrice` (pure, mudah dites).

```
subtotal       = Σ service_prices.price untuk kategori kendaraan customer (+ add-on)
jarak_km       = ST_Distance(outlet.location, address.location) / 1000, dibulatkan ke atas 1 desimal
logistics_fee  = 0 untuk on_site
               = base_fee + max(0, ceil(jarak_km - included_km)) × per_km_fee
                 untuk pickup_delivery dan home_service (tolak jika jarak_km > max_radius_km)
discount       = dari voucher (fase lanjut), default 0
platform_fee   = biaya layanan ke customer (opsional, setting superadmin, default 0)
total          = subtotal + logistics_fee - discount + platform_fee
komisi_platform= round(subtotal × commission_percent / 100)   ← dipotong dari usaha, tidak dibebankan ke customer
```

Semua hasil dalam rupiah bulat. Pembulatan memakai `intdiv`/`round` integer, bukan float.

---

## 9. Tarif Default Medan (Seeder Template)

Angka di bawah adalah **estimasi kisaran harga pasar Medan** sebagai template awal, bukan harga resmi. Owner wajib meninjau dan menyesuaikan sebelum outlet aktif. Disarankan survei singkat ke beberapa usaha di Medan sebelum rilis untuk memperbarui template ini.

Seeder: `MedanPriceTemplateSeeder` membuat `price_templates` bernama "Tarif Pasar Medan 2026".

### 9.1 Doorsmeer — Cuci Reguler (Cuci Salju)

| Kategori | Harga |
|---|---|
| Motor Matic / Bebek | Rp 15.000 |
| Motor Sport | Rp 20.000 |
| Motor Besar | Rp 35.000 |
| City Car / Hatchback | Rp 35.000 |
| Sedan | Rp 40.000 |
| MPV | Rp 45.000 |
| SUV | Rp 50.000 |
| Pickup / Double Cabin | Rp 55.000 |
| Minibus / Van | Rp 65.000 |
| Premium | Rp 75.000 |

### 9.2 Doorsmeer — Add-on dan Paket Lain (mobil, harga untuk MPV; kategori lain ±Rp 5.000–15.000)

| Layanan | Harga |
|---|---|
| Cuci + vakum interior | Rp 60.000 |
| Cuci kolong / underbody | Rp 30.000 |
| Cuci mesin (engine) | Rp 50.000 |
| Wax / semir body | Rp 50.000 |
| Fogging / pengharum kabin | Rp 50.000 |
| Salon interior (jok, plafon, karpet) | Rp 350.000 |
| Poles body / detailing exterior | Rp 400.000 |

### 9.3 Bengkel Motor — Jasa (belum termasuk sparepart/oli)

| Jasa | Matic / Bebek | Sport | Motor Besar |
|---|---|---|---|
| Servis ringan / tune-up | Rp 60.000 | Rp 80.000 | Rp 200.000 |
| Ganti oli (jasa) | Rp 10.000 | Rp 10.000 | Rp 30.000 |
| Servis CVT (matic) | Rp 60.000 | — | — |
| Ganti kampas rem (per roda) | Rp 20.000 | Rp 25.000 | Rp 50.000 |
| Setel / ganti rantai | Rp 25.000 | Rp 30.000 | Rp 60.000 |
| Servis injeksi / throttle body | Rp 50.000 | Rp 60.000 | Rp 150.000 |
| Cek kelistrikan | Rp 30.000 | Rp 35.000 | Rp 75.000 |

### 9.4 Bengkel Mobil — Jasa (belum termasuk sparepart/oli)

| Jasa | City Car | Sedan / MPV | SUV / Pickup | Van / Premium |
|---|---|---|---|---|
| Servis berkala ringan | Rp 150.000 | Rp 200.000 | Rp 250.000 | Rp 350.000 |
| Ganti oli (jasa) | Rp 25.000 | Rp 30.000 | Rp 35.000 | Rp 50.000 |
| Tune-up | Rp 250.000 | Rp 300.000 | Rp 350.000 | Rp 450.000 |
| Spooring | Rp 100.000 | Rp 120.000 | Rp 150.000 | Rp 200.000 |
| Balancing 4 roda | Rp 80.000 | Rp 100.000 | Rp 120.000 | Rp 160.000 |
| Servis rem 2 roda | Rp 100.000 | Rp 120.000 | Rp 150.000 | Rp 200.000 |
| Kuras radiator | Rp 75.000 | Rp 85.000 | Rp 100.000 | Rp 125.000 |
| Servis AC (cuci evaporator) | Rp 300.000 | Rp 350.000 | Rp 400.000 | Rp 500.000 |
| Scan OBD / diagnosa | Rp 100.000 | Rp 100.000 | Rp 125.000 | Rp 150.000 |

### 9.5 Sparepart — Contoh Produk Seed (harga generik, tanpa merek)

| Produk | Harga |
|---|---|
| Oli motor matic 0,8 L | Rp 55.000 |
| Oli motor 1 L | Rp 60.000 |
| Oli gardan matic 120 ml | Rp 18.000 |
| Busi motor | Rp 25.000 |
| Kampas rem motor (set) | Rp 50.000 |
| V-belt matic | Rp 120.000 |
| Aki motor kering | Rp 250.000 |
| Oli mobil 4 L | Rp 300.000 |
| Filter oli mobil | Rp 50.000 |
| Filter udara mobil | Rp 90.000 |
| Kampas rem depan mobil (set) | Rp 250.000 |
| Wiper blade (sepasang) | Rp 100.000 |
| Aki mobil NS40 | Rp 800.000 |
| Air radiator coolant 1 L | Rp 35.000 |

### 9.6 Tarif Logistik Default (per outlet)

| Mode | Tipe | Biaya dasar | Termasuk | Per km berikutnya | Radius maks |
|---|---|---|---|---|---|
| Antar jemput | Motor | Rp 15.000 | 3 km | Rp 3.000 | 10 km |
| Antar jemput | Mobil | Rp 40.000 | 3 km | Rp 5.000 | 15 km |
| Home service | Motor | Rp 20.000 | 3 km | Rp 3.000 | 10 km |
| Home service | Mobil | Rp 35.000 | 3 km | Rp 4.000 | 10 km |

Biaya antar jemput mencakup perjalanan pergi dan pulang.

### 9.7 Wilayah Layanan Awal (21 Kecamatan Kota Medan)

Medan Amplas, Medan Area, Medan Barat, Medan Baru, Medan Belawan, Medan Deli, Medan Denai, Medan Helvetia, Medan Johor, Medan Kota, Medan Labuhan, Medan Maimun, Medan Marelan, Medan Perjuangan, Medan Petisah, Medan Polonia, Medan Selayang, Medan Sunggal, Medan Tembung, Medan Timur, Medan Tuntungan.

### 9.8 Paket Langganan Usaha (usulan awal, bisa diubah superadmin)

| Paket | Modul | Harga / bulan | Outlet | Komisi transaksi online |
|---|---|---|---|---|
| Trial | Semua | Gratis 14 hari | 1 | 5% |
| Starter | 1 modul pilihan | Rp 99.000 | 1 | 5% |
| Duo | 2 modul pilihan | Rp 179.000 | 2 | 4% |
| Lengkap | Semua modul | Rp 249.000 | 3 | 3% |
| Bisnis | Semua modul | Rp 499.000 | 10 | 2,5% |

Diskon tahunan 2 bulan gratis.

---

## 10. Ringkasan API (Customer & Staf)

Semua di bawah `/api/v1`, auth Sanctum token kecuali yang ditandai publik.

**Auth**
- `POST /auth/register`, `POST /auth/login`, `POST /auth/otp/send`, `POST /auth/otp/verify`, `POST /auth/logout`, `GET /me`

**Master (publik, di-cache)**
- `GET /vehicle-types`, `GET /vehicle-categories`, `GET /vehicle-brands`, `GET /vehicle-models?brand=`

**Customer**
- `GET|POST|PUT|DELETE /me/vehicles`
- `GET|POST|PUT|DELETE /me/addresses`

**Pencarian (publik)**
- `GET /outlets?lat=&lng=&module=&mode=&vehicle_category=&radius=`
- `GET /outlets/{outlet}` (detail, modul, mode yang aktif, rating)
- `GET /outlets/{outlet}/services?module=&vehicle_category=`
- `GET /outlets/{outlet}/products?q=&vehicle_model=`
- `GET /outlets/{outlet}/availability?service_ids[]=&vehicle_category=&mode=&date=`

**Booking**
- `POST /bookings/quote` (hitung harga tanpa menyimpan)
- `POST /bookings`, `GET /bookings`, `GET /bookings/{id}`, `POST /bookings/{id}/cancel`
- `POST /bookings/{id}/work-order-items/{item}/approve`
- `GET /bookings/{id}/tracking` (lokasi driver dari Redis)

**Sparepart**
- `POST /orders/quote`, `POST /orders`, `GET /orders`, `GET /orders/{id}`, `POST /orders/{id}/cancel`

**Pembayaran**
- `GET /payments/channels`
- `POST /invoices/{invoice}/pay` (pilih channel, buat transaksi Tripay)
- `GET /invoices/{invoice}`
- `POST /payments/tripay/callback` (publik, divalidasi signature)

**Ulasan**
- `POST /reviews`

**Staf (driver/mekanik/washer)**
- `GET /staff/tasks`, `POST /staff/tasks/{task}/status`, `POST /staff/tasks/{task}/handover`, `POST /staff/location`
- `GET /staff/work-orders`, `POST /staff/work-orders/{wo}/items`, `POST /staff/work-orders/{wo}/status`

---

## 11. Fase Pengerjaan

### Fase 0 — Fondasi Project
Tugas:
- `laravel new oto-go`, PHP 8.3+, set timezone dan locale `id`.
- Konfigurasi PostgreSQL, aktifkan `postgis` dan `btree_gist` lewat migration pertama.
- Redis untuk cache, queue, session. Pasang Horizon.
- Pasang Octane (FrankenPHP default). Tambahkan `docker-compose.yml`: app, horizon, scheduler, postgres+postgis, redis.
- Pasang Pest, Larastan (level 6+), Laravel Pint. GitHub Actions: pint, larastan, pest dengan service Postgres + Redis.
- Buat kerangka folder `app/Domains/*` dan `app/Support/Tenancy`.

Kriteria selesai: `docker compose up` menjalankan app via Octane, `/up` sehat, CI hijau.

### Fase 1 — Auth, Peran, Tenancy, Usaha
Tugas:
- Sanctum, registrasi/login customer (email atau nomor HP + OTP; OTP disimpan di Redis dengan TTL dan rate limit).
- spatie/laravel-permission mode teams, seed peran di bagian 3.
- `TenantContext` (scoped), middleware `ResolveTenant`, trait `BelongsToBusiness`.
- Filament panel `/admin` (superadmin) dan `/business` (owner, tenancy Filament = Business).
- CRUD usaha, outlet, jam operasional, staf. Alur verifikasi usaha oleh superadmin.
- Master `platform_modules`, `business_modules`, `outlet_modules`, `outlet_module_settings`.
- Service `ModuleGate::allows($outlet, $module, $mode = null)` sesuai aturan 4.2.

Kriteria selesai:
- Test membuktikan owner usaha A tidak bisa membaca/mengubah data usaha B (model, API, dan panel).
- Test toggle: mematikan modul atau mode di tingkat mana pun membuat `ModuleGate` mengembalikan `false`.
- Test Octane: dua request berurutan dari tenant berbeda tidak bocor konteks (`TenantContext` reset).

### Fase 2 — Master Data, Katalog, Tarif
Tugas:
- Seeder: tipe dan kategori kendaraan, merek dan model populer di Indonesia, 21 kecamatan Medan, template tarif Medan (bagian 9).
- CRUD `service_categories`, `services`, `service_prices` (UI matriks: baris = jasa, kolom = kategori kendaraan).
- Fitur "Salin dari template tarif" saat onboarding.
- Endpoint master dan katalog publik dengan cache Redis + invalidasi saat data berubah.

Kriteria selesai: owner bisa membuat jasa dengan harga berbeda per kategori; jasa tanpa harga untuk suatu kategori tidak muncul di API untuk kategori itu.

### Fase 3 — Booking Bengkel dan Doorsmeer (mode on_site)
Tugas:
- `outlet_resources`, kalkulasi slot ketersediaan.
- `CalculateBookingPrice`, `CreateBooking` dengan Redis lock + EXCLUDE constraint.
- State machine booking + history.
- Work order bengkel, persetujuan item tambahan oleh customer.
- Antrean doorsmeer (walk-in + booking) dan layar antrean.
- Pembatalan dan kebijakan cutoff.

Kriteria selesai:
- Test konkurensi: dua permintaan booking slot yang sama secara paralel, hanya satu berhasil.
- Test harga sesuai kategori kendaraan.
- Booking tidak bisa dibuat untuk modul/mode yang dimatikan.

### Fase 4 — Sparepart dan Stok
Tugas:
- Produk, kompatibilitas, stok per outlet, stock movement.
- Keranjang, quote, order, reservasi stok, pelepasan stok saat batal/kedaluwarsa.
- Integrasi pemakaian sparepart di work order.
- Notifikasi stok menipis.

Kriteria selesai: stok selalu sama dengan jumlah movement; tidak bisa checkout melebihi stok tersedia (quantity − reserved).

### Fase 5 — Pembayaran Tripay dan Ledger
Tugas:
- Interface `PaymentGateway`, implementasi `TripayGateway` (channel, fee calculator, create closed transaction, detail transaksi).
- Invoice polymorphic, endpoint pay, callback + job pemrosesan, idempotensi.
- Ledger (pending, available, receivable) dan komisi.
- Pembayaran di tempat oleh kasir dan piutang komisi.
- Payout manual: pengajuan owner, proses superadmin, upload bukti transfer.
- Scheduler: expire, rekonsiliasi, rilis saldo.

Kriteria selesai:
- Test signature callback valid/tidak valid.
- Test callback ganda tidak menggandakan ledger.
- Test total saldo platform + semua usaha konsisten dengan total pembayaran.
- Uji end-to-end di Tripay sandbox berhasil.

### Fase 6 — Antar Jemput dan Home Service
Tugas:
- `outlet_logistics_rates`, validasi radius PostGIS, biaya logistik di kalkulasi harga.
- `logistics_tasks`, penugasan driver/petugas, status, serah terima dengan foto.
- Tracking lokasi driver di Redis, endpoint tracking customer.
- Alur khusus home service doorsmeer (ketersediaan air/listrik).

Kriteria selesai: alamat di luar radius ditolak; biaya sesuai formula; mode tidak muncul jika owner menonaktifkannya.

### Fase 7 — Langganan SaaS
Tugas:
- `plans`, `business_subscriptions`, trial 14 hari.
- Tagihan langganan via Tripay, perpanjangan, masa tenggang, penonaktifan otomatis modul.
- Komisi mengikuti paket (dengan override per usaha oleh superadmin).

Kriteria selesai: modul di luar paket tidak bisa diaktifkan owner; setelah grace period habis `ModuleGate` menolak.

### Fase 8 — Notifikasi, Ulasan, Laporan
Tugas:
- Notifikasi database, email, FCM, interface WhatsApp.
- Pengingat booking via scheduler.
- Ulasan dan balasan owner.
- Dashboard laporan owner dan superadmin (widget Filament), ekspor CSV/Excel.
- Opsional: Laravel Reverb untuk update status real-time.

Kriteria selesai: setiap event utama di 4.12 mengirim notifikasi lewat queue; laporan sesuai data ledger.

### Fase 9 — Hardening dan Deploy
Tugas:
- Audit N+1 (Laravel Debugbar/Telescope di dev), index database, cache query panas.
- Rate limit endpoint sensitif, validasi upload file, enkripsi data rekening (`encrypted` cast).
- Activity log untuk perubahan tarif, toggle, stok, status.
- Backup PostgreSQL harian, monitoring error (Sentry atau sejenis), health check.
- Deploy: Docker image produksi, `octane:start` di belakang Caddy/Nginx, `octane:reload` saat deploy, Horizon dan scheduler sebagai service terpisah.
- Load test endpoint pencarian dan availability.

Kriteria selesai: semua test hijau, tidak ada query N+1 di endpoint utama, deploy staging berjalan dengan callback Tripay sandbox.

---

## 12. Catatan Octane

- Daftarkan `TenantContext`, `CartService`, dan service yang menyimpan data per-request sebagai `scoped`, bukan `singleton`.
- Jangan simpan `auth()->user()` atau `request()` di properti class yang hidup lama.
- Jangan pakai properti `static` untuk cache sementara; pakai Redis atau `once()`.
- Atur `max_requests` worker (misalnya 500) untuk mencegah kebocoran memori.
- Jalankan test khusus yang mensimulasikan beberapa request berurutan dalam satu worker.
- Setelah deploy wajib `php artisan octane:reload`.

---

## 13. Hal yang Perlu Dikonfirmasi Sebelum Produksi

- **Model marketplace di Tripay**: konfirmasi ke Tripay bahwa akun merchant boleh menerima pembayaran atas nama banyak usaha dan meneruskannya (settlement ke pihak ketiga), serta apakah ada fitur split/disbursement. Ini juga berkaitan dengan regulasi Bank Indonesia soal penampungan dana pihak ketiga; konsultasikan ke penyedia pembayaran atau konsultan hukum.
- **Legalitas platform**: pendaftaran Penyelenggara Sistem Elektronik (PSE) di Komdigi, syarat dan ketentuan, kebijakan privasi (UU Pelindungan Data Pribadi).
- **Provider WhatsApp** yang akan dipakai.
- **Validasi tarif Medan**: survei lapangan untuk memperbarui template di bagian 9.
- **Asuransi/tanggung jawab** kerusakan kendaraan saat antar jemput: aturan di syarat dan ketentuan serta bukti serah terima foto.
