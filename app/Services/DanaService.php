<?php

namespace App\Services;

use App\Models\Donasi;
use App\Models\JenisDonasi;
use App\Models\SumberDanaPenyaluran;
use App\Models\ProgramPenyaluran;
use App\Models\Asnaf;
use App\Models\BidangProgram;
use App\Models\JenisPenggunaanHakAmil;
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
        // EXCLUDE "Penyaluran Langsung" karena ini bukan penerimaan dana biasa
        $totalPenerimaan = Donasi::whereIn('jenis_donasi_id', $jenisDonasi)
            ->where('status_konfirmasi', 'verified')
            ->whereHas('jenisDonasi', function($query) {
                $query->where('nama', '!=', 'Penyaluran Langsung');
            })
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            
        // 3. Hitung bagian amil berdasarkan persentase yang ada di sumber dana
        $sumberDana = SumberDanaPenyaluran::find($sumberDanaId);
        $bagianAmil = 0;
        
        if ($sumberDana && $sumberDana->persentase_hak_amil > 0) {
            $bagianAmil = ($totalPenerimaan * $sumberDana->persentase_hak_amil) / 100;
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
        $totalSaldoAwal = 0;
        $totalPenerimaan = 0;
        $totalBagianAmil = 0;
        $totalPenyaluran = 0;
        $totalSurplusDefisit = 0;
        $totalSaldoAkhir = 0;
        
        foreach ($sumberDanaList as $sumberDana) {
            // SPECIAL HANDLING: Skip "Penyaluran Langsung" dari perhitungan normal
            // karena ini bukan dana yang dikelola organisasi
            if (strtolower($sumberDana->nama_sumber_dana) === 'penyaluran langsung') {
                $detail = $this->getLaporanPenyaluranLangsung(
                    $sumberDana->nama_sumber_dana,
                    $sumberDana->id,
                    $startDate,
                    $endDate
                );
            } else {
                $detail = $this->getLaporanDanaDetail(
                    $sumberDana->nama_sumber_dana,
                    $sumberDana->id,
                    $startDate,
                    $endDate
                );
                // Jika bukan Hak Amil, hitung bagian amil berdasarkan persentase di sumber dana
                if (strtolower($sumberDana->nama_sumber_dana) !== 'hak amil') {
                    // Gunakan persentase hak amil dari sumber dana
                    $persentase = $sumberDana->persentase_hak_amil ?? 0;
                    
                    $detail['bagian_amil'] = round(($detail['penerimaan'] ?? 0) * $persentase / 100);
                    // Penyaluran net = penyaluran - bagian amil
                    $detail['penyaluran_net'] = ($detail['penyaluran'] ?? 0);
                } else {
                    $detail['bagian_amil'] = 0;
                    $detail['penyaluran_net'] = ($detail['penyaluran'] ?? 0);
                }
                
                // Akumulasi total hanya untuk dana yang dikelola (bukan Penyaluran Langsung)
                $totalSaldoAwal += $detail['saldo_awal'] ?? 0;
                $totalPenerimaan += $detail['penerimaan'] ?? 0;
                $totalBagianAmil += $detail['bagian_amil'] ?? 0;
                $totalPenyaluran += $detail['penyaluran'] ?? 0;
                $totalSurplusDefisit += $detail['surplus_defisit'] ?? 0;
                $totalSaldoAkhir += $detail['saldo_akhir'] ?? 0;
            }
            
            $result[$sumberDana->id] = $detail;
        }
        
        // Tambahkan donasi yang tidak memiliki sumber_dana_penyaluran_id (seperti Donasi Logistik/Barang)
        $donasiTanpaSumber = $this->getDanasiTanpaSumberDanaPenyaluran($startDate, $endDate);
        if ($donasiTanpaSumber['penerimaan'] > 0) {
            $totalPenerimaan += $donasiTanpaSumber['penerimaan'];
            $totalSaldoAkhir += $donasiTanpaSumber['saldo_akhir'];
            $result['donasi_lainnya'] = $donasiTanpaSumber;
        }
        
        // Tambahkan summary totals
        $result['summary'] = [
            'total_saldo_awal' => $totalSaldoAwal,
            'total_penerimaan' => $totalPenerimaan,
            'total_bagian_amil' => $totalBagianAmil,
            'total_penyaluran' => $totalPenyaluran,
            'total_surplus_defisit' => $totalSurplusDefisit,
            'total_saldo_akhir' => $totalSaldoAkhir,
        ];
        
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
        // EXCLUDE "Penyaluran Langsung" karena ini bukan penerimaan dana biasa
        $penerimaanAwal = Donasi::whereIn('jenis_donasi_id', $jenisDonasi)
            ->where('status_konfirmasi', 'verified')
            ->where('tanggal_donasi', '<', $startDate)
            ->whereHas('jenisDonasi', function($query) {
                $query->where('nama', '!=', 'Penyaluran Langsung');
            })
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            
        $penyaluranAwal = ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaId)
            ->where('tanggal_penyaluran', '<', $startDate)
            ->sum('jumlah_dana');
            
        // Hitung bagian amil awal berdasarkan persentase sumber dana
        $sumberDana = SumberDanaPenyaluran::find($sumberDanaId);
        $bagianAmilAwal = 0;
        if ($sumberDana && $sumberDana->persentase_hak_amil > 0) {
            $bagianAmilAwal = ($penerimaanAwal * $sumberDana->persentase_hak_amil) / 100;
        }
        
        $saldoAwal = $penerimaanAwal - $bagianAmilAwal - $penyaluranAwal;
        $saldoAwal = max(0, $saldoAwal); // Pastikan tidak negatif
            
        // 3. Hitung total penerimaan dalam periode
        // EXCLUDE "Penyaluran Langsung" karena ini bukan penerimaan dana biasa
        $penerimaan = Donasi::whereIn('jenis_donasi_id', $jenisDonasi)
            ->where('status_konfirmasi', 'verified')
            ->whereBetween('tanggal_donasi', [$startDate, $endDate])
            ->whereHas('jenisDonasi', function($query) {
                $query->where('nama', '!=', 'Penyaluran Langsung');
            })
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            
        // 4. Hitung bagian amil berdasarkan persentase sumber dana
        $bagianAmil = 0;
        if ($sumberDana && $sumberDana->persentase_hak_amil > 0) {
            $bagianAmil = ($penerimaan * $sumberDana->persentase_hak_amil) / 100;
        }
            
        // 5. Ambil rincian penerimaan berdasarkan jenis donasi
        // EXCLUDE "Penyaluran Langsung" karena ini bukan penerimaan dana biasa
        $rincianPenerimaan = [];
        foreach (JenisDonasi::whereIn('id', $jenisDonasi)->where('nama', '!=', 'Penyaluran Langsung')->get() as $jenis) {
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
            'sumber_dana_id' => $sumberDanaId,
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
     * Method khusus untuk menangani "Penyaluran Langsung"
     * Penyaluran Langsung adalah donasi yang langsung disalurkan oleh donatur
     * tanpa masuk ke kas organisasi, sehingga tidak ada penerimaan dan tidak ada defisit
     */
    private function getLaporanPenyaluranLangsung(string $namaSumberDana, int $sumberDanaId, string $startDate, string $endDate): array
    {
        // 1. Penyaluran Langsung tidak memiliki saldo awal karena dana tidak pernah masuk ke organisasi
        $saldoAwal = 0;
        
        // 2. Penyaluran Langsung tidak memiliki penerimaan dalam kas organisasi
        // Meskipun ada donasi tercatat, ini hanya untuk dokumentasi
        $penerimaan = 0;
        
        // 3. Tidak ada bagian amil untuk Penyaluran Langsung
        $bagianAmil = 0;
        
        // 4. Ambil rincian "penerimaan" (dokumentasi donasi) berdasarkan jenis donasi
        // Ini hanya untuk laporan, bukan penerimaan kas
        $jenisDonasi = JenisDonasi::where('sumber_dana_penyaluran_id', $sumberDanaId)->pluck('id');
        $rincianPenerimaan = [];
        $totalDonasiTerdokumentasi = 0;
        
        foreach (JenisDonasi::whereIn('id', $jenisDonasi)->get() as $jenis) {
            $jumlah = Donasi::where('jenis_donasi_id', $jenis->id)
                ->where('status_konfirmasi', 'verified')
                ->whereBetween('tanggal_donasi', [$startDate, $endDate])
                ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
                
            if ($jumlah > 0) {
                $rincianPenerimaan[$jenis->nama] = $jumlah;
                $totalDonasiTerdokumentasi += $jumlah;
            }
        }
        
        // 5. Hitung total penyaluran dalam periode (jika ada program yang tercatat)
        $penyaluran = ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaId)
            ->whereBetween('tanggal_penyaluran', [$startDate, $endDate])
            ->sum('jumlah_dana');
        
        // 6. Ambil semua bidang program yang aktif
        $bidangProgramList = BidangProgram::where('aktif', true)->pluck('nama_bidang')->toArray();
        
        // 7. Inisialisasi rincian penyaluran berdasarkan bidang program
        $rincianPenyaluran = array_fill_keys($bidangProgramList, 0);
        
        // 8. Isi data rincian penyaluran berdasarkan bidang program
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
        
        // 9. Untuk Penyaluran Langsung, tidak ada surplus/defisit karena dana tidak masuk ke organisasi
        // Yang penting adalah keseimbangan antara donasi terdokumentasi dengan penyaluran terdokumentasi
        $surplusDefisit = 0; // Tidak ada surplus/defisit untuk Penyaluran Langsung
        
        // 10. Saldo akhir juga 0 karena dana tidak masuk ke kas organisasi
        $saldoAkhir = 0;
        
        // 11. Susun hasil dengan penjelasan khusus
        return [
            'title' => $namaSumberDana,
            'sumber_dana_id' => $sumberDanaId,
            'penerimaan' => $penerimaan, // 0 karena dana tidak masuk ke kas
            'bagian_amil' => $bagianAmil, // 0 karena tidak ada bagian amil
            'rincian_penerimaan' => $rincianPenerimaan, // Untuk dokumentasi saja
            'penyaluran' => $penyaluran, // Penyaluran yang tercatat
            'rincian_penyaluran' => $rincianPenyaluran,
            'rincian_penyaluran_asnaf' => [], // Tidak ada rincian asnaf untuk penyaluran langsung
            'surplus_defisit' => $surplusDefisit, // 0 - tidak ada surplus/defisit
            'saldo_awal' => $saldoAwal, // 0 - tidak ada saldo
            'saldo_akhir' => $saldoAkhir, // 0 - tidak ada saldo
            'total_donasi_terdokumentasi' => $totalDonasiTerdokumentasi, // Untuk informasi
            'is_penyaluran_langsung' => true, // Flag untuk membedakan di view
            'keterangan' => 'Donasi langsung disalurkan oleh donatur tanpa melalui kas organisasi'
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
     * Mendapatkan data penerimaan hak amil berdasarkan pengaturan dinamis dan penggunaan hak amil dari resource PenggunaanHakAmil
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getLaporanHakAmil(string $startDate, string $endDate): array
    {
        // 1. Hitung penerimaan hak amil per jenis donasi berdasarkan pengaturan dinamis
        // EXCLUDE "Penyaluran Langsung" karena ini bukan penerimaan dana biasa
        $jenisDonasiList = JenisDonasi::where('aktif', true)->where('nama', '!=', 'Penyaluran Langsung')->get();
        $penerimaan = [];
        $totalPenerimaan = 0;
        
        foreach ($jenisDonasiList as $jenis) {
            $jumlahDonasi = Donasi::where('jenis_donasi_id', $jenis->id)
                ->where('status_konfirmasi', 'verified')
                ->whereBetween('tanggal_donasi', [$startDate, $endDate])
                ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
                
            if ($jumlahDonasi > 0) {
                // Gunakan persentase hak amil dari sumber dana penyaluran
                $sumberDanaId = $jenis->sumber_dana_penyaluran_id;
                $sumberDana = SumberDanaPenyaluran::find($sumberDanaId);
                
                $persentase = $sumberDana ? ($sumberDana->persentase_hak_amil ?? 0) : 0;
                
                $hakAmil = ($jumlahDonasi * $persentase) / 100;
                $penerimaan[$jenis->nama] = $hakAmil;
                $totalPenerimaan += $hakAmil;
            }
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

    /**
     * Mendapatkan detail pengeluaran berdasarkan asnaf
     */
    public function getDetailPengeluaranAsnaf(string $startDate, string $endDate): array
    {
        $pengeluaranAsnaf = DB::table('program_penyalurans as pp')
            ->join('asnafs as a', 'pp.asnaf_id', '=', 'a.id')
            ->whereBetween('pp.tanggal_penyaluran', [$startDate, $endDate])
            ->select(
                'a.nama_asnaf as nama',
                DB::raw('SUM(pp.jumlah_dana) as total_pengeluaran'),
                DB::raw('COUNT(pp.id) as jumlah_program')
            )
            ->groupBy('a.id', 'a.nama_asnaf')
            ->orderBy('total_pengeluaran', 'desc')
            ->get();

        return $pengeluaranAsnaf->map(function ($item) {
            return [
                'nama' => $item->nama,
                'total_pengeluaran' => $item->total_pengeluaran,
                'jumlah_program' => $item->jumlah_program,
                'persentase' => 0 // Will be calculated later
            ];
        })->toArray();
    }

    /**
     * Mendapatkan detail pengeluaran berdasarkan bidang program
     */
    public function getDetailPengeluaranBidangProgram(string $startDate, string $endDate): array
    {
        $pengeluaranBidang = DB::table('program_penyalurans as pp')
            ->join('bidang_programs as bp', 'pp.bidang_program_id', '=', 'bp.id')
            ->whereBetween('pp.tanggal_penyaluran', [$startDate, $endDate])
            ->select(
                'bp.nama_bidang as nama',
                DB::raw('SUM(pp.jumlah_dana) as total_pengeluaran'),
                DB::raw('COUNT(pp.id) as jumlah_program')
            )
            ->groupBy('bp.id', 'bp.nama_bidang')
            ->orderBy('total_pengeluaran', 'desc')
            ->get();

        return $pengeluaranBidang->map(function ($item) {
            return [
                'nama' => $item->nama,
                'total_pengeluaran' => $item->total_pengeluaran,
                'jumlah_program' => $item->jumlah_program,
                'persentase' => 0 // Will be calculated later
            ];
        })->toArray();
    }

    /**
     * Mendapatkan detail jenis penggunaan hak amil
     */
    public function getDetailJenisPenggunaanAmil(string $startDate, string $endDate): array
    {
        // Ambil semua pengeluaran yang dikategorikan sebagai hak amil
        $penggunaanAmil = DB::table('program_penyalurans as pp')
            ->join('sumber_dana_penyalurans as sdp', 'pp.sumber_dana_penyaluran_id', '=', 'sdp.id')
            ->where('sdp.nama_sumber_dana', 'LIKE', '%hak amil%')
            ->whereBetween('pp.tanggal_penyaluran', [$startDate, $endDate])
            ->select(
                'pp.nama_program as keperluan',
                'pp.jumlah_dana as jumlah',
                'pp.tanggal_penyaluran as tanggal',
                'pp.keterangan'
            )
            ->orderBy('pp.tanggal_penyaluran', 'desc')
            ->get();

        return $penggunaanAmil->map(function ($item) {
            return [
                'keperluan' => $item->keperluan,
                'jumlah' => $item->jumlah,
                'tanggal' => $item->tanggal,
                'keterangan' => $item->keterangan ?? '-'
            ];
        })->toArray();
    }

    /**
     * Mendapatkan detail dana non halal
     */
    public function getDetailDanaNonHalal(string $startDate, string $endDate): array
    {
        // Ambil donasi yang dikategorikan sebagai dana non halal
        $danaNonHalal = DB::table('donasis as d')
            ->join('jenis_donasis as jd', 'd.jenis_donasi_id', '=', 'jd.id')
            ->join('sumber_dana_penyalurans as sdp', 'jd.sumber_dana_penyaluran_id', '=', 'sdp.id')
            ->where('sdp.nama_sumber_dana', 'LIKE', '%non halal%')
            ->where('d.status_konfirmasi', 'verified')
            ->whereBetween('d.tanggal_donasi', [$startDate, $endDate])
            ->select(
                'jd.nama as jenis',
                DB::raw('SUM(d.jumlah + IFNULL(d.perkiraan_nilai_barang, 0)) as total_penerimaan'),
                DB::raw('COUNT(d.id) as jumlah_donasi')
            )
            ->groupBy('jd.id', 'jd.nama')
            ->orderBy('total_penerimaan', 'desc')
            ->get();

        // Ambil pengeluaran dana non halal
        $pengeluaranNonHalal = DB::table('program_penyalurans as pp')
            ->join('sumber_dana_penyalurans as sdp', 'pp.sumber_dana_penyaluran_id', '=', 'sdp.id')
            ->where('sdp.nama_sumber_dana', 'LIKE', '%non halal%')
            ->whereBetween('pp.tanggal_penyaluran', [$startDate, $endDate])
            ->select(
                'pp.nama_program as program',
                'pp.jumlah_dana as jumlah',
                'pp.tanggal_penyaluran as tanggal'
            )
            ->orderBy('pp.tanggal_penyaluran', 'desc')
            ->get();

        return [
            'penerimaan' => $danaNonHalal->map(function ($item) {
                return [
                    'jenis' => $item->jenis,
                    'total_penerimaan' => $item->total_penerimaan,
                    'jumlah_donasi' => $item->jumlah_donasi
                ];
            })->toArray(),
            'pengeluaran' => $pengeluaranNonHalal->map(function ($item) {
                return [
                    'program' => $item->program,
                    'jumlah' => $item->jumlah,
                    'tanggal' => $item->tanggal
                ];
            })->toArray()
        ];
    }

    /**
     * Get detail penyaluran berdasarkan asnaf
     */
    public function getDetailPenyaluranAsnaf(string $startDate, string $endDate): array
    {
        $result = DB::table('program_penyalurans as pp')
            ->join('asnafs as a', 'pp.asnaf_id', '=', 'a.id')
            ->join('sumber_dana_penyalurans as sdp', 'pp.sumber_dana_penyaluran_id', '=', 'sdp.id')
            ->select(
                'a.nama_asnaf',
                'sdp.nama_sumber_dana',
                DB::raw('SUM(pp.jumlah_dana) as total_jumlah'),
                DB::raw('COUNT(pp.id) as jumlah_program')
            )
            ->whereBetween('pp.tanggal_penyaluran', [$startDate, $endDate])
            ->where('a.aktif', true)
            ->groupBy('a.id', 'a.nama_asnaf', 'sdp.id', 'sdp.nama_sumber_dana')
            ->orderBy('a.nama_asnaf')
            ->orderBy('total_jumlah', 'desc')
            ->get()
            ->toArray();

        return array_map(function($item) {
            return (array) $item;
        }, $result);
    }

    /**
     * Get detail penyaluran berdasarkan bidang program
     */
    public function getDetailPenyaluranBidangProgram(string $startDate, string $endDate): array
    {
        $result = DB::table('program_penyalurans as pp')
            ->join('bidang_programs as bp', 'pp.bidang_program_id', '=', 'bp.id')
            ->join('sumber_dana_penyalurans as sdp', 'pp.sumber_dana_penyaluran_id', '=', 'sdp.id')
            ->select(
                'bp.nama_bidang',
                'sdp.nama_sumber_dana',
                DB::raw('SUM(pp.jumlah_dana) as total_jumlah'),
                DB::raw('COUNT(pp.id) as jumlah_program')
            )
            ->whereBetween('pp.tanggal_penyaluran', [$startDate, $endDate])
            ->where('bp.aktif', true)
            ->groupBy('bp.id', 'bp.nama_bidang', 'sdp.id', 'sdp.nama_sumber_dana')
            ->orderBy('bp.nama_bidang')
            ->orderBy('total_jumlah', 'desc')
            ->get()
            ->toArray();

        return array_map(function($item) {
            return (array) $item;
        }, $result);
    }

    /**
     * Get detail penggunaan hak amil berdasarkan jenis
     */
    public function getDetailPenggunaanHakAmilByJenis(string $startDate, string $endDate): array
    {
        $result = DB::table('penggunaan_hak_amils as pha')
            ->join('jenis_penggunaan_hak_amils as jpha', 'pha.jenis_penggunaan_hak_amil_id', '=', 'jpha.id')
            ->select(
                'jpha.nama as jenis_penggunaan',
                DB::raw('SUM(pha.jumlah) as total_jumlah'),
                DB::raw('COUNT(pha.id) as jumlah_transaksi')
            )
            ->whereBetween('pha.tanggal_penggunaan', [$startDate, $endDate])
            ->groupBy('jpha.id', 'jpha.nama')
            ->orderBy('jpha.nama')
            ->get()
            ->toArray();

        return array_map(function($item) {
            return (array) $item;
        }, $result);
    }

    /**
     * Get detail dana non halal berdasarkan kategori
     */
    public function getDetailDanaNonHalalByKategori(string $startDate, string $endDate): array
    {
        $result = DB::table('donasis as d')
            ->join('jenis_donasis as jd', 'd.jenis_donasi_id', '=', 'jd.id')
            ->leftJoin('kategori_dana_non_halal as kdnh', 'd.kategori_dana_non_halal_id', '=', 'kdnh.id')
            ->select(
                DB::raw('COALESCE(kdnh.nama, "Tidak Dikategorikan") as kategori'),
                'jd.nama as jenis_donasi',
                DB::raw('SUM(d.jumlah + IFNULL(d.perkiraan_nilai_barang, 0)) as total_jumlah'),
                DB::raw('COUNT(d.id) as jumlah_donasi')
            )
            ->whereBetween('d.tanggal_donasi', [$startDate, $endDate])
            ->where('d.status_konfirmasi', 'verified')
            ->where('jd.mengandung_dana_non_halal', true)
            ->groupBy('kdnh.id', 'kdnh.nama', 'jd.id', 'jd.nama')
            ->orderBy('kategori')
            ->orderBy('total_jumlah', 'desc')
            ->get()
            ->toArray();

        return array_map(function($item) {
            return (array) $item;
        }, $result);
    }
    
    /**
     * Mendapatkan donasi yang tidak memiliki sumber_dana_penyaluran_id
     */
    private function getDanasiTanpaSumberDanaPenyaluran(string $startDate, string $endDate): array
    {
        // Cari jenis donasi yang tidak memiliki sumber_dana_penyaluran_id
        $jenisDonasiBermasalah = JenisDonasi::whereNull('sumber_dana_penyaluran_id')->pluck('id');
        
        if ($jenisDonasiBermasalah->isEmpty()) {
            return [
                'title' => 'Donasi Lainnya',
                'saldo_awal' => 0,
                'penerimaan' => 0,
                'bagian_amil' => 0,
                'penyaluran' => 0,
                'surplus_defisit' => 0,
                'saldo_akhir' => 0,
                'rincian_penerimaan' => []
            ];
        }
        
        // Hitung penerimaan awal (sebelum startDate)
        $penerimaanAwal = Donasi::whereIn('jenis_donasi_id', $jenisDonasiBermasalah)
            ->where('status_konfirmasi', 'verified')
            ->where('tanggal_donasi', '<', $startDate)
            ->whereHas('jenisDonasi', function($query) {
                $query->where('nama', '!=', 'Penyaluran Langsung');
            })
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            
        // Hitung penerimaan dalam periode
        $penerimaan = Donasi::whereIn('jenis_donasi_id', $jenisDonasiBermasalah)
            ->where('status_konfirmasi', 'verified')
            ->whereBetween('tanggal_donasi', [$startDate, $endDate])
            ->whereHas('jenisDonasi', function($query) {
                $query->where('nama', '!=', 'Penyaluran Langsung');
            })
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            
        // Rincian penerimaan per jenis donasi
        $rincianPenerimaan = Donasi::join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id')
            ->whereIn('donasis.jenis_donasi_id', $jenisDonasiBermasalah)
            ->where('donasis.status_konfirmasi', 'verified')
            ->whereBetween('donasis.tanggal_donasi', [$startDate, $endDate])
            ->where('jenis_donasis.nama', '!=', 'Penyaluran Langsung')
            ->groupBy('jenis_donasis.nama')
            ->select('jenis_donasis.nama', DB::raw('SUM(donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)) as total'))
            ->pluck('total', 'nama')
            ->toArray();
            
        $saldoAwal = max(0, $penerimaanAwal);
        $bagianAmil = 0; // Donasi lainnya tidak ada potongan amil
        $penyaluran = 0; // Untuk sementara, donasi lainnya tidak ada penyaluran langsung
        $surplusDefisit = $penerimaan - $bagianAmil - $penyaluran;
        $saldoAkhir = $saldoAwal + $surplusDefisit;
        
        return [
            'title' => 'Donasi Lainnya (Logistik/Barang)',
            'saldo_awal' => $saldoAwal,
            'penerimaan' => $penerimaan,
            'bagian_amil' => $bagianAmil,
            'penyaluran' => $penyaluran,
            'surplus_defisit' => $surplusDefisit,
            'saldo_akhir' => $saldoAkhir,
            'rincian_penerimaan' => $rincianPenerimaan
        ];
    }
}


