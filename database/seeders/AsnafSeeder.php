<?php

// database/seeders/AsnafSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Asnaf;

class AsnafSeeder extends Seeder
{
    public function run(): void
    {
        $asnafs = [
            ['nama_asnaf' => 'Fakir', 'deskripsi' => 'Orang yang amat sengsara hidupnya, tidak mempunyai harta dan tenaga untuk memenuhi penghidupannya.'],
            ['nama_asnaf' => 'Miskin', 'deskripsi' => 'Orang yang tidak cukup penghidupannya dan dalam keadaan kekurangan.'],
            ['nama_asnaf' => 'Amil', 'deskripsi' => 'Orang yang mengurus zakat mulai dari pengumpulan hingga pendistribusiannya.'],
            ['nama_asnaf' => 'Muallaf', 'deskripsi' => 'Orang kafir yang ada harapan masuk Islam atau orang yang baru masuk Islam.'],
            ['nama_asnaf' => 'Riqab', 'deskripsi' => 'Memerdekakan budak atau hamba sahaya.'],
            ['nama_asnaf' => 'Gharimin', 'deskripsi' => 'Orang yang berhutang untuk kebutuhan yang halal dan tidak sanggup membayarnya.'],
            ['nama_asnaf' => 'Fisabilillah', 'deskripsi' => 'Orang yang berjuang di jalan Allah dalam bentuk kegiatan dakwah, jihad, dan semacamnya.'],
            ['nama_asnaf' => 'Ibnu Sabil', 'deskripsi' => 'Orang yang kehabisan biaya di perjalanan dalam ketaatan kepada Allah.'],
        ];

        foreach ($asnafs as $asnaf) {
            Asnaf::create($asnaf);
        }
    }
}
