# SIPUS-Del — Sistem Informasi Perpustakaan Kampus Del

Tugas Mandiri Minggu 5 — Pemrograman & Pengujian Web (12S3101), Institut Teknologi Del
Olivia Tambunan (12S24048)

Aplikasi web pengelolaan data buku dan kategori perpustakaan dengan arsitektur MVC (server-side rendering). Dibangun dengan Laravel 13.

## Fitur Utama

- CRUD **Buku** dan **Kategori** lewat `Route::resource` (7 aksi RESTful) dan Implicit Route Model Binding
- Relasi Eloquent One-to-Many: `Kategori hasMany Buku`, `Buku belongsTo Kategori`, dengan proteksi `$fillable`
- Validasi server-side (`$request->validate()`), proteksi CSRF (`@csrf`) dan method spoofing (`@method`)
- Eager Loading (`with('kategori')`) untuk mencegah N+1 query
- Layout Blade Component `<x-layout>` bertema Del Purple (#4C1D95), pagination, flash message, dan `@forelse`
- Factory & Seeder: 5 kategori dan 20 buku

## Instalasi

```bash
git clone https://github.com/OliviaTambunan1/ppw-2026-week5-12S24048.git
cd ppw-2026-week5-12S24048
composer install
cp .env.example .env             # Windows PowerShell: copy .env.example .env
php artisan key:generate
touch database/database.sqlite   # Windows PowerShell: New-Item database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve
```

## Bukti Audit SQL: Eager Loading vs N+1

Pengujian pada halaman `/buku` (10 data per halaman). Setiap kueri dicatat ke `storage/logs/laravel.log` lewat `DB::listen` di `AppServiceProvider`.

**Dengan Eager Loading** — `Buku::with('kategori')->latest()->paginate(10)` → **3 kueri**

```
select count(*) as "aggregate" from "bukus"
select * from "bukus" order by "created_at" desc limit 10 offset 0
select * from "kategoris" where "kategoris"."id" in (1, 2, 3)
```

**Tanpa Eager Loading** — `Buku::latest()->paginate(10)` → **12 kueri** (N+1 problem)

```
select count(*) as "aggregate" from "bukus"
select * from "bukus" order by "created_at" desc limit 10 offset 0
select * from "kategoris" where "kategoris"."id" = ? limit 1    <- diulang 10x, satu per buku
```

| Kondisi              | Jumlah kueri                        |
| -------------------- | ----------------------------------- |
| Tanpa Eager Loading  | 12 (1 count + 1 buku + 10 kategori) |
| Dengan Eager Loading | 3 (1 count + 1 buku + 1 kategori)   |

Tanpa `with()`, kategori diambil satu per satu untuk tiap buku (Lazy Loading), sehingga kueri bertambah seiring jumlah baris. Dengan `with('kategori')`, seluruh kategori diambil dalam satu kueri `WHERE id IN (...)`, jadi jumlah kueri tetap 3. Kueri `count` berasal dari `paginate()` untuk menghitung total halaman.

## Struktur MVC

| Lapisan    | Lokasi                                                              |
| ---------- | ------------------------------------------------------------------- |
| Model      | `app/Models/Buku.php`, `Kategori.php`                               |
| View       | `resources/views/buku/`, `kategori/`, `components/layout.blade.php` |
| Controller | `app/Http/Controllers/BukuController.php`, `KategoriController.php` |
| Routes     | `routes/web.php`                                                    |
