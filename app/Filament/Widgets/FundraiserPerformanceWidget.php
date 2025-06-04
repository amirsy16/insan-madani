<?php

namespace App\Filament\Widgets;

use App\Models\Donasi;
use App\Models\Fundraiser;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;

class FundraiserPerformanceWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Performa Fundraiser';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    // Filter properties
    public string $timePeriod = 'current_month';
    public int $limit = 10;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex()
                    ->badge()
                    ->color(fn ($rowLoop) => match($rowLoop->iteration) {
                        1 => 'warning', // Gold
                        2 => 'gray',    // Silver  
                        3 => 'danger',  // Bronze
                        default => 'primary'
                    }),
                
                TextColumn::make('nama_fundraiser')
                    ->label('Nama Fundraiser')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('nomor_hp')
                    ->label('No. HP')
                    ->searchable(),
                
                TextColumn::make('total_dana_terkumpul')
                    ->label('Total Dana')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                
                TextColumn::make('total_transaksi')
                    ->label('Transaksi')
                    ->sortable(),
                
                TextColumn::make('total_donatur_unique')
                    ->label('Donatur')
                    ->sortable(),
                
                TextColumn::make('rata_rata_donasi')
                    ->label('Rata-rata')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                
                TextColumn::make('efisiensi')
                    ->label('Dana/Donatur')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
            ])
            ->defaultSort('total_dana_terkumpul', 'desc')
            ->paginated(false);
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Fundraiser::query()
            ->select([
                'fundraisers.id',
                'fundraisers.nama_fundraiser',
                'fundraisers.nomor_hp',
                DB::raw('COUNT(DISTINCT donasis.id) as total_transaksi'),
                DB::raw('COUNT(DISTINCT donasis.donatur_id) as total_donatur_unique'),
                DB::raw('SUM(donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)) as total_dana_terkumpul'),
                DB::raw('AVG(donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)) as rata_rata_donasi'),
                DB::raw('CASE WHEN COUNT(DISTINCT donasis.donatur_id) > 0 THEN SUM(donasis.jumlah + IFNULL(donasis.perkiraan_nilai_barang, 0)) / COUNT(DISTINCT donasis.donatur_id) ELSE 0 END as efisiensi')
            ])
            ->leftJoin('donasis', 'fundraisers.id', '=', 'donasis.fundraiser_id')
            ->where('donasis.status_konfirmasi', 'verified');

        // Apply time period filter
        $this->applyTimePeriodFilter($query);

        return $query
            ->groupBy('fundraisers.id', 'fundraisers.nama_fundraiser', 'fundraisers.nomor_hp')
            ->orderByDesc('total_dana_terkumpul')
            ->limit($this->limit);
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
            case 'last_3_months':
                $query->where('donasis.tanggal_donasi', '>=', Carbon::now()->subMonths(3));
                break;
            case 'all_time':
            default:
                // No filter
                break;
        }
    }
}
