<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class SistemImportTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        // Return template with 2 example rows to show the expected format
        return new Collection([
            // Example row 1 - Infaq via Transfer Bank
            [
                'DON001 atau email@contoh.com atau 81234567890', // donatur_identifier
                'false',  // atas_nama_hamba_allah
                'Infaq',  // jenis_donasi_id 
                'Transfer Bank',  // metode_pembayaran_id
                'Ahmad Fundraiser atau 1234567890',  // fundraiser_identifier
                '100000',  // jumlah
                '',  // keterangan_infak_khusus
                '',  // infaq_terikat_option_id
                '',  // deskripsi_barang
                '',  // perkiraan_nilai_barang
                '',  // bukti_pembayaran
                'Semoga bermanfaat',  // catatan_donatur
                '2025-06-08',  // tanggal_donasi
                '',  // nomor_transaksi_unik
                'pending',  // status_konfirmasi
                '',  // catatan_konfirmasi
            ],
            // Example row 2 - Zakat via Tunai
            [
                'DON002',  // donatur_identifier
                'true',   // atas_nama_hamba_allah
                'Zakat',  // jenis_donasi_id
                'Tunai',  // metode_pembayaran_id
                'Siti Fundraiser',  // fundraiser_identifier
                '250000',  // jumlah
                'Infaq untuk yatim piatu',  // keterangan_infak_khusus
                '1',  // infaq_terikat_option_id
                '',  // deskripsi_barang
                '',  // perkiraan_nilai_barang
                '',  // bukti_pembayaran
                'Barakallahu fiikum',  // catatan_donatur
                '2025-06-09',  // tanggal_donasi
                'TRX20250609001',  // nomor_transaksi_unik
                'dikonfirmasi',  // status_konfirmasi
                'Donasi sudah diterima',  // catatan_konfirmasi
            ]
        ]);
    }

    public function headings(): array
    {
        // These headings should match the columns defined in DonasiImporter.php
        // Using the actual column names (keys) from ImportColumn::make('key')
        return [
            'donatur_identifier',
            'atas_nama_hamba_allah',
            'jenis_donasi_id',
            'metode_pembayaran_id', 
            'fundraiser_identifier',
            'jumlah',
            'keterangan_infak_khusus',
            'infaq_terikat_option_id',
            'deskripsi_barang',
            'perkiraan_nilai_barang',
            'bukti_pembayaran',
            'catatan_donatur',
            'tanggal_donasi',
            'nomor_transaksi_unik',
            'status_konfirmasi',
            'catatan_konfirmasi',
        ];
    }
}
