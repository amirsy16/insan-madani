<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\JenisDonasi;

class JenisDonasiSeeder extends Seeder {
    public function run(): void {
        // JenisDonasi::truncate(); // Hati-hati jika sudah ada data penting
        $jenisDonasiData = [
            ['nama' => 'Zakat Maal', 'kode' => 'ZML', 'deskripsi' => 'Zakat atas harta yang dimiliki.'],
            ['nama' => 'Zakat Fitrah', 'kode' => 'ZFT', 'deskripsi' => 'Zakat yang wajib dikeluarkan sebelum Idul Fitri.'],
            ['nama' => 'Infak Umum', 'kode' => 'IFU', 'deskripsi' => 'Infak untuk kepentingan umum.'],
            ['nama' => 'Infak Khusus', 'kode' => 'IFK', 'deskripsi' => 'Infak untuk tujuan spesifik.', 'membutuhkan_keterangan_tambahan' => true],
            ['nama' => 'Sedekah', 'kode' => 'SDK', 'deskripsi' => 'Pemberian sukarela untuk kebaikan.'],
            ['nama' => 'DSKL', 'kode' => 'DSKL', 'deskripsi' => 'Dana Sosial Keagamaan Lainnya.', 'membutuhkan_keterangan_tambahan' => true],
            ['nama' => 'Donasi Barang', 'kode' => 'BRG', 'deskripsi' => 'Donasi dalam bentuk barang.', 'membutuhkan_keterangan_tambahan' => true, 'apakah_barang' => true],
        ];
        foreach ($jenisDonasiData as $data) {
            JenisDonasi::create($data);
        }
    }
}