# SIPUS-Del Tahap 3 — Web Services (SOAP & REST API)

Tugas Mandiri Minggu 9 — Pemrograman & Pengujian Web (12S3101), Institut Teknologi Del

## Identitas

| | |
|---|---|
| Nama | Olivia Tambunan |
| NIM | 12S24048 |
| Kelas | 13 SI |
| Program Studi | Sistem Informasi |

## Deskripsi

Lanjutan SIPUS-Del Minggu 6 (aplikasi web Blade untuk katalog dan sirkulasi buku). Pada tahap ini SIPUS-Del membuka kemampuannya sebagai **web service**: endpoint SOAP untuk pengecekan bebas pustaka, REST API v1 untuk aplikasi mobile dan kiosk (autentikasi Sanctum Bearer Token, transaksi sirkulasi ACID, HATEOAS, galat RFC 7807), serta konsumsi REST API eksternal OpenLibrary. Antarmuka web Minggu 6 tetap berfungsi. Dibangun dengan Laravel 13, PHP 8.3, dan MySQL.

## Analisis Kebutuhan & Kelayakan Web Services

### Peran Provider dan Consumer

| Kebutuhan | Peran SIPUS-Del | Layanan | Alasan kelayakan |
|---|---|---|---|
| Cek bebas pustaka untuk Sistem Yudisium (sistem lama) | **Provider** | SOAP `CheckClearanceStatus` | Data pinjaman adalah domain milik perpustakaan. Sistem yudisium lama hanya memahami SOAP/XML, sehingga perlu dilayani dengan protokol yang sama. |
| Aplikasi mobile dan kiosk mandiri | **Provider** | REST API `/api/v1` | Katalog dan sirkulasi adalah domain inti. Banyak klien berbeda butuh akses yang ringan, cepat, dan stateless. |
| Metadata buku (halaman, penerbit, tautan) | **Consumer** | OpenLibrary REST API | Data bibliografi bukan domain inti dan sudah tersedia gratis. Membangunnya sendiri tidak layak. |

### Mengapa REST untuk Mobile/Kiosk, bukan SOAP

| Aspek | SOAP | REST (dipilih untuk Mobile/Kiosk) |
|---|---|---|
| Format pesan | Wajib XML `<soap:Envelope>`, verbose | JSON, ringkas dan didukung langsung oleh JavaScript, Kotlin, Dart |
| Pengelolaan state | Bergantung implementasi | Stateless dengan Bearer Token, mudah diskalakan di belakang load balancer |
| Kontrak layanan | Wajib WSDL | OpenAPI 3.0 (`docs/openapi-sipus-v1.yaml`) |
| Semantik HTTP | Hanya POST sebagai saluran | Verb dan status code bermakna (GET, POST, PUT, PATCH, DELETE; 200, 201, 204, 404, 409, 422) |
| Keputusan | Dipertahankan hanya untuk integrasi sistem lama (yudisium) | Dipakai untuk semua klien baru |

### Penilaian Kelayakan dan Risiko

| Layanan | Kelayakan teknis | Risiko utama | Mitigasi |
|---|---|---|---|
| SOAP `CheckClearanceStatus` | Layak. Hanya membaca satu tabel (`peminjamans`) dan membalas XML sederhana. | Endpoint publik tanpa autentikasi, sehingga NIM dapat ditebak. | Respons hanya memuat jumlah pinjaman dan status (tanpa judul buku atau nama). Untuk produksi: allowlist IP atau WS-Security. |
| REST API `/api/v1` | Layak. Logika sirkulasi sudah ada sejak Minggu 6, tinggal diekspos. | Race condition pada stok; kebocoran data peminjam. | `DB::transaction` + `lockForUpdate`; endpoint peminjaman wajib Bearer Token; galat seragam RFC 7807. |
| Konsumsi OpenLibrary | Layak. Gratis, dan metadata buku bukan domain inti perpustakaan. | Bergantung pada layanan luar (lambat, down, ISBN tidak ada). | `Http::timeout(8)` dengan fallback 404/502/504; data eksternal tidak disimpan. |

## Katalog Endpoint

**SOAP** (tanpa token)

| Method | URI | Deskripsi | Status |
|---|---|---|---|
| GET | `/soap/sipus.wsdl` | Kontrak WSDL | 200 |
| POST | `/soap/sipus` | Operasi `CheckClearanceStatus`: jumlah pinjaman aktif dan `is_cleared` | 200, 500 (`soap:Fault`) |

**REST API** (prefix `/api/v1`; semua galat berformat `application/problem+json`)

| Method | URI | Auth | Deskripsi | Status |
|---|---|---|---|---|
| POST | `/auth/login` | Publik | Terbitkan Bearer Token | 200, 401, 422 |
| GET | `/auth/me` | Bearer | Profil pemilik token | 200, 401 |
| POST | `/auth/logout` | Bearer | Cabut (revoke) token aktif | 200, 401 |
| GET | `/bukus` | Publik | Daftar buku: `search`, `kategori_id`, `tersedia`, `sort`, `dir`, `per_page` | 200 |
| GET | `/bukus/{id}` | Publik | Detail buku, kategori, riwayat peminjaman, `_links` | 200, 404 |
| GET | `/bukus/{id}/referensi-eksternal` | Publik | Metadata dari OpenLibrary (Service Consumer) | 200, 404, 502, 504 |
| POST | `/bukus` | Bearer | Tambah buku (header `Location`) | 201, 401, 422 |
| PUT | `/bukus/{id}` | Bearer | Ganti seluruh atribut | 200, 401, 404, 422 |
| PATCH | `/bukus/{id}` | Bearer | Ubah sebagian atribut | 200, 400, 401, 404, 422 |
| DELETE | `/bukus/{id}` | Bearer | Hapus buku (ditolak bila masih dipinjam) | 204, 401, 404, 409 |
| GET | `/peminjamans` | Bearer | Daftar transaksi: `status`, `nim`, `per_page` | 200, 401 |
| GET | `/peminjamans/{id}` | Bearer | Detail transaksi | 200, 401, 404 |
| POST | `/peminjamans` | Bearer | Pinjam buku (`DB::transaction` + `lockForUpdate`) | 201, 401, 409, 422 |
| PATCH | `/peminjamans/{id}/kembalikan` | Bearer | Kembalikan buku (`DB::transaction` + `lockForUpdate`) | 200, 401, 404, 409 |

## Instalasi & Pengujian Cepat

```bash
git clone https://github.com/OliviaTambunan1/ppw-2026-week5-12S24048.git
cd ppw-2026-week5-12S24048
git checkout week9-rest-api
composer install
cp .env.example .env             # Windows PowerShell: copy .env.example .env
php artisan key:generate
# Buat database kosong bernama sipus_del di MySQL (Laragon: Start All)
php artisan migrate:fresh --seed
php artisan serve
```

Aplikasi berjalan di http://localhost:8000 (MySQL: user `root`, password kosong, port 3306).

**Akun uji pustakawan**

| Email | Password |
|---|---|
| pustakawan@del.ac.id | password123 |

**Pengujian dengan Postman**

1. Impor `docs/SIPUS-Del-APIv1.postman_collection.json` (Postman: *Import*).
2. Pastikan `php artisan serve` berjalan dan database baru di-seed (`migrate:fresh --seed`).
3. Jalankan folder 1 sampai 6 secara berurutan (*Run collection*). Token dan ID tersimpan otomatis oleh skrip tes. Folder 6 (logout) dijalankan paling akhir.
4. Kontrak layanan: `docs/openapi-sipus-v1.yaml` (dapat dibuka di https://editor.swagger.io).

## Galeri Bukti Pengujian

Seluruh screenshot tersimpan di folder [`docs/img/`](docs/img/).

| Skenario | Berkas |
|---|---|
| (a) SOAP XML sukses dan `<soap:Fault>` | [`w9-a1-soap-sukses.png`](docs/img/w9-a1-soap-sukses.png), [`w9-a2-soap-fault.png`](docs/img/w9-a2-soap-fault.png) |
| (b) Login Bearer Token | [`w9-b-login-token.png`](docs/img/w9-b-login-token.png) |
| (c) GET katalog terpaginasi dan `_links` HATEOAS | [`w9-c-katalog-hateoas.png`](docs/img/w9-c-katalog-hateoas.png) |
| (d) POST buku 201 Created dan header `Location` | [`w9-d-post-201-location.png`](docs/img/w9-d-post-201-location.png) |
| (e) Perbedaan PUT dan PATCH | [`w9-e1-put.png`](docs/img/w9-e1-put.png), [`w9-e2-patch.png`](docs/img/w9-e2-patch.png) |
| (f) Peminjaman sukses dan penolakan stok habis | [`w9-f1-pinjam-201.png`](docs/img/w9-f1-pinjam-201.png), [`w9-f2-stok-habis-422.png`](docs/img/w9-f2-stok-habis-422.png) |
| (g) Latihan GitHub REST API (token disensor) | [`w9-g-github-api.png`](docs/img/w9-g-github-api.png) |
