# Logo OtoGo

Sumber kebenaran logo ada di `../../../resources/brand` (proyek Laravel), dibuat
oleh `resources/brand/make_brand.py`. Berkas di folder ini adalah salinannya.

| Berkas | Dipakai untuk |
|---|---|
| `app_icon.png` | Ikon peluncur iOS & bawaan (latar tinta penuh, tanpa alpha) |
| `app_icon_foreground.png` | Lapisan depan ikon adaptif Android (latar `#111418`) |
| `icon-512.png`, `icon-192.png` | Ikon bulat-sudut dengan latar transparan |
| `logo-*.svg` | Versi vektor untuk dokumen/desain |

Di dalam aplikasi, logo digambar ulang sebagai vektor oleh
`lib/core/widgets/brand_logo.dart` agar tajam di semua ukuran.

Setelah mengganti `app_icon*.png`, jalankan ulang:

```
dart run flutter_launcher_icons
```
