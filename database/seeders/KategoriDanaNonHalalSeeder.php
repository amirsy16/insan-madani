<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KategoriDanaNonHalal;

class KategoriDanaNonHalalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoris = [
            [
                'nama' => 'Dana dari Bunga Bank',
                'deskripsi' => 'Dana yang berasal dari bunga tabungan, deposito, atau investasi perbankan konvensional',
                'urutan' => 1,
                'aktif' => true,
            ],
            [
                'nama' => 'Dana dari Usaha Haram',
                'deskripsi' => 'Dana yang berasal dari usaha atau bisnis yang tidak halal menurut syariat Islam',
                'urutan' => 2,
                'aktif' => true,
            ],
            [
                'nama' => 'Dana dari Riba',
                'deskripsi' => 'Dana yang berasal dari transaksi riba atau praktik rentenir',
                'urutan' => 3,
                'aktif' => true,
            ],
            [
                'nama' => 'Dana dari Judi',
                'deskripsi' => 'Dana yang berasal dari kegiatan perjudian atau spekulasi yang diharamkan',
                'urutan' => 4,
                'aktif' => true,
            ],
            [
                'nama' => 'Dana dari Investasi Non-Syariah',
                'deskripsi' => 'Dana yang berasal dari investasi di perusahaan atau instrumen keuangan non-syariah',
                'urutan' => 5,
                'aktif' => true,
            ],
            [
                'nama' => 'Dana Tidak Jelas Sumbernya',
                'deskripsi' => 'Dana yang sumbernya meragukan atau tidak dapat dipastikan kehalalannya',
                'urutan' => 6,
                'aktif' => true,
            ],
            [
                'nama' => 'Dana dari Warisan Non-Muslim',
                'deskripsi' => 'Dana warisan dari keluarga non-Muslim yang ingin disalurkan untuk kebaikan',
                'urutan' => 7,
                'aktif' => true,
            ],
            [
                'nama' => 'Lainnya',
                'deskripsi' => 'Kategori lain yang memerlukan penjelasan khusus',
                'urutan' => 99,
                'aktif' => true,
            ],
        ];

        foreach ($kategoris as $kategori) {
            KategoriDanaNonHalal::create($kategori);
        }
    }
}
