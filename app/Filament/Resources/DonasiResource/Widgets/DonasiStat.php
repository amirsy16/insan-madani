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
            
        // Current month and year
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
            
        // Calculate metrics
        // 1. Total Donasi Keseluruhan
        $totalDonasiKeseluruhan = (clone $query)
            ->where('status_konfirmasi', 'verified')
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            
        // 2. Total Donasi Bulan Ini
        $totalDonasiBulanIni = (clone $query)
            ->where('status_konfirmasi', 'verified')
            ->whereMonth('tanggal_donasi', $currentMonth)
            ->whereYear('tanggal_donasi', $currentYear)
            ->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
            
        // 3. Total Transaksi Keseluruhan
        $totalTransaksiKeseluruhan = (clone $query)->count();
        
        // 4. Total Transaksi Bulan Ini
        $totalTransaksiBulanIni = (clone $query)
            ->whereMonth('tanggal_donasi', $currentMonth)
            ->whereYear('tanggal_donasi', $currentYear)
            ->count();
        
        return [
            Stat::make('Total Donasi Keseluruhan', 'Rp ' . number_format($totalDonasiKeseluruhan, 0, ',', '.'))
                ->description('Donasi terverifikasi')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
                
            Stat::make('Total Donasi Bulan Ini', 'Rp ' . number_format($totalDonasiBulanIni, 0, ',', '.'))
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
                
            Stat::make('Total Transaksi Keseluruhan', number_format($totalTransaksiKeseluruhan, 0, ',', '.'))
                ->description('Jumlah transaksi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('danger'),
                
            Stat::make('Total Transaksi Bulan Ini', number_format($totalTransaksiBulanIni, 0, ',', '.'))
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
        ];
    }
}


