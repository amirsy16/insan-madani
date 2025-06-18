<?php

// database/seeders/SumberDanaPenyaluranSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SumberDanaPenyaluran;

class SumberDanaPenyaluranSeeder extends Seeder
{
    public function run(): void
    {
        $sumberDanas = [
            ['nama_sumber_dana' => 'Dana Zakat'], //
            ['nama_sumber_dana' => 'Dana Infaq/Sedekah'], // Menggabungkan Infaq & Sedekah atau pisah tergantung kebutuhan
            ['nama_sumber_dana' => 'Dana CSR'], //
            ['nama_sumber_dana' => 'Dana DSKL (Dana Sosial Keagamaan Lainnya)'], //
            ['nama_sumber_dana' => 'Hak Amil'], //
            ['nama_sumber_dana' => 'Dana Umum'], // Untuk penyaluran langsung/umum
            // ['nama_sumber_dana' => 'Dana Non Halal'], // Jika ada
        ];

        foreach ($sumberDanas as $sumber) {
            SumberDanaPenyaluran::create($sumber);
        }
    }
}
