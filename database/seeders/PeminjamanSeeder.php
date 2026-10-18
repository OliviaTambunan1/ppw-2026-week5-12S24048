<?php

namespace Database\Seeders;

use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\Seeder;

class PeminjamanSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil buku non-sampel (stok > 2) agar stok tidak menjadi minus
        $bukus = Buku::where('stok', '>', 2)->orderBy('id')->take(3)->get();

        $contoh = [
            ['12S24001', 'Andi Sitorus',      'dipinjam'],
            ['12S24002', 'Maria Simanjuntak', 'dipinjam'],
            ['12S24003', 'Rudi Siregar',      'dikembalikan'],
        ];

        foreach ($contoh as $i => [$nim, $nama, $status]) {
            $buku = $bukus[$i];

            Peminjaman::create([
                'kode_pinjam'         => 'PJM-SEED-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'buku_id'             => $buku->id,
                'nim_peminjam'        => $nim,
                'nama_peminjam'       => $nama,
                'tanggal_pinjam'      => now()->subDays(5)->toDateString(),
                'tanggal_jatuh_tempo' => now()->addDays(2)->toDateString(),
                'tanggal_kembali'     => $status === 'dikembalikan' ? now()->subDay()->toDateString() : null,
                'status'              => $status,
            ]);

            if ($status === 'dipinjam') {
                $buku->decrement('stok'); // jaga konsistensi stok dengan data peminjaman
            }
        }
    }
}