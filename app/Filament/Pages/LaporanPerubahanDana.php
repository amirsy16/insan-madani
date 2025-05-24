<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use App\Models\Donasi;
use App\Models\ProgramPenyaluran;
use App\Models\SumberDanaPenyaluran;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LaporanPerubahanDana extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static string $view = 'filament.pages.laporan-perubahan-dana';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Perubahan Dana';
    protected static ?int $navigationSort = 2;

    // Properti untuk filter dan data
    public ?string $startDate = null;
    public ?string $endDate = null;
    public array $reportData = [];

    public function mount(): void
    {
        // Ubah default tanggal menjadi rentang yang mencakup data historis
        $this->startDate = '2020-01-01'; // Tanggal yang sangat awal
        $this->endDate = now()->endOfDay()->format('Y-m-d');
        
        // Tambahkan logging untuk debugging
        \Illuminate\Support\Facades\Log::info('Laporan Perubahan Dana - Date Range', [
            'startDate' => $this->startDate,
            'endDate' => $this->endDate
        ]);
        
        $this->generateReport();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Filter Laporan')
                ->columns(2)
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Dari Tanggal')
                        ->reactive()
                        ->default(now()->startOfYear()),
                    DatePicker::make('endDate')
                        ->label('Sampai Tanggal')
                        ->reactive()
                        ->default(now()),
                ]),
        ];
    }

    // Fungsi ini akan dipanggil setiap kali filter berubah
    public function updated($propertyName): void
    {
        $this->generateReport();
    }

  public function generateReport(): void
    {
        $this->reportData = [];
        
        // Dapatkan semua sumber dana penyaluran yang aktif
        $sumberDanaPenyalurans = SumberDanaPenyaluran::where('aktif', true)->get();
        
        foreach ($sumberDanaPenyalurans as $sumberDana) {
            $this->reportData[$sumberDana->id] = $this->calculateFundTypeMetrics($sumberDana->id);
        }
        
        // Tambahkan total dari semua sumber dana
        $this->reportData['total'] = $this->calculateCombinedTotals();
        
        // Pastikan semua kunci yang dibutuhkan tersedia dalam data
        foreach ($this->reportData as $key => $data) {
            if (!isset($data['penerimaan_sebelum'])) {
                $this->reportData[$key]['penerimaan_sebelum'] = 0;
            }
            if (!isset($data['penyaluran_sebelum'])) {
                $this->reportData[$key]['penyaluran_sebelum'] = 0;
            }
            if (!isset($data['debug'])) {
                $this->reportData[$key]['debug'] = [];
            }
            if (!isset($data['debug']['penerimaan_sebelum'])) {
                $this->reportData[$key]['debug']['penerimaan_sebelum'] = $this->reportData[$key]['penerimaan_sebelum'];
            }
            if (!isset($data['debug']['penyaluran_sebelum'])) {
                $this->reportData[$key]['debug']['penyaluran_sebelum'] = $this->reportData[$key]['penyaluran_sebelum'];
            }
        }
    }

    private function calculateFundTypeMetrics($sumberDanaPenyaluranId): array
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        
        // Dapatkan informasi sumber dana
        $sumberDana = SumberDanaPenyaluran::find($sumberDanaPenyaluranId);
        
        if (!$sumberDana) {
            \Illuminate\Support\Facades\Log::error('Sumber Dana tidak ditemukan dengan ID: ' . $sumberDanaPenyaluranId);
            return $this->getEmptyMetricsArray('Sumber Dana Tidak Ditemukan');
        }
        
        // DEBUGGING: Periksa ID sumber dana
        \Illuminate\Support\Facades\Log::info('Sumber Dana yang sedang dihitung: ', [
            'id' => $sumberDana->id,
            'nama' => $sumberDana->nama_sumber_dana
        ]);
        
        // DEBUGGING: Periksa relasi antara sumber dana dan jenis donasi
        $relatedJenisDonasi = \App\Models\JenisDonasi::where('sumber_dana_penyaluran_id', $sumberDanaPenyaluranId)
            ->select('id', 'nama', 'sumber_dana_penyaluran_id')
            ->get();
        
        \Illuminate\Support\Facades\Log::info('Jenis Donasi terkait dengan ' . $sumberDana->nama_sumber_dana . ':', $relatedJenisDonasi->toArray());
        
        // DEBUGGING: Periksa donasi berdasarkan jenis donasi
        $jenisDonasisIds = $relatedJenisDonasi->pluck('id')->toArray();
        
        // DEBUGGING: Periksa apakah ada jenis donasi yang terkait
        if (empty($jenisDonasisIds)) {
            \Illuminate\Support\Facades\Log::warning('Tidak ada jenis donasi yang terkait dengan sumber dana: ' . $sumberDana->nama_sumber_dana);
            
            // Coba periksa apakah ada masalah dengan nama kolom relasi
            $allJenisDonasi = \App\Models\JenisDonasi::select('id', 'nama', 'sumber_dana_penyaluran_id')->get();
            \Illuminate\Support\Facades\Log::info('Semua jenis donasi:', $allJenisDonasi->toArray());
            
            // Cari jenis donasi berdasarkan nama yang mirip dengan sumber dana
            if (str_contains(strtolower($sumberDana->nama_sumber_dana), 'zakat')) {
                $jenisDonasisIds = \App\Models\JenisDonasi::where('nama', 'like', '%Zakat%')
                    ->pluck('id')
                    ->toArray();
                \Illuminate\Support\Facades\Log::info('Mencoba mencari jenis donasi berdasarkan nama "Zakat":', $jenisDonasisIds);
                
                // Perbaiki relasi di database (solusi permanen)
                $this->updateJenisDonasiRelation($jenisDonasisIds, $sumberDanaPenyaluranId);
            } elseif (str_contains(strtolower($sumberDana->nama_sumber_dana), 'infaq') || 
                      str_contains(strtolower($sumberDana->nama_sumber_dana), 'sedekah')) {
                $jenisDonasisIds = \App\Models\JenisDonasi::where('nama', 'like', '%Infaq%')
                    ->orWhere('nama', 'like', '%Sedekah%')
                    ->pluck('id')
                    ->toArray();
                \Illuminate\Support\Facades\Log::info('Mencoba mencari jenis donasi berdasarkan nama "Infaq/Sedekah":', $jenisDonasisIds);
                
                // Perbaiki relasi di database (solusi permanen)
                $this->updateJenisDonasiRelation($jenisDonasisIds, $sumberDanaPenyaluranId);
            } elseif (str_contains(strtolower($sumberDana->nama_sumber_dana), 'csr')) {
                $jenisDonasisIds = \App\Models\JenisDonasi::where('nama', 'like', '%CSR%')
                    ->pluck('id')
                    ->toArray();
                \Illuminate\Support\Facades\Log::info('Mencoba mencari jenis donasi berdasarkan nama "CSR":', $jenisDonasisIds);
                
                // Perbaiki relasi di database (solusi permanen)
                $this->updateJenisDonasiRelation($jenisDonasisIds, $sumberDanaPenyaluranId);
            } elseif (str_contains(strtolower($sumberDana->nama_sumber_dana), 'dskl')) {
                $jenisDonasisIds = \App\Models\JenisDonasi::where('nama', 'like', '%DSKL%')
                    ->pluck('id')
                    ->toArray();
                \Illuminate\Support\Facades\Log::info('Mencoba mencari jenis donasi berdasarkan nama "DSKL":', $jenisDonasisIds);
                
                // Perbaiki relasi di database (solusi permanen)
                $this->updateJenisDonasiRelation($jenisDonasisIds, $sumberDanaPenyaluranId);
            }
            
            // Periksa apakah ada donasi yang tidak terkait dengan jenis donasi manapun
            $donasisWithoutJenisDonasi = \App\Models\Donasi::whereNull('jenis_donasi_id')
                ->orWhere('jenis_donasi_id', 0)
                ->count();
            \Illuminate\Support\Facades\Log::info('Jumlah donasi tanpa jenis donasi: ' . $donasisWithoutJenisDonasi);
        }
        
        // Hitung penerimaan sebelum periode
        $penerimaanSebelum = 0;
        if (!empty($jenisDonasisIds)) {
            $penerimaanSebelum = \App\Models\Donasi::whereIn('jenis_donasi_id', $jenisDonasisIds)
                ->where('status_konfirmasi', 'verified')
                ->where('tanggal_donasi', '<', $startDate)
                ->sum('jumlah');
            
            // DEBUGGING: Periksa query donasi
            $donasiCount = \App\Models\Donasi::whereIn('jenis_donasi_id', $jenisDonasisIds)
                ->where('status_konfirmasi', 'verified')
                ->where('tanggal_donasi', '<', $startDate)
                ->count();
            \Illuminate\Support\Facades\Log::info('Jumlah donasi sebelum ' . $startDate->format('Y-m-d') . ': ' . $donasiCount);
        }
        
        // Hitung penyaluran sebelum periode
        $penyaluranSebelum = \App\Models\ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaPenyaluranId)
            ->where('tanggal_penyaluran', '<', $startDate)
            ->sum('jumlah_dana');
        
        // DEBUGGING: Periksa query penyaluran
        $penyaluranCount = \App\Models\ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaPenyaluranId)
            ->where('tanggal_penyaluran', '<', $startDate)
            ->count();
        \Illuminate\Support\Facades\Log::info('Jumlah penyaluran sebelum ' . $startDate->format('Y-m-d') . ': ' . $penyaluranCount);
        
        // Hitung saldo awal
        $saldoAwal = $penerimaanSebelum - $penyaluranSebelum;
        
        // Hitung penerimaan dan penyaluran dalam periode
        $penerimaanPeriode = 0;
        if (!empty($jenisDonasisIds)) {
            $penerimaanPeriode = \App\Models\Donasi::whereIn('jenis_donasi_id', $jenisDonasisIds)
                ->where('status_konfirmasi', 'verified')
                ->whereBetween('tanggal_donasi', [$startDate, $endDate])
                ->sum('jumlah');
            
            // DEBUGGING: Periksa query donasi dalam periode
            $donasiPeriodeCount = \App\Models\Donasi::whereIn('jenis_donasi_id', $jenisDonasisIds)
                ->where('status_konfirmasi', 'verified')
                ->whereBetween('tanggal_donasi', [$startDate, $endDate])
                ->count();
            \Illuminate\Support\Facades\Log::info('Jumlah donasi dalam periode: ' . $donasiPeriodeCount);
        }
        
        $penyaluranTotalPeriode = \App\Models\ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaPenyaluranId)
            ->whereBetween('tanggal_penyaluran', [$startDate, $endDate])
            ->sum('jumlah_dana');
        
        // DEBUGGING: Periksa query penyaluran dalam periode
        $penyaluranPeriodeCount = \App\Models\ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaPenyaluranId)
            ->whereBetween('tanggal_penyaluran', [$startDate, $endDate])
            ->count();
        \Illuminate\Support\Facades\Log::info('Jumlah penyaluran dalam periode: ' . $penyaluranPeriodeCount);
        
        // 4. SURPLUS / DEFISIT
        $surplusDefisit = $penerimaanPeriode - $penyaluranTotalPeriode;
        
        // 5. SALDO AKHIR
        $saldoAkhir = $saldoAwal + $surplusDefisit;
        
        // Log hasil perhitungan
        \Illuminate\Support\Facades\Log::info('Hasil perhitungan untuk ' . $sumberDana->nama_sumber_dana, [
            'saldoAwal' => $saldoAwal,
            'penerimaanSebelum' => $penerimaanSebelum,
            'penyaluranSebelum' => $penyaluranSebelum,
            'penerimaanPeriode' => $penerimaanPeriode,
            'penyaluranTotalPeriode' => $penyaluranTotalPeriode,
            'surplusDefisit' => $surplusDefisit,
            'saldoAkhir' => $saldoAkhir
        ]);
        
        return [
            'title' => $sumberDana->nama_sumber_dana,
            'saldo_awal' => $saldoAwal,
            'penerimaan_sebelum' => $penerimaanSebelum,
            'penyaluran_sebelum' => $penyaluranSebelum,
            'penerimaan' => $penerimaanPeriode,
            'penyaluran' => $penyaluranTotalPeriode,
            'rincian_penyaluran' => [], // Diisi sesuai kebutuhan
            'persentase_penyaluran' => [], // Diisi sesuai kebutuhan
            'surplus_defisit' => $surplusDefisit,
            'saldo_akhir' => $saldoAkhir,
            'is_zakat' => str_contains(strtolower($sumberDana->nama_sumber_dana), 'zakat'),
            'bagian_amil' => 0, // Diisi sesuai kebutuhan
            'debug' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'sumber_dana_id' => $sumberDanaPenyaluranId,
                'sumber_dana_nama' => $sumberDana->nama_sumber_dana,
                'jenis_donasi_ids' => $jenisDonasisIds,
                'penerimaan_sebelum' => $penerimaanSebelum,
                'penyaluran_sebelum' => $penyaluranSebelum,
            ],
        ];
    }

    /**
     * Memperbarui relasi antara JenisDonasi dan SumberDanaPenyaluran
     * 
     * @param array $jenisDonasisIds
     * @param int $sumberDanaPenyaluranId
     * @return void
     */
    private function updateJenisDonasiRelation(array $jenisDonasisIds, int $sumberDanaPenyaluranId): void
    {
        try {
            // Hanya update jika ada jenis donasi yang ditemukan
            if (!empty($jenisDonasisIds)) {
                // Gunakan transaksi untuk memastikan semua update berhasil atau tidak sama sekali
                \Illuminate\Support\Facades\DB::beginTransaction();
                
                foreach ($jenisDonasisIds as $jenisDonasisId) {
                    \App\Models\JenisDonasi::where('id', $jenisDonasisId)
                        ->update(['sumber_dana_penyaluran_id' => $sumberDanaPenyaluranId]);
                }
                
                \Illuminate\Support\Facades\DB::commit();
                
                \Illuminate\Support\Facades\Log::info('Berhasil memperbarui relasi jenis donasi dengan sumber dana', [
                    'jenis_donasi_ids' => $jenisDonasisIds,
                    'sumber_dana_id' => $sumberDanaPenyaluranId
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Gagal memperbarui relasi jenis donasi dengan sumber dana', [
                'error' => $e->getMessage(),
                'jenis_donasi_ids' => $jenisDonasisIds,
                'sumber_dana_id' => $sumberDanaPenyaluranId
            ]);
        }
    }

    private function getEmptyMetricsArray($title = 'Data Tidak Tersedia'): array
    {
        return [
            'title' => $title,
            'saldo_awal' => 0,
            'penerimaan_sebelum' => 0,
            'penyaluran_sebelum' => 0,
            'penerimaan' => 0,
            'penyaluran' => 0,
            'rincian_penyaluran' => [],
            'persentase_penyaluran' => [],
            'surplus_defisit' => 0,
            'saldo_akhir' => 0,
            'is_zakat' => false,
            'bagian_amil' => 0,
            'debug' => [
                'penerimaan_sebelum' => 0,
                'penyaluran_sebelum' => 0,
            ],
        ];
    }

    private function calculateCombinedTotals(): array
    {
        // Initialize totals
        $totalSaldoAwal = 0;
        $totalPenerimaanSebelum = 0; // Tambahkan ini
        $totalPenyaluranSebelum = 0; // Tambahkan ini
        $totalPenerimaan = 0;
        $totalPenyaluran = 0;
        $totalSurplusDefisit = 0;
        $totalSaldoAkhir = 0;
        $combinedRincianPenyaluran = [];

        // Exclude 'total' key when summing
        foreach ($this->reportData as $key => $data) {
            if ($key !== 'total') {
                $totalSaldoAwal += $data['saldo_awal'];
                $totalPenerimaanSebelum += $data['penerimaan_sebelum'] ?? 0; // Gunakan null coalescing
                $totalPenyaluranSebelum += $data['penyaluran_sebelum'] ?? 0; // Gunakan null coalescing
                $totalPenerimaan += $data['penerimaan'];
                $totalPenyaluran += $data['penyaluran'];
                $totalSurplusDefisit += $data['surplus_defisit'];
                $totalSaldoAkhir += $data['saldo_akhir'];

                // Combine distribution details
                foreach ($data['rincian_penyaluran'] as $kategori => $total) {
                    if (!isset($combinedRincianPenyaluran[$kategori])) {
                        $combinedRincianPenyaluran[$kategori] = 0;
                    }
                    $combinedRincianPenyaluran[$kategori] += $total;
                }
            }
        }

        // Calculate percentages for combined distribution
        $persentasePenyaluran = [];
        foreach ($combinedRincianPenyaluran as $kategori => $total) {
            $persentasePenyaluran[$kategori] = $totalPenyaluran > 0 
                ? ($total / $totalPenyaluran) * 100 
                : 0;
        }

        return [
            'title' => 'Total Semua Dana',
            'saldo_awal' => $totalSaldoAwal,
            'penerimaan_sebelum' => $totalPenerimaanSebelum, // Tambahkan ini
            'penyaluran_sebelum' => $totalPenyaluranSebelum, // Tambahkan ini
            'penerimaan' => $totalPenerimaan,
            'penyaluran' => $totalPenyaluran,
            'rincian_penyaluran' => $combinedRincianPenyaluran,
            'persentase_penyaluran' => $persentasePenyaluran,
            'surplus_defisit' => $totalSurplusDefisit,
            'saldo_akhir' => $totalSaldoAkhir,
            'is_zakat' => false,
        ];
    }
}














