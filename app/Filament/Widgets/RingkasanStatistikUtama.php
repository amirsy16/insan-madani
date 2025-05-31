<?php

namespace App\Filament\Widgets;

use App\Models\Donasi;
use App\Models\Donatur; // Asumsi ada model Donatur
use App\Models\ProgramPenyaluran;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Number;


class RingkasanStatistikUtama extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1; // Urutan di halaman AnalisisData

    // Definisikan view untuk header (tempat filter kustom kita)
    // protected function getHeaderView(): ?string
    // {
    //     return 'filament.widgets.ringkasan-statistik-filter'; // Path ke file Blade baru
    // }

    // Tambahkan properti untuk filter periode
    public string $timePeriod = 'all_time';
    public array $timePeriodOptions = [
        'current_month' => 'Bulan Ini',
        'current_year' => 'Tahun Ini',
        'all_time' => 'Keseluruhan',
    ];

    // Untuk menyimpan filter yang dipilih dari UI
    public function setTimePeriod(string $period): void
    {
        $this->timePeriod = $period;
        $this->refresh();
    }

    // Helper untuk label periode
    public function getTimePeriodLabel(): string
    {
        return $this->timePeriodOptions[$this->timePeriod] ?? 'Keseluruhan';
    }

    // Untuk Blade filter
    public function getFilters(): array
    {
        return $this->timePeriodOptions;
    }

    // Override mount untuk inisialisasi
    public function mount($periode = null): void
    {
        $this->timePeriod = $periode ?: request('periode', 'all_time');
    }

    protected function getStats(): array
    {
        // Terapkan filter periode seperti TopDonaturRingkasWidget
        $kolomNominalDonasi = 'jumlah';
        $kolomTanggalDonasi = 'tanggal_donasi';
        $kolomDanaTersalurkan = 'jumlah_dana';
        $kolomTanggalPenyaluran = 'tanggal_penyaluran';

        // Query Donasi
        $queryDonasi = Donasi::query();
        switch ($this->timePeriod) {
            case 'current_month':
                $queryDonasi->whereMonth($kolomTanggalDonasi, Carbon::now()->month)
                            ->whereYear($kolomTanggalDonasi, Carbon::now()->year);
                break;
            case 'current_year':
                $queryDonasi->whereYear($kolomTanggalDonasi, Carbon::now()->year);
                break;
            case 'all_time':
            default:
                // Tidak ada filter
                break;
        }
        $totalDonasi = (clone $queryDonasi)->sum($kolomNominalDonasi);
        $jumlahTransaksi = (clone $queryDonasi)->count();
        $rataRataDonasi = $jumlahTransaksi > 0 ? $totalDonasi / $jumlahTransaksi : 0;

        // Query Penyaluran
        $queryPenyaluran = ProgramPenyaluran::query();
        switch ($this->timePeriod) {
            case 'current_month':
                $queryPenyaluran->whereMonth($kolomTanggalPenyaluran, Carbon::now()->month)
                                ->whereYear($kolomTanggalPenyaluran, Carbon::now()->year);
                break;
            case 'current_year':
                $queryPenyaluran->whereYear($kolomTanggalPenyaluran, Carbon::now()->year);
                break;
            case 'all_time':
            default:
                // Tidak ada filter
                break;
        }
        $totalPenyaluran = $queryPenyaluran->sum($kolomDanaTersalurkan);

        // Jumlah Donatur Aktif
        $jumlahDonaturAktif = 0;
        switch ($this->timePeriod) {
            case 'current_month':
                $jumlahDonaturAktif = Donasi::whereMonth($kolomTanggalDonasi, Carbon::now()->month)
                    ->whereYear($kolomTanggalDonasi, Carbon::now()->year)
                    ->distinct('donatur_id')->count('donatur_id');
                break;
            case 'current_year':
                $jumlahDonaturAktif = Donasi::whereYear($kolomTanggalDonasi, Carbon::now()->year)
                    ->distinct('donatur_id')->count('donatur_id');
                break;
            case 'all_time':
            default:
                $jumlahDonaturAktif = Donatur::count();
                break;
        }

        $rasioPenyaluran = $totalDonasi > 0 ? ($totalPenyaluran / $totalDonasi) * 100 : 0;

        return [
            Stat::make('💰 Total Penerimaan Donasi', Number::currency($totalDonasi, 'IDR'))
                ->description('Total dana donasi yang diterima')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('💸 Total Dana Tersalurkan', Number::currency($totalPenyaluran, 'IDR'))
                ->description('Total dana yang telah disalurkan')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),
            Stat::make('📈 Rasio Penyaluran', number_format($rasioPenyaluran, 2) . '%')
                ->description('Persentase dana tersalurkan dari penerimaan')
                ->color($rasioPenyaluran > 70 ? 'success' : ($rasioPenyaluran > 40 ? 'warning' : 'danger')),
            Stat::make('✨ Jumlah Transaksi', number_format($jumlahTransaksi))
                ->description('Total transaksi donasi yang masuk')
                ->color('info'),
            Stat::make('📊 Rata-rata per Donasi', Number::currency($rataRataDonasi, 'IDR'))
                ->description('Rata-rata nominal per transaksi donasi')
                ->color('info'),
            Stat::make('👥 Donatur Aktif', number_format($jumlahDonaturAktif))
                ->description('Jumlah donatur yang berdonasi pada periode ini')
                ->color('primary'),
        ];
    }
public static function getPages(): array
{
    return [
        \App\Filament\Pages\AnalisisData::class,
    ];
}

    // Hanya tampil di halaman Analisis Data
}
