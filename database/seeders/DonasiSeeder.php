<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\JenisDonasi;
use App\Models\MetodePembayaran;
use App\Models\User;

class DonasiSeeder extends Seeder
{
    public function run(): void
    {
        $donaturs = Donatur::all();
        $metodePembayarans = MetodePembayaran::all();
        $users = User::all();

        // Ambil jenis donasi yang BUKAN donasi barang
        $jenisDonasiUang = JenisDonasi::where('apakah_barang', false)->get();

        if ($donaturs->isEmpty() || $metodePembayarans->isEmpty() || $users->isEmpty() || $jenisDonasiUang->isEmpty()) {
            // Jangan jalankan seeder jika data master tidak ada
            return;
        }

        // Buat 20 data donasi terverifikasi dengan data yang relevan
        for ($i = 0; $i < 20; $i++) {
            $jenisDonasi = $jenisDonasiUang->random(); // Ambil acak dari jenis donasi uang

            Donasi::create([
                'nomor_transaksi_unik' => 'TRX-' . time() . '-' . uniqid(),
                'donatur_id' => $donaturs->random()->id,
                'jenis_donasi_id' => $jenisDonasi->id,
                'metode_pembayaran_id' => $metodePembayarans->random()->id,
                'jumlah' => rand(50000, 1000000),
                'tanggal_donasi' => now()->subDays(rand(1, 180)), // Tanggal acak dalam 6 bulan terakhir
                'status_konfirmasi' => 'verified', // <-- PENTING: Langsung verified
                'dicatat_oleh_id' => $users->random()->id,
                'atas_nama_hamba_allah' => rand(0, 1) == 1,
            ]);
        }
    }
}