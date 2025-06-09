<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->call([
            IndonesiaSeeder::class, // Import Indonesia regions first
            PekerjaanSeeder::class, // Then occupations
            SumberDanaPenyaluranSeeder::class, // Tambahkan ini sebelum JenisDonasiSeeder
            JenisDonasiSeeder::class,
            MetodePembayaranSeeder::class,
            KategoriInfaqTerikatSeeder::class, // Tambahkan kategori infaq terikat
            DonaturSeeder::class,
            FundraiserSeeder::class,
            DonasiSeeder::class, // Donasi terakhir karena butuh ID dari tabel lain
        ]);
    }
}



