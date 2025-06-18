<?php

namespace Database\Seeders;

use App\Models\JenisPenggunaanHakAmil;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PengaturanHakAmilSeeder extends Seeder
{
    /**
     * Run the database seeders.
     */
    public function run(): void
    {
        // Buat pengaturan global default terlebih dahulu
        JenisPenggunaanHakAmil::create([
            'nama' => 'Operasional Umum',
            'persentase_hak_amil' => 2.5, // 2.5% sebagai default global
            'aktif' => true,
            'tanggal_berlaku_mulai' => now()->toDateString(),
        ]);

        // Cari sumber dana "Dana Zakat" jika ada
        $sumberDanaZakat = \App\Models\SumberDanaPenyaluran::where('nama_sumber_dana', 'LIKE', '%zakat%')->first();
        if ($sumberDanaZakat) {
            JenisPenggunaanHakAmil::create([
                'nama' => 'Hak Amil Zakat',
                'persentase_hak_amil' => 12.5, // 1/8 dari zakat = 12.5%
                'sumber_dana_penyaluran_id' => $sumberDanaZakat->id,
                'aktif' => true,
                'tanggal_berlaku_mulai' => now()->toDateString(),
            ]);
        }

        // Cari sumber dana "Infaq" jika ada
        $sumberDanaInfaq = \App\Models\SumberDanaPenyaluran::where('nama_sumber_dana', 'LIKE', '%infaq%')->first();
        if ($sumberDanaInfaq) {
            JenisPenggunaanHakAmil::create([
                'nama' => 'Hak Amil Infaq',
                'persentase_hak_amil' => 5.0, // 5% untuk infaq
                'sumber_dana_penyaluran_id' => $sumberDanaInfaq->id,
                'aktif' => true,
                'tanggal_berlaku_mulai' => now()->toDateString(),
            ]);
        }

        // Contoh pengaturan per jenis donasi jika diperlukan
        $jenisDonasi = \App\Models\JenisDonasi::where('nama', 'LIKE', '%zakat%')->first();
        if ($jenisDonasi) {
            JenisPenggunaanHakAmil::create([
                'nama' => 'Operasional Zakat Khusus',
                'persentase_hak_amil' => 10.0, // 10% khusus untuk jenis donasi zakat tertentu
                'jenis_donasi_id' => $jenisDonasi->id,
                'aktif' => false, // Tidak aktif secara default, bisa diaktifkan jika diperlukan
                'tanggal_berlaku_mulai' => now()->toDateString(),
            ]);
        }
    }
}
