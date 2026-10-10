<?php

namespace Database\Factories;

use App\Models\Buku;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Kategori;
use Illuminate\Support\Str;

/**
 * @extends Factory<Buku>
 */
class BukuFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
        public function definition(): array
        {
            return [
                'isbn'         => fake()->unique()->isbn13(),
                'judul'        => Str::title(fake()->words(fake()->numberBetween(3, 5), true)),
                'penulis'      => fake()->name(),
                'penerbit'     => fake()->randomElement([
                    'Informatika Bandung', 'Andi Offset', 'Erlangga',
                    'Elex Media Komputindo', 'Gramedia Pustaka Utama',
                ]),
                'tahun_terbit' => fake()->numberBetween(2005, 2025),
                'kategori_id'  => Kategori::factory(),
                'stok'         => fake()->numberBetween(0, 25),
                'sinopsis'     => fake()->paragraph(4),
            ];
        }
}
