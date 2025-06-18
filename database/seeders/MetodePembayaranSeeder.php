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
        // MetodePembayaran::truncate();
        $metodePembayaranData = [
            ['nama' => 'QRIS (Umum)', 'kode' => 'QRIS', 'tipe' => 'digital'],
            ['nama' => 'LinkAja', 'kode' => 'LNK', 'tipe' => 'digital'],
            ['nama' => 'GoPay', 'kode' => 'GPY', 'tipe' => 'digital'],
            ['nama' => 'OVO', 'kode' => 'OVO', 'tipe' => 'digital'],
            ['nama' => 'DANA', 'kode' => 'DAN', 'tipe' => 'digital'],
            ['nama' => 'Tunai', 'kode' => 'CSH', 'tipe' => 'tunai'],
            ['nama' => 'TF BSI 1630', 'kode' => 'BSI1630', 'tipe' => 'transfer_bank', 'nomor_rekening' => '71XXXXXXXX1630', 'atas_nama_rekening' => 'LAZ Insan Madani', 'bank_name' => 'BSI'],
            ['nama' => 'TF BSI 6368', 'kode' => 'BSI6368', 'tipe' => 'transfer_bank', 'nomor_rekening' => '71XXXXXXXX6368', 'atas_nama_rekening' => 'LAZ Insan Madani', 'bank_name' => 'BSI'],
            ['nama' => 'TF Muamalat 7496', 'kode' => 'MUAM7496', 'tipe' => 'transfer_bank', 'nomor_rekening' => '32XXXXXXXX7496', 'atas_nama_rekening' => 'LAZ Insan Madani', 'bank_name' => 'Bank Muamalat'],
            ['nama' => 'TF BSI 6573', 'kode' => 'BSI6573', 'tipe' => 'transfer_bank', 'nomor_rekening' => '71XXXXXXXX6573', 'atas_nama_rekening' => 'LAZ Insan Madani', 'bank_name' => 'BSI'],
            ['nama' => 'TF Mandiri 6777', 'kode' => 'MDR6777', 'tipe' => 'transfer_bank', 'nomor_rekening' => '13XXXXXXXXX6777', 'atas_nama_rekening' => 'LAZ Insan Madani', 'bank_name' => 'Bank Mandiri'],
            ['nama' => 'TF BCA 5393', 'kode' => 'BCA5393', 'tipe' => 'transfer_bank', 'nomor_rekening' => '12XXXXXXXX5393', 'atas_nama_rekening' => 'LAZ Insan Madani', 'bank_name' => 'BCA'],
            ['nama' => 'TF BNI 9073', 'kode' => 'BNI9073', 'tipe' => 'transfer_bank', 'nomor_rekening' => '14XXXXXXXX9073', 'atas_nama_rekening' => 'LAZ Insan Madani', 'bank_name' => 'BNI'],
            // Tambahkan rekening BCA dan BNI Anda
        ];
        foreach ($metodePembayaranData as $data) {
            // Ganti nomor rekening dengan yang asli sebelum production
            if (str_contains($data['nama'], 'TF ')) {
                $data['instruksi_pembayaran'] = "Mohon transfer ke {$data['bank_name']} No. Rek: {$data['nomor_rekening']} a.n. {$data['atas_nama_rekening']}. Konfirmasi setelah transfer.";
            }
            MetodePembayaran::create($data);
        }
    }
}
