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

        return [
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

            // === ADDITIONAL DETAILED STATS (HIDDEN BY DEFAULT) ===
            Stat::make('🕌 Penerimaan Hak Amil', 'Rp ' . number_format($penerimaanHakAmil, 0, ',', '.'))
                ->description('12.5% dari total donasi terverifikasi')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),
                
            Stat::make('💳 Penggunaan Hak Amil', 'Rp ' . number_format($penggunaanHakAmil, 0, ',', '.'))
                ->description('Total pengeluaran hak amil dalam periode ini')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('danger'),
                
            Stat::make('💰 Saldo Hak Amil', 'Rp ' . number_format($saldoHakAmil, 0, ',', '.'))
                ->description('Sisa saldo hak amil (penerimaan - penggunaan)')
                ->descriptionIcon($saldoHakAmil >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($saldoHakAmil >= 0 ? 'success' : 'danger'),
                
            Stat::make('🎯 Program Aktif', number_format($totalProgramAktif))
                ->description('Jumlah program penyaluran dalam periode ini')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),
                
            Stat::make('💎 Total Saldo Awal', 'Rp ' . number_format($totalSaldoAwal, 0, ',', '.'))
                ->description('Saldo awal semua sumber dana')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
                
            Stat::make('📈 Total Surplus/Defisit', 'Rp ' . number_format($totalSurplusDefisit, 0, ',', '.'))
                ->description('Selisih penerimaan dan penyaluran')
                ->descriptionIcon($totalSurplusDefisit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($totalSurplusDefisit >= 0 ? 'success' : 'danger'),
                
            Stat::make('📋 Sumber Data', number_format($jenisDonasiAktif) . ' jenis, ' . number_format($sumberDanaAktif) . ' sumber')
                ->description('Jenis donasi dan sumber dana yang tersedia')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('gray'),
        ];
    }
}
