<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MetodePembayaran;

class MetodePembayaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MetodePembayaran::truncate();
        $metodePembayaranData = [
            ['nama' => 'QRIS (Umum)', 'kode' => 'QRIS', 'tipe' => 'digital'],
            ['nama' => 'LinkAja', 'kode' => 'LNK', 'tipe' => 'digital'],
            ['nama' => 'GoPay', 'kode' => 'GPY', 'tipe' => 'digital'],
            ['nama' => 'OVO', 'kode' => 'OVO', 'tipe' => 'digital'],
            ['nama' => 'DANA', 'kode' => 'DAN', 'tipe' => 'digital'],
            ['nama' => 'Tunai (Cash)', 'kode' => 'CSH', 'tipe' => 'tunai'],
            ['nama' => 'Rekening Zakat BSI 241...', 'kode' => 'BSIZ241', 'tipe' => 'transfer_bank', 'nomor_rekening' => '71XXXXXXXX241', 'atas_nama_rekening' => 'YAYASAN AMAL KITA', 'bank_name' => 'BSI'],
            ['nama' => 'Rekening Zakat BSI 103...', 'kode' => 'BSIZ103', 'tipe' => 'transfer_bank', 'nomor_rekening' => '71XXXXXXXX103', 'atas_nama_rekening' => 'YAYASAN AMAL KITA', 'bank_name' => 'BSI'],
            ['nama' => 'Rekening Zakat Muamalat 441...', 'kode' => 'MUAMZ441', 'tipe' => 'transfer_bank', 'nomor_rekening' => '32XXXXXXXX441', 'atas_nama_rekening' => 'YAYASAN AMAL KITA', 'bank_name' => 'Bank Muamalat'],
            ['nama' => 'Rekening Infak BSI 103...', 'kode' => 'BSII103', 'tipe' => 'transfer_bank', 'nomor_rekening' => '71XXXXXXXX103', 'atas_nama_rekening' => 'YAYASAN AMAL KITA', 'bank_name' => 'BSI'], // Bisa sama dengan Zakat BSI 103
            ['nama' => 'Rekening Infak Mandiri 110...', 'kode' => 'MDRI110', 'tipe' => 'transfer_bank', 'nomor_rekening' => '13XXXXXXXXX110', 'atas_nama_rekening' => 'YAYASAN AMAL KITA', 'bank_name' => 'Bank Mandiri'],
            // Tambahkan rekening BCA dan BNI Anda
        ];
        foreach ($metodePembayaranData as $data) {
            // Ganti nomor rekening dengan yang asli sebelum production
            if (str_contains($data['nama'], 'Rekening')) {
                $data['instruksi_pembayaran'] = "Mohon transfer ke {$data['bank_name']} No. Rek: {$data['nomor_rekening']} a.n. {$data['atas_nama_rekening']}. Konfirmasi setelah transfer.";
            }
            MetodePembayaran::create($data);
        }
    }
}
