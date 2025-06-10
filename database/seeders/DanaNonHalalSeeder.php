<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisDonasi;
use App\Models\SumberDanaPenyaluran;

class DanaNonHalalSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Cari atau buat sumber dana untuk dana non halal
        $sumberDanaNonHalal = SumberDanaPenyaluran::firstOrCreate(
            ['nama_sumber_dana' => 'Dana Non Halal'],
            [
                'nama_sumber_dana' => 'Dana Non Halal',
                'deskripsi' => 'Sumber dana khusus untuk dana yang mengandung unsur non halal',
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Hanya buat 1 jenis donasi "Dana Non Halal"
        JenisDonasi::firstOrCreate(
            ['nama' => 'Dana Non Halal'],
            [
                'apakah_barang' => false,
                'membutuhkan_keterangan_tambahan' => true,
                'sumber_dana_penyaluran_id' => $sumberDanaNonHalal->id,
                'aktif' => true,
                'mengandung_dana_non_halal' => true,
                'keterangan_dana_non_halal' => 'Dana yang mengandung unsur non halal dan membutuhkan penanganan khusus sesuai syariah',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('Jenis donasi Dana Non Halal berhasil ditambahkan.');
    }
}
