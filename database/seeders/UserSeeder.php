<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name'     => 'Pustakawan Del',
            'email'    => 'pustakawan@del.ac.id',
            'password' => 'password123', // otomatis di-hash oleh cast 'hashed' pada model User
        ]);
    }
}
