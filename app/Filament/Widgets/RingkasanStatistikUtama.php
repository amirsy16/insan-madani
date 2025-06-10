<?php

namespace App\Filament\Widgets;

use App\Models\Donasi;
use App\Models\Donatur; // Asumsi ada model Donatur
use App\Models\ProgramPenyaluran;
use App\Services\DanaService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Illuminate\Support\Facades\DB;


class RingkasanStatistikUtama extends BaseWidget
{
    protected static ?int $sort = 1; // Urutan di halaman AnalisisData

    protected int | string | array $columnSpan = 'full';
    
    // Custom view untuk widget dengan filter
    protected static string $view = 'filament.widgets.ringkasan-statistik-utama';

    // Property untuk filter periode
    public string $timePeriod = 'all_time';
    
    // Property untuk toggle show all
    public bool $showAll = false;

    // Method untuk toggle show all
    public function toggleShowAll(): void
    {
        $this->showAll = !$this->showAll;
    }

    // Method untuk mengubah periode filter
    public function setTimePeriod(string $period): void
    {
        $this->timePeriod = $period;
        // Tidak perlu refresh manual, Livewire akan otomatis re-render
    }

    // Override mount untuk inisialisasi
    public function mount(): void
    {
        $this->timePeriod = request('periode', 'all_time');
    }

    // Method untuk mendapatkan opsi filter
    public function getTimePeriodOptions(): array
    {
        return [
            'all_time' => 'Keseluruhan',
            'current_year' => 'Tahun Ini',
            'current_month' => 'Bulan Ini',
        ];
    }

    // Helper untuk label periode
    public function getTimePeriodLabel(): string
    {
        return $this->getTimePeriodOptions()[$this->timePeriod] ?? 'Keseluruhan';
    }

    // Override method untuk menyediakan data ke view
    protected function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'timePeriodLabel' => $this->getTimePeriodLabel(),
            'timePeriodOptions' => $this->getTimePeriodOptions(),
            'currentPeriod' => $this->timePeriod,
            'showAll' => $this->showAll,
        ];
    }

    protected function getStats(): array
    {
        // Apply time period filter
        $kolomTanggalDonasi = 'tanggal_donasi';
        $kolomTanggalPenyaluran = 'tanggal_penyaluran';
        $kolomTanggalPenggunaanHakAmil = 'tanggal';

        // Base queries with time filtering
        $queryDonasi = Donasi::where('status_konfirmasi', 'verified');
        $queryPenyaluran = ProgramPenyaluran::query();
        $queryPenggunaanHakAmil = \App\Models\PenggunaanHakAmil::query();
        
        switch ($this->timePeriod) {
            case 'current_month':
                $currentDate = Carbon::now('Asia/Jakarta');
                $queryDonasi->whereMonth($kolomTanggalDonasi, $currentDate->month)
                           ->whereYear($kolomTanggalDonasi, $currentDate->year);
                $queryPenyaluran->whereMonth($kolomTanggalPenyaluran, $currentDate->month)
                               ->whereYear($kolomTanggalPenyaluran, $currentDate->year);
                $queryPenggunaanHakAmil->whereMonth($kolomTanggalPenggunaanHakAmil, $currentDate->month)
                                      ->whereYear($kolomTanggalPenggunaanHakAmil, $currentDate->year);
                break;
            case 'current_year':
                $currentDate = Carbon::now('Asia/Jakarta');
                $queryDonasi->whereYear($kolomTanggalDonasi, $currentDate->year);
                $queryPenyaluran->whereYear($kolomTanggalPenyaluran, $currentDate->year);
                $queryPenggunaanHakAmil->whereYear($kolomTanggalPenggunaanHakAmil, $currentDate->year);
                break;
            case 'all_time':
            default:
                // No filter
                break;
        }

        // === CORE STATISTICS ===
        
        // 1. Total Donasi (Cash + Goods value)
        $totalDonasi = (clone $queryDonasi)->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
        
        // 2. Total Transaksi Donasi
        $totalTransaksiDonasi = (clone $queryDonasi)->count();
        
        // 3. Total Donatur Aktif 
        $totalDonaturAktif = (clone $queryDonasi)->distinct('donatur_id')->count('donatur_id');
        
        // 4. Total Dana Tersalurkan
        $totalDanaTersalurkan = (clone $queryPenyaluran)->sum('jumlah_dana');
        
        // === HAK AMIL STATISTICS ===
        
        // 5. Total Penerimaan Hak Amil (12.5% dari SEMUA donasi terverifikasi)
        $penerimaanHakAmil = $totalDonasi * 0.125; // 12.5% dari total donasi
        
        // 6. Total Penggunaan Hak Amil
        $penggunaanHakAmil = (clone $queryPenggunaanHakAmil)->sum('jumlah');
        
        // 7. Saldo Hak Amil
        $saldoHakAmil = $penerimaanHakAmil - $penggunaanHakAmil;

        // === RESOURCE-BASED STATISTICS ===
        
        // 8. Total Program Penyaluran Aktif
        $totalProgramAktif = (clone $queryPenyaluran)->count();
        
        // 9. Jumlah Jenis Donasi Aktif
        $jenisDonasiAktif = \App\Models\JenisDonasi::where('aktif', true)->count();
        
        // 10. Jumlah Sumber Dana Aktif
        $sumberDanaAktif = \App\Models\SumberDanaPenyaluran::where('aktif', true)->count();

        // === DONATION TYPE BREAKDOWN ===
        
        // Get totals by donation type (jenis donasi)
        $jenisDonasiTotals = (clone $queryDonasi)
            ->join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id')
            ->select('jenis_donasis.nama', 
                    DB::raw('COUNT(*) as jumlah_transaksi'),
                    DB::raw('SUM(donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)) as total_nilai'))
            ->groupBy('jenis_donasis.id', 'jenis_donasis.nama')
            ->orderByDesc('total_nilai')
            ->get();

        // === TOTAL BY MAIN DONATION CATEGORIES (using DanaService) ===
        
        // Use DanaService to get saldo for each sumber dana penyaluran
        $danaService = new DanaService();
        $allSumberDana = \App\Models\SumberDanaPenyaluran::where('aktif', true)->get();
        
        $sumberDanaTotals = collect();
        $totalZakat = 0;
        $totalInfaq = 0;
        $totalCSR = 0;
        $totalDSKL = 0;
        $totalLainnya = 0;
        
        foreach ($allSumberDana as $sumberDana) {
            $saldo = $danaService->getSaldoTersedia($sumberDana->id);
            
            // Only process sumber dana with saldo > 0
            if ($saldo > 0) {
                // Count transactions for this sumber dana
                $jumlahTransaksi = (clone $queryDonasi)
                    ->join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id')
                    ->where('jenis_donasis.sumber_dana_penyaluran_id', $sumberDana->id)
                    ->count();
                
                $sumberDanaTotals->push((object)[
                    'nama_sumber_dana' => $sumberDana->nama_sumber_dana,
                    'total_nilai' => $saldo,
                    'jumlah_transaksi' => $jumlahTransaksi
                ]);
                
                // Categorize by sumber dana name
                $namaSumber = strtolower(trim($sumberDana->nama_sumber_dana));
                
                if (str_contains($namaSumber, 'zakat')) {
                    $totalZakat += $saldo;
                } elseif (str_contains($namaSumber, 'infaq') || str_contains($namaSumber, 'sedekah')) {
                    $totalInfaq += $saldo;
                } elseif (str_contains($namaSumber, 'csr')) {
                    $totalCSR += $saldo;
                } elseif (str_contains($namaSumber, 'dskl') || str_contains($namaSumber, 'sosial keagamaan')) {
                    $totalDSKL += $saldo;
                } else {
                    $totalLainnya += $saldo;
                }
            }
        }
        
        // Sort by total value descending
        $sumberDanaTotals = $sumberDanaTotals->sortByDesc('total_nilai');

        // === EFFICIENCY METRICS ===
        
        // Rasio Penyaluran
        $rasioPenyaluran = $totalDonasi > 0 ? ($totalDanaTersalurkan / $totalDonasi) * 100 : 0;
        
        // Rata-rata donasi per transaksi
        $rataRataPerTransaksi = $totalTransaksiDonasi > 0 ? $totalDonasi / $totalTransaksiDonasi : 0;

        // === SUMMARY FINANCIAL DATA ===
        
        // Get comprehensive financial summary using DanaService
        $startDate = null;
        $endDate = null;
        
        // Set date range based on time period
        switch ($this->timePeriod) {
            case 'current_month':
                $currentDate = Carbon::now('Asia/Jakarta');
                $startDate = $currentDate->copy()->startOfMonth()->format('Y-m-d');
                $endDate = $currentDate->copy()->endOfMonth()->format('Y-m-d');
                break;
            case 'current_year':
                $currentDate = Carbon::now('Asia/Jakarta');
                $startDate = $currentDate->copy()->startOfYear()->format('Y-m-d');
                $endDate = $currentDate->copy()->endOfYear()->format('Y-m-d');
                break;
            case 'all_time':
            default:
                // For all time, use a very wide range
                $startDate = '2020-01-01'; // Adjust as needed
                $endDate = Carbon::now('Asia/Jakarta')->format('Y-m-d');
                break;
        }
        
        $danaService = new DanaService();
        $laporanData = $danaService->getLaporanPerubahanDana($startDate, $endDate);
        
        // Extract summary data
        $summaryData = $laporanData['summary'] ?? [];
        $totalSaldoAwal = $summaryData['total_saldo_awal'] ?? 0;
        $totalPenerimaanSummary = $summaryData['total_penerimaan'] ?? 0;
        $totalBagianAmilSummary = $summaryData['total_bagian_amil'] ?? 0;
        $totalPenyaluranSummary = $summaryData['total_penyaluran'] ?? 0;
        $totalSurplusDefisit = $summaryData['total_surplus_defisit'] ?? 0;
        $totalSaldoAkhir = $summaryData['total_saldo_akhir'] ?? 0;
        
        // Financial health status
        $getFinancialStatus = function($balance) {
            if ($balance >= 1000000) {
                return ['status' => 'SEHAT', 'color' => 'success', 'icon' => '✅'];
            } elseif ($balance >= 0) {
                return ['status' => 'PERLU PERHATIAN', 'color' => 'warning', 'icon' => '⚠️'];
            } else {
                return ['status' => 'DEFISIT', 'color' => 'danger', 'icon' => '❌'];
            }
        };
        
        $financialStatus = $getFinancialStatus($totalSaldoAkhir);

        $stats = [
            // === ROW 1: CRITICAL FINANCIAL OVERVIEW (ALWAYS VISIBLE) ===
            Stat::make('💰 Total Penerimaan', 'Rp ' . number_format($totalDonasi, 0, ',', '.'))
                ->description('Total donasi terverifikasi (tunai + nilai barang)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
                
            Stat::make('💸 Dana Tersalurkan', 'Rp ' . number_format($totalDanaTersalurkan, 0, ',', '.'))
                ->description('Total dana yang telah disalurkan')
                ->descriptionIcon('heroicon-m-arrow-right-start-on-rectangle')
                ->color('warning'),
                
            Stat::make('🏦 Total Sisa Saldo', 'Rp ' . number_format($totalSaldoAkhir, 0, ',', '.'))
                ->description('Saldo akhir setelah semua distribusi')
                ->descriptionIcon('heroicon-m-building-library')
                ->color($financialStatus['color']),
                
            Stat::make($financialStatus['icon'] . ' Status Keuangan', $financialStatus['status'])
                ->description('Kondisi kesehatan keuangan organisasi')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color($financialStatus['color']),

            // === ROW 2: KEY PERFORMANCE METRICS (ALWAYS VISIBLE) ===
            Stat::make('📈 Rasio Penyaluran', number_format($rasioPenyaluran, 1) . '%')
                ->description('Persentase dana tersalurkan dari penerimaan')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($rasioPenyaluran > 70 ? 'success' : ($rasioPenyaluran > 40 ? 'warning' : 'danger')),
                
            Stat::make('👥 Donatur Aktif', number_format($totalDonaturAktif))
                ->description('Donatur yang berdonasi dalam periode ini')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('📊 Total Transaksi', number_format($totalTransaksiDonasi))
                ->description('Jumlah transaksi donasi terverifikasi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
                
            Stat::make('⚡ Efisiensi Operasional', number_format(($totalPenerimaanSummary > 0 ? ($totalPenyaluranSummary / $totalPenerimaanSummary) * 100 : 0), 1) . '%')
                ->description('Rasio penyaluran dari total penerimaan')
                ->descriptionIcon('heroicon-m-bolt')
                ->color(($totalPenerimaanSummary > 0 && ($totalPenyaluranSummary / $totalPenerimaanSummary) > 0.8) ? 'success' : 'warning'),

            // === ROW 3: DONATION TYPE BREAKDOWN (ALWAYS VISIBLE) ===
            Stat::make('🕌 Total Zakat', 'Rp ' . number_format($totalZakat, 0, ',', '.'))
                ->description('Semua jenis zakat (fitrah, maal, perusahaan, dll)')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),
        ];

        // Only show Total Infaq if it has value > 0
        if ($totalInfaq > 0) {
            $stats[] = Stat::make('💝 Total Infaq', 'Rp ' . number_format($totalInfaq, 0, ',', '.'))
                ->description('Semua jenis infaq (terikat dan tidak terikat)')
                ->descriptionIcon('heroicon-m-gift')
                ->color('blue');
        }

        // === ROW 4: ADDITIONAL DONATION CATEGORIES (IF ANY) ===
        // Only show these if they have values > 0
        if ($totalCSR > 0) {
            $stats[] = Stat::make('🏢 Total CSR', 'Rp ' . number_format($totalCSR, 0, ',', '.'))
                ->description('Dana Corporate Social Responsibility')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('orange');
        }
        
        if ($totalDSKL > 0) {
            $stats[] = Stat::make('🏛️ Total DSKL', 'Rp ' . number_format($totalDSKL, 0, ',', '.'))
                ->description('Dana Sosial Keagamaan Lainnya')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('yellow');
        }
        
        if ($totalLainnya > 0) {
            $stats[] = Stat::make('📦 Dana Lainnya', 'Rp ' . number_format($totalLainnya, 0, ',', '.'))
                ->description('Jenis donasi lainnya yang tidak terkategorisasi')
                ->descriptionIcon('heroicon-m-cube')
                ->color('gray');
        }

        // === ADDITIONAL DETAILED STATS (HIDDEN BY DEFAULT) ===
        $stats[] = Stat::make('🕌 Penerimaan Hak Amil', 'Rp ' . number_format($penerimaanHakAmil, 0, ',', '.'))
            ->description('12.5% dari total donasi terverifikasi')
            ->descriptionIcon('heroicon-m-building-library')
            ->color('success');
            
        $stats[] = Stat::make('💳 Penggunaan Hak Amil', 'Rp ' . number_format($penggunaanHakAmil, 0, ',', '.'))
            ->description('Total pengeluaran hak amil dalam periode ini')
            ->descriptionIcon('heroicon-m-credit-card')
            ->color('danger');
            
        $stats[] = Stat::make('💰 Saldo Hak Amil', 'Rp ' . number_format($saldoHakAmil, 0, ',', '.'))
            ->description('Sisa saldo hak amil (penerimaan - penggunaan)')
            ->descriptionIcon($saldoHakAmil >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($saldoHakAmil >= 0 ? 'success' : 'danger');
            
        $stats[] = Stat::make('🎯 Program Aktif', number_format($totalProgramAktif))
            ->description('Jumlah program penyaluran dalam periode ini')
            ->descriptionIcon('heroicon-m-clipboard-document-list')
            ->color('info');
            
        $stats[] = Stat::make('💎 Total Saldo Awal', 'Rp ' . number_format($totalSaldoAwal, 0, ',', '.'))
            ->description('Saldo awal semua sumber dana')
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('info');
            
        $stats[] = Stat::make('📈 Total Surplus/Defisit', 'Rp ' . number_format($totalSurplusDefisit, 0, ',', '.'))
            ->description('Selisih penerimaan dan penyaluran')
            ->descriptionIcon($totalSurplusDefisit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($totalSurplusDefisit >= 0 ? 'success' : 'danger');
            
        $stats[] = Stat::make('📋 Sumber Data', number_format($jenisDonasiAktif) . ' jenis, ' . number_format($sumberDanaAktif) . ' sumber')
            ->description('Jenis donasi dan sumber dana yang tersedia')
            ->descriptionIcon('heroicon-m-squares-2x2')
            ->color('gray');

        // === ADD DETAILED SUMBER DANA BREAKDOWN STATS ===
        
        // Add individual sumber dana statistics (only show those with values > 0)
        foreach ($sumberDanaTotals as $sumberDana) {
            if ($sumberDana->total_nilai > 0) { // Only show sumber dana with values > 0
                $percentage = $totalDonasi > 0 ? ($sumberDana->total_nilai / $totalDonasi) * 100 : 0;
                $icon = $this->getSumberDanaIcon($sumberDana->nama_sumber_dana);
                $color = $this->getSumberDanaColor($sumberDana->nama_sumber_dana);
                
                $stats[] = Stat::make($icon . ' ' . $sumberDana->nama_sumber_dana, 'Rp ' . number_format($sumberDana->total_nilai, 0, ',', '.'))
                    ->description($sumberDana->jumlah_transaksi . ' transaksi (' . number_format($percentage, 1) . '% dari total)')
                    ->descriptionIcon('heroicon-m-chart-pie')
                    ->color($color);
            }
        }

        // === ADD DETAILED DONATION TYPE BREAKDOWN STATS ===
        
        // Add individual donation type statistics (only show types with values > 0)
        foreach ($jenisDonasiTotals as $index => $jenisDonasi) {
            if ($index < 10 && $jenisDonasi->total_nilai > 0) { // Show top 10 donation types with values > 0
                $percentage = $totalDonasi > 0 ? ($jenisDonasi->total_nilai / $totalDonasi) * 100 : 0;
                
                $stats[] = Stat::make('📊 ' . $jenisDonasi->nama, 'Rp ' . number_format($jenisDonasi->total_nilai, 0, ',', '.'))
                    ->description($jenisDonasi->jumlah_transaksi . ' transaksi (' . number_format($percentage, 1) . '% dari total)')
                    ->descriptionIcon('heroicon-m-chart-bar-square')
                    ->color($this->getDonationTypeColor($jenisDonasi->nama));
            }
        }

        return $stats;
    }

    /**
     * Get appropriate color for donation type based on its name
     */
    private function getDonationTypeColor(string $donationType): string
    {
        $donationType = strtolower(trim($donationType));
        
        // Zakat types - green shades
        if (str_contains($donationType, 'zakat')) {
            if (str_contains($donationType, 'fitrah')) {
                return 'success'; // Green for Zakat Fitrah
            } elseif (str_contains($donationType, 'maal')) {
                return 'emerald'; // Emerald for Zakat Maal
            } elseif (str_contains($donationType, 'perusahaan')) {
                return 'teal'; // Teal for Zakat Perusahaan
            }
            return 'success'; // Default green for other zakat types
        }
        
        // Infaq types - blue shades
        if (str_contains($donationType, 'infaq')) {
            if (str_contains($donationType, 'terikat')) {
                return 'blue'; // Blue for Infaq Terikat
            } elseif (str_contains($donationType, 'tidak terikat')) {
                return 'sky'; // Sky blue for Infaq Tidak Terikat
            }
            return 'blue'; // Default blue for other infaq types
        }
        
        // Sedekah - purple
        if (str_contains($donationType, 'sedekah')) {
            return 'purple';
        }
        
        // CSR - orange
        if (str_contains($donationType, 'csr')) {
            return 'orange';
        }
        
        // DSKL - yellow
        if (str_contains($donationType, 'dskl') || str_contains($donationType, 'sosial keagamaan')) {
            return 'yellow';
        }
        
        // Donasi barang/logistik - gray
        if (str_contains($donationType, 'barang') || str_contains($donationType, 'logistik')) {
            return 'gray';
        }
        
        // Default colors for other types
        $colors = ['indigo', 'pink', 'red', 'amber', 'lime', 'cyan', 'violet', 'rose'];
        return $colors[abs(crc32($donationType)) % count($colors)];
    }

    /**
     * Get appropriate icon for sumber dana based on its name
     */
    private function getSumberDanaIcon(string $sumberDana): string
    {
        $sumberDana = strtolower(trim($sumberDana));
        
        if (str_contains($sumberDana, 'zakat')) {
            return '🕌';
        } elseif (str_contains($sumberDana, 'infaq') || str_contains($sumberDana, 'sedekah')) {
            return '💝';
        } elseif (str_contains($sumberDana, 'csr')) {
            return '🏢';
        } elseif (str_contains($sumberDana, 'dskl') || str_contains($sumberDana, 'sosial keagamaan')) {
            return '🏛️';
        } elseif (str_contains($sumberDana, 'hak amil')) {
            return '💰';
        } else {
            return '📦';
        }
    }

    /**
     * Get appropriate color for sumber dana based on its name
     */
    private function getSumberDanaColor(string $sumberDana): string
    {
        $sumberDana = strtolower(trim($sumberDana));
        
        if (str_contains($sumberDana, 'zakat')) {
            return 'success';
        } elseif (str_contains($sumberDana, 'infaq') || str_contains($sumberDana, 'sedekah')) {
            return 'blue';
        } elseif (str_contains($sumberDana, 'csr')) {
            return 'orange';
        } elseif (str_contains($sumberDana, 'dskl') || str_contains($sumberDana, 'sosial keagamaan')) {
            return 'yellow';
        } elseif (str_contains($sumberDana, 'hak amil')) {
            return 'purple';
        } else {
            return 'gray';
        }
    }
}