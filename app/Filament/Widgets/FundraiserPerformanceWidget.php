<?php

namespace App\Filament\Widgets;

use App\Models\Donasi;
use App\Models\Fundraiser;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
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
    public string $timePeriod = 'all_time';  // Changed default to all_time
    public int $limit = 10;

    protected static bool $isDiscovered = true;

    public function getHeading(): string
    {
        $periodLabel = match($this->timePeriod) {
            'current_month' => 'Bulan Ini',
            'last_month' => 'Bulan Lalu', 
            'last_3_months' => '3 Bulan Terakhir',
            'last_6_months' => '6 Bulan Terakhir',
            'current_year' => 'Tahun Ini',
            'all_time' => 'Semua Waktu',
            default => 'Semua Waktu'
        };
        
        $limitLabel = $this->limit > 0 ? "Top {$this->limit}" : 'Semua';
        
        return "Performa Fundraiser - {$limitLabel} ({$periodLabel})";
    }

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
                    ->sortable()
                    ->weight('medium'),
                
                TextColumn::make('nomor_hp')
                    ->label('No. HP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('aktif')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ? 'Aktif' : 'Tidak Aktif')
                    ->toggleable(),
                
                TextColumn::make('total_dana_terkumpul')
                    ->label('Total Dana')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                
                TextColumn::make('total_transaksi')
                    ->label('Transaksi')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                
                TextColumn::make('total_donatur_unique')
                    ->label('Donatur')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                
                TextColumn::make('rata_rata_donasi')
                    ->label('Rata-rata')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('efisiensi')
                    ->label('Dana/Donatur')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('time_period')
                    ->label('Periode Waktu')
                    ->options([
                        'all_time' => 'Semua Waktu',
                        'current_month' => 'Bulan Ini',
                        'last_month' => 'Bulan Lalu',
                        'last_3_months' => '3 Bulan Terakhir',
                        'last_6_months' => '6 Bulan Terakhir',
                        'current_year' => 'Tahun Ini',
                    ])
                    ->default('all_time')
                    ->query(function ($query, array $data) {
                        $this->timePeriod = $data['value'] ?? 'all_time';
                        return $query;
                    }),
                    
                SelectFilter::make('status')
                    ->label('Status Fundraiser')
                    ->options([
                        'all' => 'Semua',
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                    ])
                    ->default('all')
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'active') {
                            return $query->where('fundraisers.aktif', true);
                        } elseif ($data['value'] === 'inactive') {
                            return $query->where('fundraisers.aktif', false);
                        }
                        return $query;
                    }),
                    
                SelectFilter::make('limit')
                    ->label('Jumlah Data')
                    ->options([
                        5 => 'Top 5',
                        10 => 'Top 10',
                        15 => 'Top 15',
                        20 => 'Top 20',
                        50 => 'Top 50',
                        0 => 'Semua',
                    ])
                    ->default(10)
                    ->query(function ($query, array $data) {
                        $this->limit = $data['value'] ?? 10;
                        return $query;
                    }),
            ])
            ->defaultSort('total_dana_terkumpul', 'desc')
            ->striped()
            ->paginated(false);
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Fundraiser::query()
            ->select([
                'fundraisers.id',
                'fundraisers.nama_fundraiser',
                'fundraisers.nomor_hp',
                'fundraisers.aktif',
                DB::raw('COUNT(DISTINCT donasis.id) as total_transaksi'),
                DB::raw('COUNT(DISTINCT donasis.donatur_id) as total_donatur_unique'),
                DB::raw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) as total_dana_terkumpul'),
                DB::raw('AVG(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) as rata_rata_donasi'),
                DB::raw('CASE WHEN COUNT(DISTINCT donasis.donatur_id) > 0 THEN SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) / COUNT(DISTINCT donasis.donatur_id) ELSE 0 END as efisiensi')
            ])
            ->leftJoin('donasis', function($join) {
                $join->on('fundraisers.id', '=', 'donasis.fundraiser_id')
                     ->where('donasis.status_konfirmasi', '=', 'verified');
                     
                // Apply time filter in join clause
                $this->applyTimePeriodFilterToJoin($join);
            });

        $baseQuery = $query
            ->groupBy('fundraisers.id', 'fundraisers.nama_fundraiser', 'fundraisers.nomor_hp', 'fundraisers.aktif')
            ->having('total_transaksi', '>', 0)  // Hanya tampilkan yang punya transaksi 
            ->orderByDesc('total_dana_terkumpul');
            
        // Apply limit only if it's not 0 (which means show all)
        if ($this->limit > 0) {
            $baseQuery->limit($this->limit);
        }
        
        return $baseQuery;
    }

    protected function applyTimePeriodFilterToJoin($join): void
    {
        switch ($this->timePeriod) {
            case 'current_month':
                $join->whereMonth('donasis.tanggal_donasi', Carbon::now()->month)
                     ->whereYear('donasis.tanggal_donasi', Carbon::now()->year);
                break;
            case 'last_month':
                $join->whereMonth('donasis.tanggal_donasi', Carbon::now()->subMonth()->month)
                     ->whereYear('donasis.tanggal_donasi', Carbon::now()->subMonth()->year);
                break;
            case 'current_year':
                $join->whereYear('donasis.tanggal_donasi', Carbon::now()->year);
                break;
            case 'last_3_months':
                $join->where('donasis.tanggal_donasi', '>=', Carbon::now()->subMonths(3));
                break;
            case 'last_6_months':
                $join->where('donasis.tanggal_donasi', '>=', Carbon::now()->subMonths(6));
                break;
            case 'all_time':
            default:
                // No additional filter - will show all fundraisers with any donations
                break;
        }
    }
}
