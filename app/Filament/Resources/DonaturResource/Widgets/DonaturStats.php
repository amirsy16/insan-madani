<?php

namespace App\Filament\Resources\DonaturResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Donatur;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DonaturStats extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    // Define the component name explicitly to fix the Livewire error
    public static function getComponentName(): string
    {
        return 'donatur-resource.widgets.donatur-stats';
    }

    protected function getStats(): array
    {
        // Current month and year
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        // 1. Total Donatur Keseluruhan - Count all donors, not just those with donations
        $totalDonaturKeseluruhan = Donatur::count();
        
        // 2. Total Donatur Aktif - Donors who have made verified donations
        $totalDonaturAktif = Donatur::whereHas('donasis', function ($q) {
            $q->where('status_konfirmasi', 'verified');
        })->count();
        
        // 3. Total Donatur Bulan Ini
        $totalDonaturBulanIni = Donatur::whereHas('donasis', function ($q) use ($currentMonth, $currentYear) {
            $q->where('status_konfirmasi', 'verified')
              ->whereMonth('tanggal_donasi', $currentMonth)
              ->whereYear('tanggal_donasi', $currentYear);
        })->count();
        
        // 4. Top Donatur Bulan Ini
        $topDonatur = Donatur::select([
                'donaturs.id',
                'donaturs.nama',
                DB::raw('SUM(donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)) as total_donasi')
            ])
            ->join('donasis', 'donaturs.id', '=', 'donasis.donatur_id')
            ->where('donasis.status_konfirmasi', 'verified')
            ->whereMonth('donasis.tanggal_donasi', $currentMonth)
            ->whereYear('donasis.tanggal_donasi', $currentYear)
            ->groupBy('donaturs.id', 'donaturs.nama')
            ->orderByDesc('total_donasi')
            ->first();
            
        $topDonaturNama = $topDonatur ? $topDonatur->nama : 'N/A';
        $topDonaturJumlah = $topDonatur ? $topDonatur->total_donasi : 0;
        
        return [
            Stat::make('Total Donatur', number_format($totalDonaturKeseluruhan, 0, ',', '.'))
                ->description('Semua donatur terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Donatur Aktif', number_format($totalDonaturAktif, 0, ',', '.'))
                ->description('Donatur yang pernah berdonasi')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
                
            Stat::make('Donatur Bulan Ini', number_format($totalDonaturBulanIni, 0, ',', '.'))
                ->description(Carbon::now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
                
            Stat::make('Top Donatur Bulan Ini', $topDonaturNama)
                ->description($topDonatur ? 'Rp ' . number_format($topDonaturJumlah, 0, ',', '.') : '')
                ->descriptionIcon('heroicon-m-trophy')
                ->color('warning'),
        ];
    }
}


