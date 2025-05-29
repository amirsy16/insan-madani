<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\JenisDonasi;
use App\Models\SumberDanaPenyaluran;
use App\Models\ProgramPenyaluran;
use App\Models\Asnaf;
use App\Models\BidangProgram;
use Illuminate\Support\Facades\DB;

class DanaService
{
    /**
     * Mendapatkan saldo tersedia untuk sumber dana tertentu
     * 
     * @param int $sumberDanaId ID sumber dana
     * @return float Saldo tersedia
     */
    public function getSaldoTersedia(int $sumberDanaId): float
    {
        // 1. Ambil data jenis donasi yang terkait dengan sumber dana ini
        $jenisDonasi = JenisDonasi::where('sumber_dana_penyaluran_id', $sumberDanaId)->pluck('id');
        
        // 2. Hitung total penerimaan (donasi yang sudah terverifikasi)
        $totalPenerimaan = Donasi::whereIn('jenis_donasi_id', $jenisDonasi)
            ->where('status_konfirmasi', 'verified')
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            
        // 3. Hitung bagian amil jika ini adalah dana zakat (12.5%)
        $sumberDana = SumberDanaPenyaluran::find($sumberDanaId);
        $bagianAmil = 0;
        if ($sumberDana && strtolower($sumberDana->nama_sumber_dana) === 'dana zakat') {
            $bagianAmil = $totalPenerimaan * 0.125; // 12.5% untuk amil
        }
        
        // 4. Hitung total penyaluran untuk sumber dana ini
        $totalPenyaluran = ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaId)
            ->sum('jumlah_dana');
            
        // 5. Hitung saldo tersedia
        $saldoTersedia = $totalPenerimaan - $bagianAmil - $totalPenyaluran;
        
        return max(0, $saldoTersedia); // Pastikan tidak negatif
    }

    /**
     * Mendapatkan laporan perubahan dana untuk semua jenis sumber dana
     */
    public function getLaporanPerubahanDana(string $startDate, string $endDate): array
    {
        $sumberDanaList = SumberDanaPenyaluran::where('aktif', true)->get();
        $result = [];
        foreach ($sumberDanaList as $sumberDana) {
            $detail = $this->getLaporanDanaDetail(
                $sumberDana->nama_sumber_dana,
                $sumberDana->id,
                $startDate,
                $endDate
            );
            // Jika bukan Hak Amil, hitung potongan 12% dari penerimaan
            if (strtolower($sumberDana->nama_sumber_dana) !== 'hak amil') {
                $detail['bagian_amil'] = round(($detail['penerimaan'] ?? 0) * 0.12);
                // Penyaluran net = penyaluran - bagian amil
                $detail['penyaluran_net'] = ($detail['penyaluran'] ?? 0);
            } else {
                $detail['bagian_amil'] = 0;
                $detail['penyaluran_net'] = ($detail['penyaluran'] ?? 0);
            }
            $result[$sumberDana->id] = $detail;
        }
        return $result;
    }
    
    /**
     * Mendapatkan detail laporan untuk satu jenis sumber dana
     */
    private function getLaporanDanaDetail(string $namaSumberDana, int $sumberDanaId, string $startDate, string $endDate): array
    {
        // 1. Ambil data jenis donasi yang terkait dengan sumber dana ini
        $jenisDonasi = JenisDonasi::where('sumber_dana_penyaluran_id', $sumberDanaId)->pluck('id');
        
        // 2. Hitung saldo awal (total donasi sebelum tanggal mulai - total penyaluran sebelum tanggal mulai)
        $penerimaanAwal = Donasi::whereIn('jenis_donasi_id', $jenisDonasi)
            ->where('status_konfirmasi', 'verified')
            ->where('tanggal_donasi', '<', $startDate)
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            
        $penyaluranAwal = ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaId)
            ->where('tanggal_penyaluran', '<', $startDate)
            ->sum('jumlah_dana');
            
        // Hitung bagian amil awal jika ini adalah dana zakat (12.5%)
        $bagianAmilAwal = 0;
        if (strtolower($namaSumberDana) === 'dana zakat') {
            $bagianAmilAwal = $penerimaanAwal * 0.125; // 12.5% untuk amil
        }
        
        $saldoAwal = $penerimaanAwal - $bagianAmilAwal - $penyaluranAwal;
        $saldoAwal = max(0, $saldoAwal); // Pastikan tidak negatif
            
        // 3. Hitung total penerimaan dalam periode
        $penerimaan = Donasi::whereIn('jenis_donasi_id', $jenisDonasi)
            ->where('status_konfirmasi', 'verified')
            ->whereBetween('tanggal_donasi', [$startDate, $endDate])
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            
        // 4. Hitung bagian amil jika ini adalah dana zakat (12.5%)
        $bagianAmil = 0;
        if (strtolower($namaSumberDana) === 'dana zakat') {
            $bagianAmil = $penerimaan * 0.125; // 12.5% untuk amil
        }
            
        // 5. Ambil rincian penerimaan berdasarkan jenis donasi
        $rincianPenerimaan = [];
        foreach (JenisDonasi::whereIn('id', $jenisDonasi)->get() as $jenis) {
            $jumlah = Donasi::where('jenis_donasi_id', $jenis->id)
                ->where('status_konfirmasi', 'verified')
                ->whereBetween('tanggal_donasi', [$startDate, $endDate])
                ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
                
            if ($jumlah > 0) {
                $rincianPenerimaan[$jenis->nama] = $jumlah;
            }
        }
        
        // 6. Hitung total penyaluran dalam periode
        $penyaluran = ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaId)
            ->whereBetween('tanggal_penyaluran', [$startDate, $endDate])
            ->sum('jumlah_dana');
        
        // 7. Ambil semua bidang program yang aktif
        $bidangProgramList = BidangProgram::where('aktif', true)->pluck('nama_bidang')->toArray();
        
        // 8. Inisialisasi rincian penyaluran berdasarkan bidang program
        $rincianPenyaluran = array_fill_keys($bidangProgramList, 0);
        
        // 9. Isi data rincian penyaluran berdasarkan bidang program
        $penyaluranByProgram = ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaId)
            ->whereBetween('tanggal_penyaluran', [$startDate, $endDate])
            ->join('bidang_programs', 'program_penyalurans.bidang_program_id', '=', 'bidang_programs.id')
            ->select('bidang_programs.nama_bidang', DB::raw('SUM(program_penyalurans.jumlah_dana) as total'))
            ->groupBy('bidang_programs.nama_bidang')
            ->get();
            
        foreach ($penyaluranByProgram as $item) {
            if (isset($rincianPenyaluran[$item->nama_bidang])) {
                $rincianPenyaluran[$item->nama_bidang] = $item->total;
            }
        }
        
        // 10. Jika ini dana zakat, tambahkan rincian penyaluran berdasarkan asnaf
        $rincianPenyaluranAsnaf = [];
        if (strtolower($namaSumberDana) === 'dana zakat') {
            // Ambil semua asnaf yang aktif
            $asnafList = Asnaf::where('aktif', true)->pluck('nama_asnaf')->toArray();
            
            // Inisialisasi rincian penyaluran berdasarkan asnaf
            $rincianPenyaluranAsnaf = array_fill_keys($asnafList, 0);
            
            // Isi data rincian penyaluran berdasarkan asnaf
            $penyaluranByAsnaf = ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaId)
                ->whereBetween('tanggal_penyaluran', [$startDate, $endDate])
                ->join('asnafs', 'program_penyalurans.asnaf_id', '=', 'asnafs.id')
                ->select('asnafs.nama_asnaf', DB::raw('SUM(program_penyalurans.jumlah_dana) as total'))
                ->groupBy('asnafs.nama_asnaf')
                ->get();
                
            foreach ($penyaluranByAsnaf as $item) {
                if (isset($rincianPenyaluranAsnaf[$item->nama_asnaf])) {
                    $rincianPenyaluranAsnaf[$item->nama_asnaf] = $item->total;
                }
            }
        }
        
        // 11. Hitung surplus/defisit
        $surplusDefisit = $penerimaan - $bagianAmil - $penyaluran;
        
        // 12. Hitung saldo akhir
        $saldoAkhir = $saldoAwal + $surplusDefisit;
        
        // 13. Susun hasil
        return [
            'title' => $namaSumberDana,
            'penerimaan' => $penerimaan,
            'bagian_amil' => $bagianAmil,
            'rincian_penerimaan' => $rincianPenerimaan,
            'penyaluran' => $penyaluran,
            'rincian_penyaluran' => $rincianPenyaluran,
            'rincian_penyaluran_asnaf' => $rincianPenyaluranAsnaf,
            'surplus_defisit' => $surplusDefisit,
            'saldo_awal' => $saldoAwal,
            'saldo_akhir' => $saldoAkhir
        ];
    }
    
    /**
     * Ambil data penggunaan hak amil untuk periode tertentu
     *
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Support\Collection
     */
    public function getPenggunaanHakAmil(string $startDate, string $endDate)
    {
        return \App\Models\PenggunaanHakAmil::with('jenisPenggunaanHakAmil')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->get();
    }

    /**
     * Mendapatkan data penerimaan hak amil (12% dari setiap donasi terverifikasi per jenis donasi) dan penggunaan hak amil dari resource PenggunaanHakAmil
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getLaporanHakAmil(string $startDate, string $endDate): array
    {
        // 1. Hitung penerimaan hak amil per jenis donasi (12% dari donasi terverifikasi)
        $jenisDonasiList = JenisDonasi::where('aktif', true)->get();
        $penerimaan = [];
        $totalPenerimaan = 0;
        foreach ($jenisDonasiList as $jenis) {
            $jumlahDonasi = Donasi::where('jenis_donasi_id', $jenis->id)
                ->where('status_konfirmasi', 'verified')
                ->whereBetween('tanggal_donasi', [$startDate, $endDate])
                ->sum(\DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            $hakAmil = $jumlahDonasi * 0.12;
            $penerimaan[$jenis->nama] = $hakAmil;
            $totalPenerimaan += $hakAmil;
        }

        // 2. Ambil penggunaan hak amil dari resource PenggunaanHakAmil
        $penggunaan = \App\Models\PenggunaanHakAmil::with('jenisPenggunaanHakAmil')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->get();
        $penggunaanDetail = [];
        $totalPenggunaan = 0;
        foreach ($penggunaan as $item) {
            $nama = $item->jenisPenggunaanHakAmil->nama ?? $item->keterangan ?? '-';
            $penggunaanDetail[$nama] = ($penggunaanDetail[$nama] ?? 0) + $item->jumlah;
            $totalPenggunaan += $item->jumlah;
        }

        // 3. Surplus/defisit dan saldo awal/akhir (opsional, bisa dikembangkan)
        $surplusDefisit = $totalPenerimaan - $totalPenggunaan;

        return [
            'penerimaan_detail' => $penerimaan,
            'total_penerimaan' => $totalPenerimaan,
            'penggunaan_detail' => $penggunaanDetail,
            'total_penggunaan' => $totalPenggunaan,
            'surplus_defisit' => $surplusDefisit,
        ];
    }
}


