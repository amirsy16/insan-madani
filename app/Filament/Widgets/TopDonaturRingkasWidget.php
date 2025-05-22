<?php

namespace App\Filament\Widgets;

use App\Models\Donatur;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use App\Filament\Resources\DonaturResource;
use Carbon\Carbon;

class TopDonaturRingkasWidget extends Widget
{

    use HasWidgetShield;
    protected static string $view = 'filament.widgets.top-donatur-ringkas-widget'; // Path ke view Blade

    protected static ?int $sort = 3; // Sesuaikan urutan dengan widget lain di dashboard

    // Berapa banyak top donatur yang ingin ditampilkan
    public int $limit = 10; // Kita tampilkan top 10

    // Properti untuk menyimpan data top donatur
    public ?Collection $topDonaturs; // Ubah tipe menjadi ?Collection
    
    // Properti untuk filter periode waktu
    public string $timePeriod = 'all_time';
    
    // Opsi periode waktu
    public array $timePeriodOptions = [
        'current_month' => 'Bulan Ini',
        'current_year' => 'Tahun Ini',
        'all_time' => 'Keseluruhan'
    ];
    
    // Total donasi untuk persentase
    public float $totalDonasi = 0;

    // Untuk mengatur lebar kolom widget
    // Jika dashboard Anda 3 kolom (lg), widget ini bisa mengambil 1 kolom.
    // Jika dashboard Anda 2 kolom (md), widget ini bisa mengambil 1 kolom atau 'full'.
    protected int | string | array $columnSpan = [
        'md' => 1,
        'lg' => 1,
    ];

    public function mount(): void
    {
        $this->refreshData();
    }
    
    public function setTimePeriod(string $period): void
    {
        $this->timePeriod = $period;
        $this->refreshData();
    }
    
    public function refreshData(): void
    {
        // Base query
        $query = Donatur::query()
            ->select([
                'donaturs.id',
                'donaturs.nama',
                DB::raw('SUM(donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)) as total_donasi_sum'),
                DB::raw('COUNT(donasis.id) as total_transaksi')
            ])
            ->join('donasis', 'donaturs.id', '=', 'donasis.donatur_id')
            ->where('donasis.status_konfirmasi', 'verified');
        
        // Apply time period filter
        $this->applyTimePeriodFilter($query);
        
        // Get top donaturs
        $this->topDonaturs = $query
            ->groupBy('donaturs.id', 'donaturs.nama')
            ->orderByDesc('total_donasi_sum')
            ->limit($this->limit)
            ->get();
        
        // Calculate total donations for percentage
        $this->calculateTotalDonations();
    }
    
    protected function applyTimePeriodFilter($query): void
    {
        switch ($this->timePeriod) {
            case 'current_month':
                $query->whereMonth('donasis.tanggal_donasi', Carbon::now()->month)
                      ->whereYear('donasis.tanggal_donasi', Carbon::now()->year);
                break;
            case 'current_year':
                $query->whereYear('donasis.tanggal_donasi', Carbon::now()->year);
                break;
            case 'all_time':
            default:
                // No filter needed for all time
                break;
        }
    }
    
    protected function calculateTotalDonations(): void
    {
        // Base query for total donations
        $query = DB::table('donasis')
            ->where('status_konfirmasi', 'verified');
        
        // Apply time period filter
        switch ($this->timePeriod) {
            case 'current_month':
                $query->whereMonth('tanggal_donasi', Carbon::now()->month)
                      ->whereYear('tanggal_donasi', Carbon::now()->year);
                break;
            case 'current_year':
                $query->whereYear('tanggal_donasi', Carbon::now()->year);
                break;
            case 'all_time':
            default:
                // No filter needed for all time
                break;
        }
        
        // Calculate total
        $this->totalDonasi = $query->sum(DB::raw('jumlah + IFNULL(perkiraan_nilai_barang, 0)'));
    }
    
    public function getPercentageText(float $donaturTotal): string
    {
        if ($this->totalDonasi <= 0) {
            return '0%';
        }
        
        $percentage = ($donaturTotal / $this->totalDonasi) * 100;
        return number_format($percentage, 1) . '%';
    }
    
    public function getTimePeriodLabel(): string
    {
        return $this->timePeriodOptions[$this->timePeriod] ?? 'Keseluruhan';
    }

    public function getDonaturUrl(int $donaturId): string
    {
        return DonaturResource::getUrl('view', ['record' => $donaturId]);
    }
}




