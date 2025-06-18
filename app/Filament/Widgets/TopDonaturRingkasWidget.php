<?php

namespace App\Filament\Widgets;

use App\Models\Donatur;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use App\Filament\Resources\DonaturResource;
use Carbon\Carbon;

class TopDonaturRingkasWidget extends Widget
{
    use HasWidgetShield;
    
    protected static string $view = 'filament.widgets.top-donatur-ringkas-widget';
    protected static ?int $sort = 3;
    protected static bool $isDiscovered = false;

    // Tampilkan hanya top 5 untuk dashboard
    public int $limit = 5;
    public $topDonaturs;

    // Untuk mengatur lebar kolom widget - lebih compact
    protected int | string | array $columnSpan = [
        'md' => 1,
        'lg' => 1,
    ];

    public function mount(): void
    {
        $this->getTopDonaturs();
    }
    
    public function getTopDonaturs()
    {
        // Ambil top donatur untuk bulan ini saja
        $this->topDonaturs = Donatur::query()
            ->select([
                'donaturs.id',
                'donaturs.nama',
                DB::raw('SUM(donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)) as total_donasi'),
                DB::raw('COUNT(donasis.id) as jumlah_transaksi')
            ])
            ->join('donasis', 'donaturs.id', '=', 'donasis.donatur_id')
            ->where('donasis.status_konfirmasi', 'verified')
            ->whereMonth('donasis.tanggal_donasi', Carbon::now()->month)
            ->whereYear('donasis.tanggal_donasi', Carbon::now()->year)
            ->groupBy('donaturs.id', 'donaturs.nama')
            ->orderByDesc('total_donasi')
            ->limit($this->limit)
            ->get();
    }

    public function getDonaturUrl(int $donaturId): string
    {
        return DonaturResource::getUrl('view', ['record' => $donaturId]);
    }
}




