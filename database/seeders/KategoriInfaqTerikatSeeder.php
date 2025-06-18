<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriInfaqTerikat;

class KategoriInfaqTerikatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoris = [
            [
                'nama_kategori' => 'Pembangunan Masjid',
                'deskripsi' => 'Dana untuk pembangunan masjid baru',
                'urutan' => 1,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Renovasi Masjid',
                'deskripsi' => 'Dana untuk renovasi dan perbaikan masjid',
                'urutan' => 2,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Beasiswa Pendidikan',
                'deskripsi' => 'Dana beasiswa untuk pendidikan',
                'urutan' => 3,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Bantuan Yatim Piatu',
                'deskripsi' => 'Dana bantuan untuk anak yatim piatu',
                'urutan' => 4,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Program Kesehatan',
                'deskripsi' => 'Dana untuk program kesehatan',
                'urutan' => 5,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Air Bersih & Sanitasi',
                'deskripsi' => 'Dana untuk program air bersih dan sanitasi',
                'urutan' => 6,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Pemberdayaan Ekonomi',
                'deskripsi' => 'Dana untuk program pemberdayaan ekonomi',
                'urutan' => 7,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Program Dakwah',
                'deskripsi' => 'Dana untuk kegiatan dakwah',
                'urutan' => 8,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Bantuan Bencana Alam',
                'deskripsi' => 'Dana bantuan untuk korban bencana alam',
                'urutan' => 9,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Pembangunan Sekolah',
                'deskripsi' => 'Dana untuk pembangunan sekolah',
                'urutan' => 10,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Program Tahfidz Quran',
                'deskripsi' => 'Dana untuk program tahfidz Al-Quran',
                'urutan' => 11,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Bantuan Lansia',
                'deskripsi' => 'Dana bantuan untuk lansia',
                'urutan' => 12,
                'aktif' => true,
            ],
            [
                'nama_kategori' => 'Program Lingkungan',
                'deskripsi' => 'Dana untuk program lingkungan',
                'urutan' => 13,
                'aktif' => true,
            ],
        ];

        foreach ($kategoris as $kategori) {
            KategoriInfaqTerikat::firstOrCreate(
                ['nama_kategori' => $kategori['nama_kategori']],
                $kategori
            );
        }
    }
}
