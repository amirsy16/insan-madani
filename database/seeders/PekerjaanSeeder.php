<?php

namespace Database\Seeders;

use App\Models\Pekerjaan; // Pastikan namespace model sudah benar
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PekerjaanSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Pekerjaan::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $pekerjaans = [
            'Belum/Tidak Bekerja',
            'Pelajar/Mahasiswa',
            'Mengurus Rumah Tangga',
            'Pensiunan',
            'Pegawai Negeri Sipil',
            'Karyawan Swasta',
            'Wiraswasta',
            'Guru',
            'Dosen',
            'Dokter',
            'Petani',
            'Nelayan',
            'Pedagang',
            'Buruh',
            'TNI',
            'POLRI',
            'Lainnya',
        ];
        
        // Pengurutan berdasarkan abjad untuk kemudahan pencarian di dropdown, 
        // kecuali 'Lainnya', 'Belum/Tidak Bekerja', 'Pelajar/Mahasiswa', 
        // 'Mengurus Rumah Tangga', dan 'Pensiunan' yang mungkin ingin diletakkan di awal atau akhir.
        // Untuk contoh ini, kita urutkan semua kecuali 'Lainnya'.
        
        $fixedAtEnd = 'Lainnya';
        // Opsi untuk item yang ingin diletakkan di awal tanpa diurutkan
        $fixedAtStart = [
            'Belum/Tidak Bekerja',
            'Pelajar/Mahasiswa',
            'Mengurus Rumah Tangga',
            'Pensiunan',
        ];

        // Pisahkan item yang akan diletakkan di awal dan akhir
        $tempPekerjaans = array_diff($pekerjaans, $fixedAtStart, [$fixedAtEnd]);
        sort($tempPekerjaans); // Urutkan sisa item

        // Gabungkan kembali
        $finalPekerjaans = array_merge($fixedAtStart, $tempPekerjaans);
        if (in_array($fixedAtEnd, $pekerjaans)) {
            $finalPekerjaans[] = $fixedAtEnd;
        }
        // Hapus duplikat jika ada (misal jika fixedAtStart ada di $pekerjaans awal dan ter-generate lagi)
        // Seharusnya tidak terjadi dengan array_diff, tapi sebagai penjagaan.
        $finalPekerjaans = array_values(array_unique($finalPekerjaans));


        foreach ($finalPekerjaans as $pekerjaan) {
            Pekerjaan::create(['nama' => $pekerjaan]);
        }
    }
}