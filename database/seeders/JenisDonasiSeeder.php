<?php
// database/seeders/JenisDonasiSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisDonasi;
use App\Models\SumberDanaPenyaluran;

class JenisDonasiSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil ID dari sumber dana yang sudah kita buat sebelumnya
        $sumberZakat = SumberDanaPenyaluran::where('nama_sumber_dana', 'like', '%Zakat%')->first()->id;
        $sumberInfaq = SumberDanaPenyaluran::where('nama_sumber_dana', 'like', '%Infaq%')->first()->id;
        $sumberCsr = SumberDanaPenyaluran::where('nama_sumber_dana', 'like', '%CSR%')->first()->id;
        $sumberDskl = SumberDanaPenyaluran::where('nama_sumber_dana', 'like', '%DSKL%')->first()->id;
        $sumberUmum = SumberDanaPenyaluran::where('nama_sumber_dana', 'Dana Umum')->first()->id;

        $jenisDonasiData = [
            // Zakat
            ['nama' => 'Zakat Maal', 'sumber_dana_penyaluran_id' => $sumberZakat],
            ['nama' => 'Zakat Fitrah', 'sumber_dana_penyaluran_id' => $sumberZakat],
            ['nama' => 'Zakat Perusahaan', 'sumber_dana_penyaluran_id' => $sumberZakat],
            
            // Infaq
            ['nama' => 'Infaq Terikat', 'sumber_dana_penyaluran_id' => $sumberInfaq],
            ['nama' => 'Infaq Tidak Terikat', 'sumber_dana_penyaluran_id' => $sumberInfaq],
            ['nama' => 'Sedekah', 'sumber_dana_penyaluran_id' => $sumberInfaq],

            // Lainnya
            ['nama' => 'Dana CSR', 'sumber_dana_penyaluran_id' => $sumberCsr],
            ['nama' => 'DSKL (Dana Sosial Keagamaan Lainnya)', 'sumber_dana_penyaluran_id' => $sumberDskl],
            
            // Penyaluran Langsung - untuk import CSV
            ['nama' => 'Penyaluran Langsung', 'sumber_dana_penyaluran_id' => $sumberUmum],
            
            // Donasi Barang (tidak masuk perhitungan dana)
            ['nama' => 'Donasi Logistik/Barang', 'apakah_barang' => true, 'sumber_dana_penyaluran_id' => null],
        ];

        foreach ($jenisDonasiData as $data) {
            JenisDonasi::create([
                'nama' => $data['nama'],
                'apakah_barang' => $data['apakah_barang'] ?? false,
                'sumber_dana_penyaluran_id' => $data['sumber_dana_penyaluran_id'],
                'aktif' => true,
            ]);
        }
    }
}