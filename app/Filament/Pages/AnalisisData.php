<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FundraiserPerformanceWidget;
use Filament\Pages\Page;
use App\Filament\Widgets\RingkasanStatistikUtama;
use App\Filament\Widgets\TrenPenerimaanDonasiChart;
use App\Filament\Widgets\MetodePembayaranChart;
use App\Filament\Widgets\TopDonaturWidget;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class AnalisisData extends Page
{
    use HasPageShield;
    

    // Ikon navigasi (pilih dari Heroicons)
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    // Label navigasi di sidebar
    protected static ?string $navigationLabel = 'Statistik & Analisis';

    // Judul halaman
    protected static ?string $title = 'Pusat Statistik dan Analisis Data';

    // Grup navigasi (opsional)
    protected static ?string $navigationGroup = 'Laporan & Keuangan';

    // Urutan menu
    protected static ?int $navigationSort = 1;

    // File view Blade yang akan digunakan
    protected static string $view = 'filament.pages.analisis-data';

    // protected function getFooterWidgets(): array // Atau getFooterWidgets, atau getWidgets jika versi Filament lebih baru
    // {
    //     return [
    //         RingkasanStatistikUtama::class, // <--- Daftarkan widget
    //     ];
    // }

    public function getStatistikUtama(): array
    {
        $kolomNominalDonasi = 'jumlah';
        $kolomTanggalDonasi = 'tanggal_donasi';
        $periode = request('periode', 'all_time');
        $queryDonasi = \App\Models\Donasi::query();
        switch ($periode) {
            case 'current_month':
                $queryDonasi->whereMonth($kolomTanggalDonasi, now()->month)
                            ->whereYear($kolomTanggalDonasi, now()->year);
                break;
            case 'current_year':
                $queryDonasi->whereYear($kolomTanggalDonasi, now()->year);
                break;
            case 'all_time':
            default:
                // Tidak ada filter
                break;
        }
        $totalDonasi = (clone $queryDonasi)->sum($kolomNominalDonasi);
        $jumlahTransaksi = (clone $queryDonasi)->count();
        $rataRataDonasi = $jumlahTransaksi > 0 ? $totalDonasi / $jumlahTransaksi : 0;
        $jumlahDonatur = \App\Models\Donatur::count();
        return [
            'totalDonasi' => $totalDonasi,
            'jumlahDonatur' => $jumlahDonatur,
            'jumlahTransaksi' => $jumlahTransaksi,
            'rataRataDonasi' => $rataRataDonasi,
        ];
    }
    public function getViewData(): array
    {
        return [
            'statistikUtama' => $this->getStatistikUtama(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RingkasanStatistikUtama::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // TrenPenerimaanDonasiChart::class,
             TopDonaturWidget::class,
            MetodePembayaranChart::class,
            FundraiserPerformanceWidget::class,
        ];
    }
}
