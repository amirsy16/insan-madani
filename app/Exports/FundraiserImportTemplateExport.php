<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Collection;

class FundraiserImportTemplateExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        // Return example data for Fundraiser template with 2 examples
        return new Collection([
            // Example row 1 - Active Fundraiser
            [
                'Ahmad Fundraiser',  // nama_fundraiser
                '1234567890123456',  // nomor_identitas
                '81234567890',  // nomor_hp
                'Jl. Fundraiser No. 123',  // alamat
                'admin@contoh.com',  // user_email
                'true',  // aktif
            ],
            // Example row 2 - Inactive Fundraiser
            [
                'Siti Fundraiser',  // nama_fundraiser
                '6543210987654321',  // nomor_identitas
                '81987654321',  // nomor_hp
                'Jl. Dakwah No. 456',  // alamat
                'fundraiser@contoh.com',  // user_email
                'false',  // aktif
            ]
        ]);
    }

    public function headings(): array
    {
        // Column names that match FundraiserImporter.php
        return [
            'nama_fundraiser',
            'nomor_identitas',
            'nomor_hp',
            'alamat',
            'user_email',
            'aktif',
        ];
    }
}
