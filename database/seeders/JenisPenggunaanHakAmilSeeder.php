<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisPenggunaanHakAmil; // Pastikan ini diimport

class JenisPenggunaanHakAmilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jenisPenggunaan = [
            'Operasional Kantor',
            'Gaji Amil',
            'Honorarium Panitia',
            'Sosialisasi dan Dakwah',
            'Pengembangan Sumber Daya Amil',
            'Pengadaan Sarana dan Prasarana',
            'Biaya Administrasi Bank',
        ];

        foreach ($jenisPenggunaan as $nama) {
            JenisPenggunaanHakAmil::firstOrCreate(['nama' => $nama]);
        }
    }
}