<?php

namespace App\Filament\Widgets;

use App\Models\Donasi;
use App\Models\Donatur;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class ZakatStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    
    // Tetap menggunakan 4 kolom untuk 4 statistik penting
    protected function getColumns(): int
    {
        return 4;
    }

    // Mengaktifkan mode compact untuk tampilan yang lebih efisien
    protected static array $options = [
        'enableCompactMode' => true,
    ];

    protected function getStats(): array
    {
        // --- Current period data ---
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // --- 1. Total Donasi Keseluruhan ---
        $totalDonasiKeseluruhan = Donasi::where('status_konfirmasi', 'verified')
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));

        // --- 2. Total Donasi Bulan Ini ---
        $totalDonasiBulanIni = Donasi::where('status_konfirmasi', 'verified')
            ->whereMonth('tanggal_donasi', $currentMonth)
            ->whereYear('tanggal_donasi', $currentYear)
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));

        // --- 3. Total Donatur Aktif ---
        $totalDonaturAktif = Donatur::whereHas('donasis', function ($query) {
            $query->where('status_konfirmasi', 'verified');
        })->count();

        // --- 4. Rata-rata Donasi per Transaksi ---
        $avgDonasiPerTransaksi = Donasi::where('status_konfirmasi', 'verified')
            ->avg(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));

        return [
            // 1. Total Donasi Keseluruhan
            Stat::make('Total Keseluruhan', 'Rp ' . number_format($totalDonasiKeseluruhan, 0, ',', '.'))
                ->description('Semua donasi terverifikasi')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary')
                ->chart($this->getDailyDonationAmountsForStat(10)),

            // 2. Total Donasi Bulan Ini
            Stat::make('Total Bulan Ini', 'Rp ' . number_format($totalDonasiBulanIni, 0, ',', '.'))
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),

            // 3. Total Donatur Aktif
            Stat::make('Donatur Aktif', number_format($totalDonaturAktif, 0, ',', '.'))
                ->description('Jumlah donatur yang pernah berdonasi')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('danger'),

            // 4. Rata-rata Donasi per Transaksi
            Stat::make('Rata-rata Donasi', 'Rp ' . number_format($avgDonasiPerTransaksi, 0, ',', '.'))
                ->description('Per transaksi')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),
        ];
    }

    /**
     * Helper function to get daily donation amounts for the chart in Stat.
     * @param int $days
     * @return array
     */
    protected function getDailyDonationAmountsForStat(int $days = 10): array
    {
        $data = [];
        $endDate = Carbon::today();
        $startDate = Carbon::today()->subDays($days - 1);

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $total = Donasi::where('status_konfirmasi', 'verified')
                ->whereDate('tanggal_donasi', $date)
                ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            $data[] = $total;
        }
        return $data;
    }
}


