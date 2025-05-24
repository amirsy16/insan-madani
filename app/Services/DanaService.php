<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\ProgramPenyaluran;
use App\Models\SumberDanaPenyaluran;
use Illuminate\Support\Facades\DB;

class DanaService
{
    /**
     * Menghitung saldo tersedia untuk suatu Sumber Dana Penyaluran.
     *
     * @param int $sumberDanaPenyaluranId
     * @return float
     */
    public function getSaldoTersedia(int $sumberDanaPenyaluranId): float
    {
        $sumberDana = SumberDanaPenyaluran::find($sumberDanaPenyaluranId);
        if (!$sumberDana) {
            return 0;
        }

        // Log untuk debugging
        \Illuminate\Support\Facades\Log::info('Menghitung saldo untuk: ' . $sumberDana->nama_sumber_dana);

        // Tentukan pola pencarian berdasarkan nama sumber dana
        $jenisDonasiLikePattern = '%'; // Default - ambil semua jenis donasi
        
        // Jika ingin spesifik berdasarkan nama sumber dana
        if (str_contains(strtolower($sumberDana->nama_sumber_dana), 'zakat')) {
            $jenisDonasiLikePattern = '%Zakat%';
        } elseif (str_contains(strtolower($sumberDana->nama_sumber_dana), 'infaq')) {
            $jenisDonasiLikePattern = '%Infaq%';
        } elseif (str_contains(strtolower($sumberDana->nama_sumber_dana), 'csr')) {
            $jenisDonasiLikePattern = '%CSR%';
        }

        // 1. Hitung total penerimaan dana - METODE ALTERNATIF
        // Dapatkan semua jenis donasi yang terkait dengan sumber dana ini
        $jenisDonasis = \App\Models\JenisDonasi::where('sumber_dana_penyaluran_id', $sumberDanaPenyaluranId)
            ->pluck('id')
            ->toArray();
        
        \Illuminate\Support\Facades\Log::info('Jenis Donasi IDs untuk ' . $sumberDana->nama_sumber_dana, $jenisDonasis);
        
        // Jika tidak ada jenis donasi yang terkait, coba gunakan pendekatan alternatif
        if (empty($jenisDonasis)) {
            \Illuminate\Support\Facades\Log::warning('Tidak ada jenis donasi yang terkait dengan ' . $sumberDana->nama_sumber_dana);
            
            // Coba cari berdasarkan nama jenis donasi
            $totalPenerimaan = \App\Models\Donasi::where('status_konfirmasi', 'verified')
                ->whereHas('jenisDonasi', function ($query) use ($jenisDonasiLikePattern) {
                    $query->where('nama', 'like', $jenisDonasiLikePattern);
                })
                ->sum('jumlah');
        } else {
            // Gunakan jenis donasi yang terkait
            $totalPenerimaan = \App\Models\Donasi::where('status_konfirmasi', 'verified')
                ->whereIn('jenis_donasi_id', $jenisDonasis)
                ->sum('jumlah');
        }
        
        \Illuminate\Support\Facades\Log::info('Total Penerimaan untuk ' . $sumberDana->nama_sumber_dana . ': ' . $totalPenerimaan);

        // 2. Hitung total penyaluran dana
        $totalPenyaluran = \App\Models\ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaPenyaluranId)
            ->sum('jumlah_dana');
        
        \Illuminate\Support\Facades\Log::info('Total Penyaluran untuk ' . $sumberDana->nama_sumber_dana . ': ' . $totalPenyaluran);
        
        $saldo = $totalPenerimaan - $totalPenyaluran;
        \Illuminate\Support\Facades\Log::info('Saldo Akhir untuk ' . $sumberDana->nama_sumber_dana . ': ' . $saldo);

        return $saldo > 0 ? $saldo : 0;
    }
}
