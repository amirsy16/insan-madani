<?php

// database/seeders/BidangProgramSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BidangProgram;

class BidangProgramSeeder extends Seeder
{
    public function run(): void
    {
        $bidangs = [
            ['nama_bidang' => 'Pendidikan'],
            ['nama_bidang' => 'Kesehatan'],
            ['nama_bidang' => 'Kemanusiaan'],
            ['nama_bidang' => 'Ekonomi'],
            ['nama_bidang' => 'Dakwah dan Advokasi'],
            ['nama_bidang' => 'Lingkungan'],
            ['nama_bidang' => 'Infrastruktur Sosial'],
            ['nama_bidang' => 'Operasional Lembaga'],
        ];

        foreach ($bidangs as $bidang) {
            BidangProgram::firstOrCreate(
                ['nama_bidang' => $bidang['nama_bidang']], // Search criteria
                $bidang // Data to create if not found
            );
        }
    }
}

