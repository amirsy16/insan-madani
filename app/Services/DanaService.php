<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\ProgramPenyaluran;
use App\Models\SumberDanaPenyaluran;

class DanaService
{
    public function getLaporanPerubahanDana($startDate, $endDate, $sumberDanaId = null)
    {
        $sumberDanaPenyaluran = SumberDanaPenyaluran::query()
            ->when($sumberDanaId, fn ($query, $id) => $query->where('id', $id))
            ->get();

        $report = [];

        foreach ($sumberDanaPenyaluran as $sumberDana) {
            $saldoAwal = $this->getSaldoAwal($startDate, $sumberDana->id);
            $totalPemasukan = $this->getTotalPemasukan($startDate, $endDate, $sumberDana->id);
            $totalPengeluaran = $this->getTotalPengeluaran($startDate, $endDate, $sumberDana->id);
            $saldoAkhir = $saldoAwal + $totalPemasukan - $totalPengeluaran;

            $report[] = [
                'sumber_dana' => $sumberDana->nama_sumber_dana,
                'saldo_awal' => $saldoAwal,
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'kenaikan_penurunan' => $totalPemasukan - $totalPengeluaran,
                'saldo_akhir' => $saldoAkhir,
            ];
        }
        return $report;
    }


    public function getSaldoAwal($date, $sumberDanaId)
    {
        // Pemasukan sebelum $date
        $pemasukanSebelum = Donasi::query()
            ->join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id')
            ->where('jenis_donasis.sumber_dana_id', $sumberDanaId)
            ->where('donasis.tanggal_donasi', '<', $date)
            // MODIFIED: Now includes both 'terverifikasi' and 'pending' statuses.
            ->whereIn('donasis.status_donasi', ['terverifikasi', 'pending'])
            ->sum('donasis.jumlah_donasi');

        // Pengeluaran sebelum $date
        $pengeluaranSebelum = ProgramPenyaluran::query()
            ->where('sumber_dana_penyaluran_id', $sumberDanaId)
            ->where('tanggal_penyaluran', '<', $date)
            ->sum('jumlah_penyaluran');

        return $pemasukanSebelum - $pengeluaranSebelum;
    }

    public function getTotalPemasukan($startDate, $endDate, $sumberDanaId)
    {
        return Donasi::query()
            ->join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id')
            ->where('jenis_donasis.sumber_dana_id', $sumberDanaId)
            ->whereBetween('donasis.tanggal_donasi', [$startDate, $endDate])
            // MODIFIED: Now includes both 'terverifikasi' and 'pending' statuses.
            ->whereIn('donasis.status_donasi', ['verified', 'pending'])
            ->sum('donasis.jumlah_donasi');
    }

    public function getTotalPengeluaran($startDate, $endDate, $sumberDanaId)
    {
        return ProgramPenyaluran::query()
            ->where('sumber_dana_penyaluran_id', $sumberDanaId)
            ->whereBetween('tanggal_penyaluran', [$startDate, $endDate])
            ->sum('jumlah_penyaluran');
    }
}