<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Kategori;
use Illuminate\Database\Seeder;

class BukuSeeder extends Seeder
{
    public function run(): void
    {
        // ['kode_kategori' => id kategori], contoh: ['PRG' => 1]
        $kategori = Kategori::pluck('id', 'kode_kategori');

        $penerbit = [
            'Informatika Bandung', 'Andi Offset', 'Erlangga',
            'Elex Media Komputindo', 'Gramedia Pustaka Utama',
        ];

        $data = [
            // Pemrograman
            ['PRG', 'Dasar-Dasar Pemrograman Web dengan Laravel', 'Budi Santoso', 'Panduan membangun aplikasi web dari nol memakai framework Laravel, mulai dari routing hingga autentikasi.'],
            ['PRG', 'Pemrograman Berorientasi Objek dengan PHP', 'Siti Rahmawati', 'Membahas konsep class, inheritance, dan polymorphism melalui contoh kasus nyata.'],
            ['PRG', 'Algoritma dan Struktur Data Menggunakan Python', 'Andi Pratama', 'Pengenalan struktur data dan analisis kompleksitas algoritma untuk pemula.'],
            ['PRG', 'Pengembangan Aplikasi Android dengan Kotlin', 'Dewi Lestari', 'Langkah demi langkah membuat aplikasi mobile modern dengan Kotlin dan Jetpack.'],

            // Basis Data
            ['DBS', 'Perancangan Basis Data Relasional', 'Rudi Hartono', 'Teknik pemodelan ER diagram, normalisasi, dan perancangan skema yang efisien.'],
            ['DBS', 'Mahir SQL dari Dasar hingga Optimasi Kueri', 'Maya Anggraini', 'Latihan kueri SELECT, JOIN, subquery, serta strategi indexing untuk performa.'],
            ['DBS', 'Administrasi Database MySQL', 'Hendra Gunawan', 'Panduan instalasi, backup, replikasi, dan pengamanan server MySQL.'],
            ['DBS', 'Pengantar Data Warehouse dan Business Intelligence', 'Lina Marlina', 'Konsep ETL, desain data warehouse, dan pembuatan dashboard analitik.'],

            // Jaringan Komputer
            ['JKM', 'Dasar-Dasar Jaringan Komputer', 'Agus Setiawan', 'Memahami model OSI, TCP/IP, pengalamatan IP, dan perangkat jaringan.'],
            ['JKM', 'Keamanan Jaringan dan Etika Hacking', 'Fajar Nugroho', 'Pengenalan ancaman siber, firewall, dan teknik pertahanan jaringan.'],
            ['JKM', 'Konfigurasi Router dan Switch untuk Pemula', 'Rina Kusuma', 'Praktik konfigurasi VLAN, routing statis, dan dinamis pada perangkat jaringan.'],
            ['JKM', 'Komputasi Awan dan Infrastruktur Virtual', 'Yoga Permana', 'Mengenal layanan cloud, virtualisasi, dan kontainer untuk infrastruktur modern.'],

            // Sistem Informasi
            ['SIS', 'Analisis dan Perancangan Sistem Informasi', 'Tri Wahyuni', 'Tahapan SDLC, pemodelan UML, dan dokumentasi kebutuhan sistem.'],
            ['SIS', 'Manajemen Proyek Teknologi Informasi', 'Bambang Susilo', 'Perencanaan, penjadwalan, dan pengendalian risiko pada proyek TI.'],
            ['SIS', 'Sistem Informasi Manajemen untuk Organisasi Modern', 'Ratna Sari', 'Peran sistem informasi dalam pengambilan keputusan dan strategi bisnis.'],
            ['SIS', 'Tata Kelola TI dengan Kerangka COBIT', 'Dedi Kurniawan', 'Penerapan kerangka tata kelola untuk menyelaraskan TI dengan tujuan bisnis.'],

            // Kecerdasan Buatan
            ['AIN', 'Pengantar Kecerdasan Buatan', 'Eko Prasetyo', 'Konsep dasar AI, pencarian, representasi pengetahuan, dan sistem pakar.'],
            ['AIN', 'Machine Learning dengan Python dan Scikit-Learn', 'Nadia Putri', 'Membangun model klasifikasi dan regresi serta mengevaluasi hasilnya.'],
            ['AIN', 'Deep Learning untuk Pengolahan Citra', 'Reza Maulana', 'Jaringan saraf konvolusional dan penerapannya pada pengenalan gambar.'],
            ['AIN', 'Pemrosesan Bahasa Alami untuk Bahasa Indonesia', 'Putri Handayani', 'Teknik tokenisasi, analisis sentimen, dan model bahasa untuk teks berbahasa Indonesia.'],
        ];

        foreach ($data as [$kode, $judul, $penulis, $sinopsis]) {
            Buku::factory()->create([
                'judul'       => $judul,
                'penulis'     => $penulis,
                'sinopsis'    => $sinopsis,
                'kategori_id' => $kategori[$kode],
            ]);
        }
    }
}