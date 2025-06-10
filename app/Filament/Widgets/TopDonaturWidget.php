<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Donatur;
use App\Models\Donasi;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;

class TopDonaturWidget extends BaseWidget
{
    protected static ?string $heading = null;
    
    // protected int | string | array $columnSpan = 6;
    
    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return 'Top 10 Donatur - Analisis Lengkap';
    }

    public function getDescription(): ?string
    {
        return 'Analisis mendalam performa donatur dengan berbagai opsi pengurutan dan filter periode';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('ranking')
                    ->label('Rank')
                    ->getStateUsing(function ($rowLoop) {
                        return $rowLoop->iteration;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        1 => 'warning', // Gold
                        2 => 'gray',    // Silver
                        3 => 'success', // Bronze
                        default => 'primary',
                    }),
                    
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Donatur')
                    ->searchable()
                    ->weight('medium')
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('total_kontribusi')
                    ->label('Total Donasi')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('total_donasi_count')
                    ->label('Frekuensi')
                    ->suffix(' kali')
                    ->alignCenter()
                    ->badge()
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state > 10 => 'success',   // Sering - hijau
                        $state >= 5 => 'warning',   // Sedang - kuning  
                        $state >= 2 => 'info',      // Jarang - biru
                        $state == 1 => 'gray',      // Sekali - abu-abu
                        default => 'primary',
                    })
                    ->tooltip(fn ($state) => match (true) {
                        $state > 10 => 'Donatur Setia (Sering)',
                        $state >= 5 => 'Donatur Aktif (Sedang)',
                        $state >= 2 => 'Donatur Biasa (Jarang)',
                        $state == 1 => 'Donatur Baru (Sekali)',
                        default => 'Donatur',
                    }),
                    
                Tables\Columns\TextColumn::make('rata_rata_donasi')
                    ->label('Rata-rata')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('donasi_terakhir')
                    ->label('Donasi Terakhir')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('periode')
                    ->label('Periode')
                    ->options([
                        'keseluruhan' => 'Keseluruhan',
                        'tahun_ini' => 'Tahun Ini',
                        'bulan_ini' => 'Bulan Ini',
                    ])
                    ->default('keseluruhan')
                    ->query(function (Builder $query, array $data): Builder {
                        return $this->applyPeriodeFilter($query, $data['value'] ?? 'keseluruhan');
                    }),
                    
            ])
            ->paginated(false);
    }

    protected function getTableQuery(): Builder
    {
        return Donatur::query()
            ->join('donasis', 'donaturs.id', '=', 'donasis.donatur_id')
            ->where('donasis.status_konfirmasi', 'verified')
            ->select([
                'donaturs.id',
                'donaturs.nama',
                DB::raw('COUNT(donasis.id) as total_donasi_count'),
                DB::raw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) as total_kontribusi'),
                DB::raw('AVG(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) as rata_rata_donasi'),
                DB::raw('MAX(donasis.tanggal_donasi) as donasi_terakhir')
            ])
            ->groupBy('donaturs.id', 'donaturs.nama')
            ->limit(10);
    }

    protected function applyPeriodeFilter(Builder $query, string $periode): Builder
    {
        switch ($periode) {
            case 'bulan_ini':
                return $query->whereMonth('donasis.tanggal_donasi', now()->month)
                            ->whereYear('donasis.tanggal_donasi', now()->year);
                            
            case 'tahun_ini':
                return $query->whereYear('donasis.tanggal_donasi', now()->year);
                
            case 'keseluruhan':
            default:
                return $query; // Tidak ada filter tambahan
        }
    }

    protected function applyUrutanDonasiFilter(Builder $query, string $urutan): Builder
    {
        switch ($urutan) {
            case 'terkecil':
                return $query->orderBy('total_kontribusi', 'asc');
                
            case 'terbanyak':
            default:
                return $query->orderByDesc('total_kontribusi');
        }
    }

    protected function applyUrutanFrekuensiFilter(Builder $query, string $urutan): Builder
    {
        switch ($urutan) {
            case 'terkecil':
                return $query->orderBy('total_donasi_count', 'asc');
                
            case 'terbanyak':
            default:
                return $query->orderByDesc('total_donasi_count');
        }
    }
}
