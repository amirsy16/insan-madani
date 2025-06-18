<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Donatur;
use App\Models\Donasi;
use App\Models\JenisDonasi;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TopDonaturWidget extends BaseWidget
{
    protected static ?string $heading = null;
    
    // protected int | string | array $columnSpan = 6;
    
    protected static ?int $sort = 3;

    protected static bool $isDiscovered = false;

    // Property untuk menyimpan jumlah top donatur yang ditampilkan
    public $topCount = 10;
    public $sortBy = 'total_kontribusi';
    public $sortDirection = 'desc';
    
    // Property untuk statistik tambahan
    public $totalDonaturAktif = 0;
    public $totalKontribusiKeseluruhan = 0;
       protected int | string | array $columnSpan = 'full';

    public function mount(): void
    {
        $this->calculateStatistics();
    }

    public function getHeading(): string
    {
        return "Top {$this->topCount} Donatur - Analisis Lengkap";
    }

    public function getDescription(): ?string
    {
        return 'Analisis mendalam performa donatur dengan berbagai opsi pengurutan dan filter periode yang lengkap. ' .
               "Total {$this->totalDonaturAktif} donatur aktif dengan kontribusi Rp " . 
               number_format($this->totalKontribusiKeseluruhan, 0, ',', '.');
    }

    protected function calculateStatistics(): void
    {
        $stats = Donatur::query()
            ->join('donasis', 'donaturs.id', '=', 'donasis.donatur_id')
            ->where('donasis.status_konfirmasi', 'verified')
            ->select([
                DB::raw('COUNT(DISTINCT donaturs.id) as total_donatur'),
                DB::raw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) as total_kontribusi')
            ])
            ->first();
            
        $this->totalDonaturAktif = $stats->total_donatur ?? 0;
        $this->totalKontribusiKeseluruhan = $stats->total_kontribusi ?? 0;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('ranking')
                    ->label('Rank')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => match ((int)$state) {
                        1 => 'warning', // Gold
                        2 => 'gray',    // Silver
                        3 => 'success', // Bronze
                        default => 'primary',
                    }),
                    
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Donatur')
                    ->searchable()
                    ->weight('medium')
                    ->wrap()
                    ->description(fn ($record) => $record->nomor_hp ? "HP: " . $record->nomor_hp : null)
                    ->tooltip(fn ($record) => $record->email ? "Email: " . $record->email : null),
                    
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('donasi_terakhir')
                    ->label('Donasi Terakhir')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('donasi_pertama')
                    ->label('Donasi Pertama')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('kategori_donatur')
                    ->label('Kategori')
                    ->badge()
                    ->toggleable()
                    ->color(fn ($state) => match ($state) {
                        'Platinum' => 'warning',
                        'Gold' => 'success', 
                        'Silver' => 'info',
                        'Bronze' => 'gray',
                        default => 'primary',
                    })
                    ->getStateUsing(function ($record) {
                        $total = $record->total_kontribusi;
                        if ($total >= 50000000) return 'Platinum';
                        if ($total >= 10000000) return 'Gold'; 
                        if ($total >= 5000000) return 'Silver';
                        if ($total >= 1000000) return 'Bronze';
                        return 'Regular';
                    }),
                    
                Tables\Columns\TextColumn::make('provinsi')
                    ->label('Provinsi')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('jenis_donasi_terfavorit')
                    ->label('Jenis Terfavorit')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('durasi_aktif')
                    ->label('Durasi Aktif')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function ($record) {
                        if (!$record->donasi_pertama || !$record->donasi_terakhir) {
                            return '-';
                        }
                        
                        $pertama = \Carbon\Carbon::parse($record->donasi_pertama);
                        $terakhir = \Carbon\Carbon::parse($record->donasi_terakhir);
                        $durasi = $pertama->diffInDays($terakhir);
                        
                        if ($durasi < 30) {
                            return $durasi . ' hari';
                        } elseif ($durasi < 365) {
                            return intval($durasi / 30) . ' bulan';
                        } else {
                            return intval($durasi / 365) . ' tahun';
                        }
                    }),
                    
                Tables\Columns\TextColumn::make('konsistensi')
                    ->label('Konsistensi')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function ($record) {
                        if (!$record->donasi_pertama || !$record->donasi_terakhir || $record->total_donasi_count <= 1) {
                            return 'N/A';
                        }
                        
                        $pertama = \Carbon\Carbon::parse($record->donasi_pertama);
                        $terakhir = \Carbon\Carbon::parse($record->donasi_terakhir);
                        $durasi = $pertama->diffInDays($terakhir);
                        $rataRataHari = $durasi / max($record->total_donasi_count - 1, 1);
                        
                        if ($rataRataHari <= 30) return 'Sangat Tinggi';
                        if ($rataRataHari <= 60) return 'Tinggi';
                        if ($rataRataHari <= 120) return 'Sedang';
                        if ($rataRataHari <= 365) return 'Rendah';
                        return 'Sangat Rendah';
                    })
                    ->color(fn ($state) => match ($state) {
                        'Sangat Tinggi' => 'success',
                        'Tinggi' => 'info',
                        'Sedang' => 'warning',
                        'Rendah' => 'danger',
                        'Sangat Rendah' => 'gray',
                        default => 'primary',
                    }),
                    
                Tables\Columns\TextColumn::make('potensi_upgrade')
                    ->label('Potensi Upgrade')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->getStateUsing(function ($record) {
                        $total = $record->total_kontribusi;
                        $frekuensi = $record->total_donasi_count;
                        
                        // Analisis potensi berdasarkan total dan frekuensi
                        if ($total >= 5000000 && $frekuensi >= 5) {
                            if ($total < 10000000) return 'Menuju Gold';
                            if ($total < 50000000) return 'Menuju Platinum';
                            return 'Tertinggi';
                        }
                        
                        if ($total >= 1000000 && $frekuensi >= 3) {
                            return 'Menuju Silver';
                        }
                        
                        if ($frekuensi >= 2) {
                            return 'Menuju Bronze';
                        }
                        
                        return 'Baru';
                    })
                    ->color(fn ($state) => match ($state) {
                        'Menuju Platinum' => 'warning',
                        'Menuju Gold' => 'success',
                        'Menuju Silver' => 'info',
                        'Menuju Bronze' => 'gray',
                        'Tertinggi' => 'danger',
                        default => 'primary',
                    }),
            ])
            ->filters([
                // Filter jumlah top donatur
                SelectFilter::make('top_count')
                    ->label('Jumlah Top Donatur')
                    ->options([
                        5 => 'Top 5',
                        10 => 'Top 10', 
                        15 => 'Top 15',
                        20 => 'Top 20',
                        25 => 'Top 25',
                        50 => 'Top 50',
                        100 => 'Top 100',
                    ])
                    ->default(10)
                    ->query(function (Builder $query, array $data): Builder {
                        $this->topCount = $data['value'] ?? 10;
                        return $query;
                    }),
                    
                // Filter periode
                SelectFilter::make('periode')
                    ->label('Periode Waktu')
                    ->options([
                        'keseluruhan' => 'Keseluruhan Waktu',
                        'tahun_ini' => 'Tahun Ini (' . date('Y') . ')',
                        'tahun_lalu' => 'Tahun Lalu (' . (date('Y') - 1) . ')',
                        'bulan_ini' => 'Bulan Ini (' . Carbon::now()->format('F Y') . ')',
                        'bulan_lalu' => 'Bulan Lalu (' . Carbon::now()->subMonth()->format('F Y') . ')',
                        '3_bulan' => '3 Bulan Terakhir',
                        '6_bulan' => '6 Bulan Terakhir',
                        '12_bulan' => '12 Bulan Terakhir',
                        'custom' => 'Periode Kustom',
                    ])
                    ->default('keseluruhan')
                    ->query(function (Builder $query, array $data): Builder {
                        return $this->applyPeriodeFilter($query, $data['value'] ?? 'keseluruhan');
                    }),
                    
                // Filter custom date range
                Filter::make('custom_date_range')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('dari_tanggal')
                                    ->label('Dari Tanggal')
                                    ->placeholder('Pilih tanggal mulai'),
                                DatePicker::make('sampai_tanggal')
                                    ->label('Sampai Tanggal')
                                    ->placeholder('Pilih tanggal akhir'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('donasis.tanggal_donasi', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('donasis.tanggal_donasi', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = 'Dari: ' . Carbon::parse($data['dari_tanggal'])->format('d/m/Y');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                // Filter berdasarkan urutan/sorting
                SelectFilter::make('urutan')
                    ->label('Urutkan Berdasarkan')
                    ->options([
                        'total_kontribusi_desc' => 'Total Donasi (Terbesar)',
                        'total_kontribusi_asc' => 'Total Donasi (Terkecil)',
                        'total_donasi_count_desc' => 'Frekuensi Donasi (Terbanyak)',
                        'total_donasi_count_asc' => 'Frekuensi Donasi (Tersedikit)',
                        'rata_rata_donasi_desc' => 'Rata-rata Donasi (Terbesar)',
                        'rata_rata_donasi_asc' => 'Rata-rata Donasi (Terkecil)',
                        'donasi_terakhir_desc' => 'Donasi Terakhir (Terbaru)',
                        'donasi_terakhir_asc' => 'Donasi Terakhir (Terlama)',
                    ])
                    ->default('total_kontribusi_desc')
                    ->query(function (Builder $query, array $data): Builder {
                        return $this->applySortingFilter($query, $data['value'] ?? 'total_kontribusi_desc');
                    }),
                    
                // Filter berdasarkan jenis donasi
                SelectFilter::make('jenis_donasi')
                    ->label('Jenis Donasi')
                    ->options(function () {
                        return JenisDonasi::where('aktif', true)
                            ->pluck('nama', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $jenisId): Builder => 
                                $query->where('donasis.jenis_donasi_id', $jenisId)
                        );
                    }),
                    
                // Filter berdasarkan provinsi
                SelectFilter::make('provinsi')
                    ->label('Provinsi')
                    ->options(function () {
                        return Province::pluck('name', 'id')->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $provinsiId): Builder => 
                                $query->where('donaturs.province_id', $provinsiId)
                        );
                    }),
                    
                // Filter berdasarkan kategori donatur
                SelectFilter::make('kategori_donatur')
                    ->label('Kategori Donatur')
                    ->options([
                        'platinum' => 'Platinum (≥ 50 Juta)',
                        'gold' => 'Gold (≥ 10 Juta)',
                        'silver' => 'Silver (≥ 5 Juta)',
                        'bronze' => 'Bronze (≥ 1 Juta)',
                        'regular' => 'Regular (< 1 Juta)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $this->applyKategoriDonaturFilter($query, $data['value'] ?? null);
                    }),
                    
                // Filter berdasarkan minimal total donasi
                Filter::make('minimal_donasi')
                    ->form([
                        TextInput::make('jumlah')
                            ->label('Minimal Total Donasi')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Contoh: 1000000')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['jumlah'],
                            fn (Builder $query, $jumlah): Builder => 
                                $query->havingRaw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) >= ?', [$jumlah])
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['jumlah'] ?? null) {
                            return 'Minimal donasi: Rp ' . number_format($data['jumlah'], 0, ',', '.');
                        }
                        return null;
                    }),
                    
                // Filter berdasarkan minimal frekuensi donasi
                Filter::make('minimal_frekuensi')
                    ->form([
                        TextInput::make('frekuensi')
                            ->label('Minimal Frekuensi Donasi')
                            ->numeric()
                            ->suffix('kali')
                            ->placeholder('Contoh: 5')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['frekuensi'],
                            fn (Builder $query, $frekuensi): Builder => 
                                $query->havingRaw('COUNT(donasis.id) >= ?', [$frekuensi])
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['frekuensi'] ?? null) {
                            return 'Minimal frekuensi: ' . $data['frekuensi'] . ' kali';
                        }
                        return null;
                    }),
                    
                // Filter berdasarkan konsistensi donatur
                SelectFilter::make('konsistensi')
                    ->label('Tingkat Konsistensi')
                    ->options([
                        'sangat_tinggi' => 'Sangat Tinggi (≤ 30 hari)',
                        'tinggi' => 'Tinggi (≤ 60 hari)',
                        'sedang' => 'Sedang (≤ 120 hari)',
                        'rendah' => 'Rendah (≤ 365 hari)',
                        'sangat_rendah' => 'Sangat Rendah (> 365 hari)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $this->applyKonsistensiFilter($query, $data['value'] ?? null);
                    }),
                    
                // Filter berdasarkan status donatur (aktif/tidak aktif)
                SelectFilter::make('status_aktif')
                    ->label('Status Keaktifan')
                    ->options([
                        'aktif_1_bulan' => 'Aktif (1 Bulan Terakhir)',
                        'aktif_3_bulan' => 'Aktif (3 Bulan Terakhir)',
                        'aktif_6_bulan' => 'Aktif (6 Bulan Terakhir)',
                        'aktif_1_tahun' => 'Aktif (1 Tahun Terakhir)',
                        'tidak_aktif' => 'Tidak Aktif (> 1 Tahun)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $this->applyStatusAktifFilter($query, $data['value'] ?? null);
                    }),
                    
            ])
            ->paginated(false)
            ->defaultSort('total_kontribusi', 'desc')
            ->striped()
            ->searchable()
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('Lihat detail lengkap donatur')
                    ->modalHeading(fn ($record) => 'Detail Donatur: ' . $record->nama)
                    ->modalContent(function ($record) {
                        return view('filament.widgets.top-donatur.detail-modal', ['record' => $record]);
                    })
                    ->modalFooterActions([])
                    ->modalWidth('4xl'),
                    
                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->tooltip('Hubungi via WhatsApp')
                    ->url(fn ($record) => $record->nomor_hp ? 
                        'https://wa.me/' . preg_replace('/[^0-9]/', '', $record->nomor_hp) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->nomor_hp)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename(fn () => 'top-donatur-' . date('Y-m-d-H-i-s') . '.xlsx')
                        ])
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('refresh')
                    ->label('Refresh')
                    ->icon('heroicon-o-arrow-path')
                    ->tooltip('Refresh data terbaru')
                    ->action(fn () => $this->dispatch('$refresh')),
                    
                Tables\Actions\Action::make('analytics')
                    ->label('Analisis')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->tooltip('Lihat analisis mendalam')
                    ->modalHeading('Analisis Top Donatur')
                    ->modalContent(function () {
                        return view('filament.widgets.top-donatur.analytics-modal', [
                            'totalDonatur' => $this->totalDonaturAktif,
                            'totalKontribusi' => $this->totalKontribusiKeseluruhan,
                            'topCount' => $this->topCount
                        ]);
                    })
                    ->modalFooterActions([]),
            ]);
    }

    protected function getTableQuery(): Builder
    {
        return Donatur::query()
            ->join('donasis', 'donaturs.id', '=', 'donasis.donatur_id')
            ->leftJoin('provinces', 'donaturs.province_id', '=', 'provinces.id')
            ->where('donasis.status_konfirmasi', 'verified')
            ->select([
                'donaturs.id',
                'donaturs.nama',
                'donaturs.nomor_hp',
                'donaturs.email',
                'provinces.name as provinsi',
                DB::raw('COUNT(donasis.id) as total_donasi_count'),
                DB::raw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) as total_kontribusi'),
                DB::raw('AVG(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) as rata_rata_donasi'),
                DB::raw('MAX(donasis.tanggal_donasi) as donasi_terakhir'),
                DB::raw('MIN(donasis.tanggal_donasi) as donasi_pertama'),
                DB::raw('ROW_NUMBER() OVER (ORDER BY SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) DESC) as ranking'),
                DB::raw('(
                    SELECT jd.nama 
                    FROM donasis d2 
                    JOIN jenis_donasis jd ON d2.jenis_donasi_id = jd.id 
                    WHERE d2.donatur_id = donaturs.id 
                    AND d2.status_konfirmasi = "verified"
                    GROUP BY jd.id, jd.nama 
                    ORDER BY COUNT(*) DESC 
                    LIMIT 1
                ) as jenis_donasi_terfavorit')
            ])
            ->groupBy('donaturs.id', 'donaturs.nama', 'donaturs.nomor_hp', 'donaturs.email', 'provinces.name')
            ->limit($this->topCount);
    }

    protected function applyPeriodeFilter(Builder $query, string $periode): Builder
    {
        switch ($periode) {
            case 'bulan_ini':
                return $query->whereMonth('donasis.tanggal_donasi', now()->month)
                            ->whereYear('donasis.tanggal_donasi', now()->year);
                            
            case 'bulan_lalu':
                $bulanLalu = now()->subMonth();
                return $query->whereMonth('donasis.tanggal_donasi', $bulanLalu->month)
                            ->whereYear('donasis.tanggal_donasi', $bulanLalu->year);
                            
            case 'tahun_ini':
                return $query->whereYear('donasis.tanggal_donasi', now()->year);
                
            case 'tahun_lalu':
                return $query->whereYear('donasis.tanggal_donasi', now()->year - 1);
                
            case '3_bulan':
                return $query->where('donasis.tanggal_donasi', '>=', now()->subMonths(3));
                
            case '6_bulan':
                return $query->where('donasis.tanggal_donasi', '>=', now()->subMonths(6));
                
            case '12_bulan':
                return $query->where('donasis.tanggal_donasi', '>=', now()->subMonths(12));
                
            case 'keseluruhan':
            default:
                return $query; // Tidak ada filter tambahan
        }
    }

    protected function applySortingFilter(Builder $query, string $sorting): Builder
    {
        switch ($sorting) {
            case 'total_kontribusi_asc':
                return $query->orderBy('total_kontribusi', 'asc');
                
            case 'total_donasi_count_desc':
                return $query->orderByDesc('total_donasi_count');
                
            case 'total_donasi_count_asc':
                return $query->orderBy('total_donasi_count', 'asc');
                
            case 'rata_rata_donasi_desc':
                return $query->orderByDesc('rata_rata_donasi');
                
            case 'rata_rata_donasi_asc':
                return $query->orderBy('rata_rata_donasi', 'asc');
                
            case 'donasi_terakhir_desc':
                return $query->orderByDesc('donasi_terakhir');
                
            case 'donasi_terakhir_asc':
                return $query->orderBy('donasi_terakhir', 'asc');
                
            case 'total_kontribusi_desc':
            default:
                return $query->orderByDesc('total_kontribusi');
        }
    }

    protected function applyKategoriDonaturFilter(Builder $query, ?string $kategori): Builder
    {
        if (!$kategori) {
            return $query;
        }

        switch ($kategori) {
            case 'platinum':
                return $query->havingRaw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) >= 50000000');
                
            case 'gold':
                return $query->havingRaw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) >= 10000000')
                           ->havingRaw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) < 50000000');
                
            case 'silver':
                return $query->havingRaw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) >= 5000000')
                           ->havingRaw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) < 10000000');
                
            case 'bronze':
                return $query->havingRaw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) >= 1000000')
                           ->havingRaw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) < 5000000');
                
            case 'regular':
                return $query->havingRaw('SUM(COALESCE(donasis.jumlah, 0) + COALESCE(donasis.perkiraan_nilai_barang, 0)) < 1000000');
                
            default:
                return $query;
        }
    }

    protected function applyKonsistensiFilter(Builder $query, ?string $konsistensi): Builder
    {
        if (!$konsistensi) {
            return $query;
        }

        // Hanya tampilkan donatur yang memiliki lebih dari 1 donasi untuk menghitung konsistensi
        $query->havingRaw('COUNT(donasis.id) > 1');

        switch ($konsistensi) {
            case 'sangat_tinggi':
                return $query->havingRaw('
                    (DATEDIFF(MAX(donasis.tanggal_donasi), MIN(donasis.tanggal_donasi)) / GREATEST(COUNT(donasis.id) - 1, 1)) <= 30
                ');
                
            case 'tinggi':
                return $query->havingRaw('
                    (DATEDIFF(MAX(donasis.tanggal_donasi), MIN(donasis.tanggal_donasi)) / GREATEST(COUNT(donasis.id) - 1, 1)) <= 60
                    AND (DATEDIFF(MAX(donasis.tanggal_donasi), MIN(donasis.tanggal_donasi)) / GREATEST(COUNT(donasis.id) - 1, 1)) > 30
                ');
                
            case 'sedang':
                return $query->havingRaw('
                    (DATEDIFF(MAX(donasis.tanggal_donasi), MIN(donasis.tanggal_donasi)) / GREATEST(COUNT(donasis.id) - 1, 1)) <= 120
                    AND (DATEDIFF(MAX(donasis.tanggal_donasi), MIN(donasis.tanggal_donasi)) / GREATEST(COUNT(donasis.id) - 1, 1)) > 60
                ');
                
            case 'rendah':
                return $query->havingRaw('
                    (DATEDIFF(MAX(donasis.tanggal_donasi), MIN(donasis.tanggal_donasi)) / GREATEST(COUNT(donasis.id) - 1, 1)) <= 365
                    AND (DATEDIFF(MAX(donasis.tanggal_donasi), MIN(donasis.tanggal_donasi)) / GREATEST(COUNT(donasis.id) - 1, 1)) > 120
                ');
                
            case 'sangat_rendah':
                return $query->havingRaw('
                    (DATEDIFF(MAX(donasis.tanggal_donasi), MIN(donasis.tanggal_donasi)) / GREATEST(COUNT(donasis.id) - 1, 1)) > 365
                ');
                
            default:
                return $query;
        }
    }

    protected function applyStatusAktifFilter(Builder $query, ?string $status): Builder
    {
        if (!$status) {
            return $query;
        }

        switch ($status) {
            case 'aktif_1_bulan':
                return $query->havingRaw('MAX(donasis.tanggal_donasi) >= ?', [now()->subMonth()]);
                
            case 'aktif_3_bulan':
                return $query->havingRaw('MAX(donasis.tanggal_donasi) >= ?', [now()->subMonths(3)]);
                
            case 'aktif_6_bulan':
                return $query->havingRaw('MAX(donasis.tanggal_donasi) >= ?', [now()->subMonths(6)]);
                
            case 'aktif_1_tahun':
                return $query->havingRaw('MAX(donasis.tanggal_donasi) >= ?', [now()->subYear()]);
                
            case 'tidak_aktif':
                return $query->havingRaw('MAX(donasis.tanggal_donasi) < ?', [now()->subYear()]);
                
            default:
                return $query;
        }
    }
}
