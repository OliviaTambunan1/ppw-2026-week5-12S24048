<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kategori;


class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['kode_kategori' => 'PRG', 'nama_kategori' => 'Pemrograman'],
            ['kode_kategori' => 'DBS', 'nama_kategori' => 'Basis Data'],
            ['kode_kategori' => 'JKM', 'nama_kategori' => 'Jaringan Komputer'],
            ['kode_kategori' => 'SIS', 'nama_kategori' => 'Sistem Informasi'],
            ['kode_kategori' => 'AIN', 'nama_kategori' => 'Kecerdasan Buatan'],
        ];

        foreach ($data as $item) {
            Kategori::create($item);
        }
    }
}
