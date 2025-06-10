<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class DonaturImportTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        // Return example data for Donatur template with 2 examples
        return new Collection([
            // Example row 1 - Male Donatur
            [
                'DON001',  // kode_donatur
                'male',    // gender
                'Ahmad Donatur',  // nama
                'Jawa Barat',  // province
                '1',  // city_id (ID numerik kota/kabupaten)
                'Coblong',  // district
                'Dago',  // village
                'Jl. Contoh No. 123 RT 01 RW 02',  // alamat_detail
                'Jl. Contoh No. 123 RT 01 RW 02, Dago, Coblong, Bandung, Jawa Barat',  // alamat_lengkap
                '81234567890',  // nomor_hp
                'ahmad@contoh.com',  // email
                'Karyawan Swasta',  // pekerjaan
            ],
            // Example row 2 - Female Donatur
            [
                'DON002',  // kode_donatur
                'female',  // gender
                'Siti Donatur',  // nama
                'DKI Jakarta',  // province
                '2',  // city_id (ID numerik kota/kabupaten)
                'Tanah Abang',  // district
                'Bendungan Hilir',  // village
                'Jl. Sudirman No. 456 RT 03 RW 05',  // alamat_detail
                'Jl. Sudirman No. 456 RT 03 RW 05, Bendungan Hilir, Tanah Abang, Jakarta Pusat, DKI Jakarta',  // alamat_lengkap
                '81987654321',  // nomor_hp
                'siti@contoh.com',  // email
                'Ibu Rumah Tangga',  // pekerjaan
            ]
        ]);
    }

    public function headings(): array
    {
        // Column names that match DonaturImporter.php
        return [
            'kode_donatur',
            'gender',
            'nama',
            'province',
            'city_id',
            'district',
            'village',
            'alamat_detail',
            'alamat_lengkap',
            'nomor_hp',
            'email',
            'pekerjaan',
        ];
    }
}
