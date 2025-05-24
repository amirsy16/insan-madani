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
            return [
                'title' => 'Sumber Dana Tidak Ditemukan',
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
        
        // DEBUGGING: Periksa relasi antara sumber dana dan jenis donasi
        $relatedJenisDonasi = \App\Models\JenisDonasi::where('sumber_dana_penyaluran_id', $sumberDanaPenyaluranId)
            ->select('id', 'nama', 'sumber_dana_penyaluran_id')
            ->get();
        
        \Illuminate\Support\Facades\Log::info('Jenis Donasi terkait dengan ' . $sumberDana->nama_sumber_dana . ':', $relatedJenisDonasi->toArray());
        
        // DEBUGGING: Periksa donasi berdasarkan jenis donasi
        $jenisDonasisIds = $relatedJenisDonasi->pluck('id')->toArray();
        
        // Hitung penerimaan sebelum periode
        $penerimaanSebelum = 0;
        if (!empty($jenisDonasisIds)) {
            $penerimaanSebelum = \App\Models\Donasi::whereIn('jenis_donasi_id', $jenisDonasisIds)
                ->where('status_konfirmasi', 'verified')
                ->where('tanggal_donasi', '<', $startDate)
                ->sum('jumlah');
        }
        
        // Hitung penyaluran sebelum periode
        $penyaluranSebelum = \App\Models\ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaPenyaluranId)
            ->where('tanggal_penyaluran', '<', $startDate)
            ->sum('jumlah_dana');
        
        // Hitung saldo awal
        $saldoAwal = $penerimaanSebelum - $penyaluranSebelum;
        
        // Hitung penerimaan dan penyaluran dalam periode
        $penerimaanPeriode = 0;
        if (!empty($jenisDonasisIds)) {
            $penerimaanPeriode = \App\Models\Donasi::whereIn('jenis_donasi_id', $jenisDonasisIds)
                ->where('status_konfirmasi', 'verified')
                ->whereBetween('tanggal_donasi', [$startDate, $endDate])
                ->sum('jumlah');
        }
        
        $penyaluranTotalPeriode = \App\Models\ProgramPenyaluran::where('sumber_dana_penyaluran_id', $sumberDanaPenyaluranId)
            ->whereBetween('tanggal_penyaluran', [$startDate, $endDate])
            ->sum('jumlah_dana');
        
        // Hapus kode dummy - gunakan nilai sebenarnya
        // Jika saldo awal negatif, set ke 0 untuk menghindari saldo negatif
        if ($saldoAwal < 0) {
            \Illuminate\Support\Facades\Log::warning('Saldo awal negatif untuk ' . $sumberDana->nama_sumber_dana . ', diatur ke 0');
            $saldoAwal = 0;
        }
        
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











