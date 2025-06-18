<?php

namespace App\Filament\Widgets;

use App\Models\Donasi;
use App\Models\JenisPenggunaanHakAmil;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class HakAmilOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Hitung total donasi bulan ini
        $totalDonasiBulanIni = Donasi::whereMonth('tanggal_donasi', now()->month)
            ->whereYear('tanggal_donasi', now()->year)
            ->where('status_konfirmasi', 'dikonfirmasi')
            ->sum(DB::raw('COALESCE(jumlah, 0) + COALESCE(perkiraan_nilai_barang, 0)'));

        // Hitung total hak amil bulan ini berdasarkan pengaturan
        $totalHakAmilBulanIni = 0;
        $donasiList = Donasi::whereMonth('tanggal_donasi', now()->month)
            ->whereYear('tanggal_donasi', now()->year)
            ->where('status_konfirmasi', 'dikonfirmasi')
            ->get();

        foreach ($donasiList as $donasi) {
            $totalHakAmilBulanIni += $donasi->hak_amil;
        }

        // Hitung pengaturan hak amil aktif
        $pengaturanAktif = JenisPenggunaanHakAmil::aktif()->count();

        // Persentase rata-rata hak amil
        $avgPersentase = JenisPenggunaanHakAmil::aktif()
            ->avg('persentase_hak_amil') ?? 0;

        return [
            Stat::make('Total Donasi Bulan Ini', 'Rp ' . number_format($totalDonasiBulanIni, 0, ',', '.'))
                ->description('Donasi yang telah dikonfirmasi')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('Hak Amil Bulan Ini', 'Rp ' . number_format($totalHakAmilBulanIni, 0, ',', '.'))
                ->description('Berdasarkan pengaturan aktif')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),

            Stat::make('Pengaturan Aktif', $pengaturanAktif)
                ->description('Konfigurasi hak amil')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info'),

            Stat::make('Persentase Rata-rata', number_format($avgPersentase, 2) . '%')
                ->description('Rata-rata persentase hak amil')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),
        ];
    }
}
