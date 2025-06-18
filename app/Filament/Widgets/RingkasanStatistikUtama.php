<?php

namespace App\Filament\Widgets;

use App\Models\Donasi;
use App\Models\Donatur;
use App\Models\ProgramPenyaluran;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RingkasanStatistikUtama extends Widget
{
    protected static string $view = 'filament.widgets.ringkasan-statistik-utama';
    
    protected ?string $heading = '📊 Dashboard Keuangan Madani - Ringkasan Lengkap';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static bool $isDiscovered = false;

    // Properties untuk blade view
    public $currentPeriod = 'keseluruhan';
    public $showAll = false;
    public $timePeriodOptions = [
        'keseluruhan' => 'Keseluruhan',
        'tahun_ini' => 'Tahun Ini',
        'bulan_ini' => 'Bulan Ini',
    ];

    public function mount()
    {
        $this->currentPeriod = request()->get('period', 'keseluruhan');
        $this->showAll = false;
    }

    public function setTimePeriod($period)
    {
        $this->currentPeriod = $period;
    }

    public function toggleShowAll()
    {
        $this->showAll = !$this->showAll;
    }

    public function getTimePeriodLabelProperty()
    {
        return $this->timePeriodOptions[$this->currentPeriod] ?? 'Keseluruhan';
    }

    public function getStatsProperty()
    {
        $stats = $this->getStats();
        
        // Debug: Log jumlah statistik yang dihasilkan
        logger()->info('RingkasanStatistikUtama - Total stats generated: ' . count($stats));
        
        return $stats;
    }

    protected function getStats(): array
    {
        // Tentukan periode berdasarkan filter
        $filter = $this->currentPeriod ?? 'keseluruhan';
        
        // Siapkan constraint tanggal berdasarkan filter
        $dateConstraint = $this->getDateConstraint($filter);
        
        // TOTAL DONASI BERDASARKAN FILTER (EXCLUDE PENYALURAN LANGSUNG)
        $totalDonasiQuery = Donasi::where('status_konfirmasi', 'verified')
            ->whereHas('jenisDonasi', function($query) {
                $query->where('nama', '!=', 'Penyaluran Langsung');
            });
            
        if ($dateConstraint) {
            $totalDonasiQuery->whereBetween('tanggal_donasi', $dateConstraint);
        }
        
        $totalDonasi = $totalDonasiQuery->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
        
        // BREAKDOWN PER KATEGORI
        $totalZakat = $this->getTotalByCategory('zakat', $dateConstraint);
        $totalInfaq = $this->getTotalByCategory('infaq', $dateConstraint);
        $totalCSR = $this->getTotalByCategory('csr', $dateConstraint);
        $totalDSKL = $this->getTotalByCategory('dskl', $dateConstraint);

        // STATISTIK TAMBAHAN
        $totalTransaksiQuery = Donasi::where('status_konfirmasi', 'verified')
            ->whereHas('jenisDonasi', function($query) {
                $query->where('nama', '!=', 'Penyaluran Langsung');
            });
            
        if ($dateConstraint) {
            $totalTransaksiQuery->whereBetween('tanggal_donasi', $dateConstraint);
        }
        
        $totalTransaksi = $totalTransaksiQuery->count();
        $totalDonatur = $totalTransaksiQuery->distinct('donatur_id')->count('donatur_id');
        
        // Statistik penyaluran
        $totalDanaTersalurkanQuery = ProgramPenyaluran::where('sumber_dana_penyaluran_id', '!=', 591);
        if ($dateConstraint) {
            $totalDanaTersalurkanQuery->whereBetween('tanggal_penyaluran', $dateConstraint);
        }
        $totalDanaTersalurkan = $totalDanaTersalurkanQuery->sum('jumlah_dana');
        $sisaDana = $totalDonasi - $totalDanaTersalurkan;
        
        // Efisiensi penyaluran
        $efisiensiPenyaluran = $totalDonasi > 0 ? ($totalDanaTersalurkan / $totalDonasi) * 100 : 0;
        
        // STATISTIK PENYALURAN LANGSUNG (TERPISAH)
        $totalPenyaluranLangsungQuery = ProgramPenyaluran::where('sumber_dana_penyaluran_id', 591);
        if ($dateConstraint) {
            $totalPenyaluranLangsungQuery->whereBetween('tanggal_penyaluran', $dateConstraint);
        }
        $totalPenyaluranLangsung = $totalPenyaluranLangsungQuery->sum('jumlah_dana');
        $totalTransaksiPenyaluranLangsung = $totalPenyaluranLangsungQuery->count();

        $baseStats = [
            [
                'label' => 'TOTAL DANA TERHIMPUN',
                'value' => 'Rp ' . number_format($totalDonasi, 0, ',', '.'),
                'description' => $totalTransaksi . ' transaksi dari ' . $totalDonatur . ' donatur',
                'icon' => '💰',
                'color' => 'success'
            ],
            [
                'label' => 'DANA ZAKAT',
                'value' => 'Rp ' . number_format($totalZakat, 0, ',', '.'),
                'description' => number_format(($totalZakat / max($totalDonasi, 1)) * 100, 1) . '% dari total',
                'icon' => '🕌',
                'color' => 'success'
            ],
            [
                'label' => 'INFAQ & SEDEKAH',
                'value' => 'Rp ' . number_format($totalInfaq, 0, ',', '.'),
                'description' => number_format(($totalInfaq / max($totalDonasi, 1)) * 100, 1) . '% dari total',
                'icon' => '💝',
                'color' => 'info'
            ],
            [
                'label' => 'DANA CSR',
                'value' => 'Rp ' . number_format($totalCSR, 0, ',', '.'),
                'description' => number_format(($totalCSR / max($totalDonasi, 1)) * 100, 1) . '% dari total',
                'icon' => '🏢',
                'color' => 'warning'
            ],
            [
                'label' => 'DANA SOSIAL KEAGAMAAN',
                'value' => 'Rp ' . number_format($totalDSKL, 0, ',', '.'),
                'description' => number_format(($totalDSKL / max($totalDonasi, 1)) * 100, 1) . '% dari total',
                'icon' => '🏛️',
                'color' => 'gray'
            ],
            [
                'label' => 'DANA TERSALURKAN',
                'value' => 'Rp ' . number_format($totalDanaTersalurkan, 0, ',', '.'),
                'description' => 'Efisiensi: ' . number_format($efisiensiPenyaluran, 1) . '%',
                'icon' => '📤',
                'color' => $efisiensiPenyaluran > 70 ? 'success' : 'warning'
            ],
            [
                'label' => 'SISA DANA TERSEDIA',
                'value' => 'Rp ' . number_format($sisaDana, 0, ',', '.'),
                'description' => number_format(($sisaDana / max($totalDonasi, 1)) * 100, 1) . '% tersedia',
                'icon' => '💼',
                'color' => $sisaDana > 0 ? 'success' : 'danger'
            ],
            [
                'label' => 'PENYALURAN LANGSUNG',
                'value' => 'Rp ' . number_format($totalPenyaluranLangsung, 0, ',', '.'),
                'description' => $totalTransaksiPenyaluranLangsung . ' transaksi',
                'icon' => '🔄',
                'color' => 'info'
            ]
        ];

        // Return base stats or extended stats based on showAll property
        return $baseStats;
    }

    private function getDateConstraint(?string $filter): ?array
    {
        switch ($filter) {
            case 'tahun_ini':
                return [
                    Carbon::now()->startOfYear()->format('Y-m-d'),
                    Carbon::now()->endOfYear()->format('Y-m-d')
                ];
            case 'bulan_ini':
                return [
                    Carbon::now()->startOfMonth()->format('Y-m-d'),
                    Carbon::now()->endOfMonth()->format('Y-m-d')
                ];
            case 'keseluruhan':
            default:
                return null;
        }
    }

    private function getTotalByCategory(string $category, ?array $dateConstraint): float
    {
        $query = Donasi::join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id')
            ->join('sumber_dana_penyalurans', 'jenis_donasis.sumber_dana_penyaluran_id', '=', 'sumber_dana_penyalurans.id')
            ->where('donasis.status_konfirmasi', 'verified')
            ->where('jenis_donasis.nama', '!=', 'Penyaluran Langsung');

        if ($dateConstraint) {
            $query->whereBetween('donasis.tanggal_donasi', $dateConstraint);
        }

        switch ($category) {
            case 'zakat':
                $query->where('sumber_dana_penyalurans.nama_sumber_dana', 'LIKE', '%zakat%');
                break;
            case 'infaq':
                $query->where(function($q) {
                    $q->where('sumber_dana_penyalurans.nama_sumber_dana', 'LIKE', '%infaq%')
                      ->orWhere('sumber_dana_penyalurans.nama_sumber_dana', 'LIKE', '%sedekah%');
                });
                break;
            case 'csr':
                $query->where('sumber_dana_penyalurans.nama_sumber_dana', 'LIKE', '%csr%');
                break;
            case 'dskl':
                $query->where(function($q) {
                    $q->where('sumber_dana_penyalurans.nama_sumber_dana', 'LIKE', '%dskl%')
                      ->orWhere('sumber_dana_penyalurans.nama_sumber_dana', 'LIKE', '%sosial keagamaan%');
                });
                break;
            default:
                return 0;
        }

        return $query->sum(DB::raw('donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)')) ?? 0;
    }
}
