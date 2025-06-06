<?php

namespace App\Filament\Resources\DonasiResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Donasi;
use App\Models\JenisDonasi;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;

class DonasiStat extends BaseWidget
{
    use HasWidgetShield;
    
    // Make sure the widget is protected
    protected static ?string $pollingInterval = null;
    
    // Add this line to ensure the widget is registered correctly
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        // Get the filtered query if available
        $query = app()->bound('donasi.filtered.query') 
            ? app()->make('donasi.filtered.query') 
            : Donasi::query();
            
        // Current date, month and year
        $currentDate = Carbon::now();
        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;
            
        // Calculate metrics
        // 1. Total Donasi Keseluruhan
        $totalDonasiKeseluruhan = (clone $query)
            ->where('status_konfirmasi', 'verified')
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
        $totalTransaksiKeseluruhan = (clone $query)
            ->where('status_konfirmasi', 'verified')
            ->count();
            
        // 2. Total Donasi Tahun Ini
        $totalDonasiTahunIni = (clone $query)
            ->where('status_konfirmasi', 'verified')
            ->whereYear('tanggal_donasi', $currentYear)
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
        $totalTransaksiTahunIni = (clone $query)
            ->where('status_konfirmasi', 'verified')
            ->whereYear('tanggal_donasi', $currentYear)
            ->count();
            
        // 3. Total Donasi Bulan Ini
        $totalDonasiBulanIni = (clone $query)
            ->where('status_konfirmasi', 'verified')
            ->whereMonth('tanggal_donasi', $currentMonth)
            ->whereYear('tanggal_donasi', $currentYear)
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
        $totalTransaksiBulanIni = (clone $query)
            ->where('status_konfirmasi', 'verified')
            ->whereMonth('tanggal_donasi', $currentMonth)
            ->whereYear('tanggal_donasi', $currentYear)
            ->count();
            
        // 4. Total Donasi Hari Ini
        $totalDonasiHariIni = (clone $query)
            ->where('status_konfirmasi', 'verified')
            ->whereDate('tanggal_donasi', $currentDate->toDateString())
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
        $totalTransaksiHariIni = (clone $query)
            ->where('status_konfirmasi', 'verified')
            ->whereDate('tanggal_donasi', $currentDate->toDateString())
            ->count();
        
        return [
            Stat::make('Total Donasi Keseluruhan', 'Rp ' . number_format($totalDonasiKeseluruhan, 0, ',', '.'))
                ->description('Total transaksi: ' . number_format($totalTransaksiKeseluruhan, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
                
            Stat::make('Total Donasi Tahun Ini', 'Rp ' . number_format($totalDonasiTahunIni, 0, ',', '.'))
                ->description('Total transaksi: ' . number_format($totalTransaksiTahunIni, 0, ',', '.') . ' (' . $currentYear . ')')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
                
            Stat::make('Total Donasi Bulan Ini', 'Rp ' . number_format($totalDonasiBulanIni, 0, ',', '.'))
                ->description('Total transaksi: ' . number_format($totalTransaksiBulanIni, 0, ',', '.') . ' (' . $currentDate->translatedFormat('F Y') . ')')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
                
            Stat::make('Total Donasi Hari Ini', 'Rp ' . number_format($totalDonasiHariIni, 0, ',', '.'))
                ->description('Total transaksi: ' . number_format($totalTransaksiHariIni, 0, ',', '.') . ' (' . $currentDate->translatedFormat('d F Y') . ')')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}


