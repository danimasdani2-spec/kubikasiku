# kubikasiku

Aplikasi hitung kubikasi & simulasi muatan kendaraan — PWA yang bisa di-install di HP dan jalan offline.

**Demo:** https://kubikasiku.kossyariah.my.id

## Fitur

- **Input Barang** — daftar barang (kode, nama, P×L×T, berat, isi per Mbox) + master 81 produk, import `.xlsx`/`.csv`, tombol 📄 Format Excel (sheet DATA + PANDUAN)
- **Hitung Volume** — pilih barang + qty (satuan Box/Mbox), pilih kendaraan → ringkasan selisih kubikasi, perkiraan maks muat, simulasi 3D isometrik bak terisi, panduan urutan muat, rekomendasi kendaraan + auto-pilih kendaraan terkecil yang muat
- **21 jenis kendaraan Indonesia** — Pick Up Bak/Box, Blind Van, Engkel CDE, CDD, CDD Long, Fuso, Tronton, Wing Box, Trailer, Kontainer, Box Built-Up 45 CBM (berdasar riset logistik Indonesia)
- **Packing realistis** — depan→belakang, berat di bawah, sebar ke lantai dulu, peringatan roboh bila utilisasi <60%
- **Riwayat** — simpan perhitungan, buka lagi kapan pun
- **Rute pengiriman** — kolom Dari & Ke, tampil di bagikan/cetak/PDF
- **Cetak/PDF & Bagikan + PDF** (dibuat offline di HP)
- **Backup** — file JSON lokal + Google Drive
- **Sinkronisasi cloud** — daftar/masuk dengan akun, data tersinkron antar perangkat (backend PHP + MySQL, lihat bawah)
- **PWA** — install ke layar utama, update otomatis via `version.json`

## Struktur file

| File | Keterangan |
|---|---|
| `index.html` | Aplikasi utama (satu file) |
| `api.php` | Backend sinkronisasi (register/login/pull/push) |
| `config.php` | Kredensial database — **isi dulu sebelum dipakai** |
| `manifest.json` | Manifest PWA |
| `sw.js` | Service worker (cache offline) |
| `icon-192.png`, `icon-512.png`, `icon-180.png` | Ikon aplikasi |
| `version.json` | Nomor versi (untuk auto-update) |

## Cara menjalankan

Tanpa backend (mode lokal/offline): cukup buka `index.html` di browser, atau upload semua file ke hosting.

## Setup sinkronisasi cloud (opsional)

1. Buat database MySQL + user di cPanel, beri semua hak akses.
2. Isi `config.php` dengan kredensial database tersebut.
3. Upload `api.php` + `config.php` ke hosting (sejajar `index.html`). Tabel dibuat otomatis saat API pertama kali diakses.
4. Buka aplikasi → ⋯ → ⚙️ Pengaturan → ☁️ Sinkronisasi akun → Daftar.

## Rilis versi baru

1. Naikkan `APP_V` di `index.html`, nama cache di `sw.js`, dan `version.json`.
2. Upload file yang berubah ke hosting. Aplikasi yang ter-install akan menawarkan update otomatis.

## Kontribusi

Silakan fork, perbaiki, dan kirim pull request. Diskusi/bug bisa lewat Issues.

## Lisensi

MIT — lihat `LICENSE`.
