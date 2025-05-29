<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use App\Models\Donasi;
use App\Models\JenisDonasi;
use App\Models\MetodePembayaran;
use App\Models\Fundraiser;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Notifications\Notification; // Keep if you use notifications elsewhere
use Filament\Support\Enums\IconPosition; // Keep for export actions
use Illuminate\Support\HtmlString; // Keep if used, or remove
use Illuminate\Support\Facades\DB;

// Import untuk ekspor data
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Actions\Action; // For the refresh button

class LaporanPemasukan extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.laporan-pemasukan'; //
    protected static ?string $navigationGroup = 'Laporan & Keuangan'; //
    protected static ?string $title = 'Laporan Pemasukan Donasi'; //
    protected static ?string $navigationLabel = 'Pemasukan Donasi'; //
    protected static ?int $navigationSort = 1; //

    // Properti untuk menyimpan nilai filter
    public ?string $startDate = null; //
    public ?string $endDate = null; //
    public ?int $jenisDonasiId = null; //
    public ?int $metodePembayaranId = null; //
    public ?int $fundraiserId = null; //
    public ?string $statusKonfirmasi = 'verified'; //
    public bool $groupByJenisDonasi = false; //
    public bool $showBarangOnly = false; //
    public bool $showUangOnly = false; //
    public bool $showHambaAllahOnly = false; //

    // Untuk menyimpan total pemasukan (current period)
    public float $totalPemasukan = 0; //
    public float $totalNilaiBarang = 0; //
    public float $grandTotalPemasukan = 0; //
    public int $totalTransaksi = 0; //
    public array $summaryByJenisDonasi = []; //

    // Untuk menyimpan total pemasukan (previous period)
    public float $totalPemasukanPrev = 0;
    public float $totalNilaiBarangPrev = 0;
    public float $grandTotalPemasukanPrev = 0;
    public int $totalTransaksiPrev = 0;
    public ?string $previousPeriodLabel = null;


    // Untuk menyimpan perubahan persentase
    public ?float $pemasukanChange = null;
    public ?float $nilaiBarangChange = null;
    public ?float $grandTotalChange = null;
    public ?float $transaksiChange = null;

    // Removed chart data property: public array $donationTrendData = [];

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d'); //
        $this->endDate = now()->format('Y-m-d'); //
        
        $this->calculateAllMetrics();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Filter Laporan')
                ->description('Sesuaikan filter untuk melihat data yang diinginkan')
                ->icon('heroicon-o-funnel') //
                ->collapsible()
                ->columns([
                    'sm' => 2,
                    'md' => 3,
                    'lg' => 4,
                ])
                ->schema([
                    Grid::make()
                        ->columnSpan(2)
                        ->schema([
                            DatePicker::make('startDate')
                                ->label('Dari Tanggal')
                                ->reactive() //
                                ->maxDate(fn (?string $state, callable $get) => $get('endDate') ?: now())
                                ->default(now()->startOfMonth()), //
                            DatePicker::make('endDate')
                                ->label('Sampai Tanggal')
                                ->reactive() //
                                ->minDate(fn (?string $state, callable $get) => $get('startDate') ?: null)
                                ->default(now()), //
                        ]),
                    
                    Select::make('jenisDonasiId')
                        ->label('Jenis Donasi')
                        ->options(JenisDonasi::where('aktif', true)->pluck('nama', 'id')) //
                        ->searchable() //
                        ->preload() //
                        ->reactive() //
                        ->afterStateUpdated(fn () => $this->showBarangOnly = false), //
                    
                    Select::make('metodePembayaranId')
                        ->label('Metode Pembayaran')
                        ->options(MetodePembayaran::where('aktif', true)->pluck('nama', 'id')) //
                        ->searchable() //
                        ->preload() //
                        ->reactive(), //
                    
                    Select::make('fundraiserId')
                        ->label('Fundraiser')
                        ->options(Fundraiser::where('aktif', true)->pluck('nama_fundraiser', 'id')) //
                        ->searchable() //
                        ->preload() //
                        ->reactive(), //
                    
                    Select::make('statusKonfirmasi')
                        ->label('Status Konfirmasi')
                        ->options([
                            'all' => 'Semua Status',
                            'pending' => 'Pending',
                            'verified' => 'Terverifikasi',
                            'rejected' => 'Ditolak',
                        ]) //
                        ->default('verified') //
                        ->reactive(), //
                    
                    Toggle::make('groupByJenisDonasi')
                        ->label('Kelompokkan per Jenis Donasi (Summary)')
                        ->helperText('Menampilkan ringkasan per jenis donasi di bawah tabel')
                        ->reactive(), //
                    
                    Toggle::make('showBarangOnly')
                        ->label('Hanya Donasi Barang')
                        ->reactive() //
                        ->afterStateUpdated(function (bool $state) { //
                            if ($state) {
                                $this->showUangOnly = false;
                                $this->jenisDonasiId = null; 
                            }
                        }),
                    
                    Toggle::make('showUangOnly')
                        ->label('Hanya Donasi Uang')
                        ->reactive() //
                        ->afterStateUpdated(function (bool $state) { //
                            if ($state) {
                                $this->showBarangOnly = false;
                                $this->jenisDonasiId = null;
                            }
                        }),
                    
                    Toggle::make('showHambaAllahOnly')
                        ->label('Hanya Donasi Hamba Allah')
                        ->reactive(), //
                ]),
        ];
    }
    
    // Add a method for the refresh button
    public function refreshDataAction(): Action
    {
        return Action::make('refreshData')
            ->label('Refresh Data')
            ->icon('heroicon-o-arrow-path')
            ->color('secondary')
            ->action(function () {
                // Call calculateAllMetrics instead, which internally calls calculateTotals with proper parameters
                $this->calculateAllMetrics();
                
                // Notify the user that data has been refreshed.
                Notification::make()
                    ->title('Data berhasil di-refresh')
                    ->success()
                    ->send();
            });
    }

    // We'll override getHeaderActions to include our custom refresh button
    protected function getHeaderActions(): array
    {
        return []; // Empty array - no header actions
    }


    public function updated($propertyName): void
    {
        $this->resetPage(); // For table pagination
        $this->calculateAllMetrics();
    }

    protected function calculateAllMetrics(): void
    {
        // Calculate for current period
        $this->calculateTotals($this->startDate, $this->endDate, false);
        
        // Calculate for previous period
        if ($this->startDate && $this->endDate) {
            $currentStart = Carbon::parse($this->startDate);
            $currentEnd = Carbon::parse($this->endDate);

            // Calculate the duration of the current period in days
            $durationInDays = $currentEnd->diffInDays($currentStart) + 1; // +1 to include both start and end dates
            
            // Calculate the previous period with the same duration
            $prevEndDate = $currentStart->copy()->subDay(); // Day before current start
            $prevStartDate = $prevEndDate->copy()->subDays($durationInDays - 1); // Same duration as current period
            
            if ($prevStartDate && $prevEndDate) {
                $this->calculateTotals($prevStartDate->format('Y-m-d'), $prevEndDate->format('Y-m-d'), true);
                $this->previousPeriodLabel = $prevStartDate->translatedFormat('d M Y') . ' - ' . $prevEndDate->translatedFormat('d M Y');
            } else {
                // Fallback
                $this->resetPreviousPeriodData();
                $this->previousPeriodLabel = 'N/A';
            }

            $this->calculatePercentageChanges();
        } else {
            $this->resetPreviousPeriodData();
            $this->resetPercentageChanges();
        }
        
        if ($this->groupByJenisDonasi) {
            $this->calculateSummaryByJenisDonasi();
        } else {
            $this->summaryByJenisDonasi = [];
        }
    }

    protected function resetPreviousPeriodData(): void
    {
        $this->totalPemasukanPrev = 0;
        $this->totalNilaiBarangPrev = 0;
        $this->grandTotalPemasukanPrev = 0;
        $this->totalTransaksiPrev = 0;
        $this->previousPeriodLabel = null;
    }
    
    protected function getBaseQuery(?string $startDate, ?string $endDate): Builder
    {
        $query = Donasi::query();

        if ($startDate) {
            $query->whereDate('tanggal_donasi', '>=', $startDate); //
        }
        if ($endDate) {
            $query->whereDate('tanggal_donasi', '<=', $endDate); //
        }
        // Apply other filters
        if ($this->jenisDonasiId) { //
            $query->where('jenis_donasi_id', $this->jenisDonasiId); //
        }
        if ($this->metodePembayaranId) { //
            $query->where('metode_pembayaran_id', $this->metodePembayaranId); //
        }
        if ($this->fundraiserId) { //
            $query->where('fundraiser_id', $this->fundraiserId); //
        }
        if ($this->statusKonfirmasi && $this->statusKonfirmasi !== 'all') { //
            $query->where('status_konfirmasi', $this->statusKonfirmasi); //
        }
         if ($this->showBarangOnly) { //
            $query->whereHas('jenisDonasi', function ($q) { //
                $q->where('apakah_barang', true); //
            });
        }
        if ($this->showUangOnly) { //
            $query->whereHas('jenisDonasi', function ($q) { //
                $q->where('apakah_barang', false); //
            });
        }
        if ($this->showHambaAllahOnly) { //
            $query->where('atas_nama_hamba_allah', true); //
        }
        return $query;
    }

    protected function calculateTotals(?string $startDate, ?string $endDate, bool $isPreviousPeriod): void
    {
        if (!$startDate || !$endDate) { // Add guard for invalid date ranges
            if ($isPreviousPeriod) $this->resetPreviousPeriodData();
            return;
        }
        $query = $this->getBaseQuery($startDate, $endDate);

        if ($isPreviousPeriod) {
            $this->totalPemasukanPrev = (clone $query)->whereHas('jenisDonasi', fn ($q) => $q->where('apakah_barang', false))->sum('jumlah');
            $this->totalNilaiBarangPrev = (clone $query)->whereHas('jenisDonasi', fn ($q) => $q->where('apakah_barang', true))->sum('perkiraan_nilai_barang');
            $this->grandTotalPemasukanPrev = $this->totalPemasukanPrev + $this->totalNilaiBarangPrev;
            $this->totalTransaksiPrev = (clone $query)->count();
        } else {
            $this->totalPemasukan = (clone $query)->whereHas('jenisDonasi', fn ($q) => $q->where('apakah_barang', false))->sum('jumlah');
            $this->totalNilaiBarang = (clone $query)->whereHas('jenisDonasi', fn ($q) => $q->where('apakah_barang', true))->sum('perkiraan_nilai_barang');
            $this->grandTotalPemasukan = $this->totalPemasukan + $this->totalNilaiBarang; //
            $this->totalTransaksi = (clone $query)->count();
        }
    }

    protected function calculatePercentageChanges(): void
    {
        $this->pemasukanChange = $this->calculateChange($this->totalPemasukan, $this->totalPemasukanPrev);
        $this->nilaiBarangChange = $this->calculateChange($this->totalNilaiBarang, $this->totalNilaiBarangPrev);
        $this->grandTotalChange = $this->calculateChange($this->grandTotalPemasukan, $this->grandTotalPemasukanPrev);
        $this->transaksiChange = $this->calculateChange($this->totalTransaksi, $this->totalTransaksiPrev);
    }
    
    protected function resetPercentageChanges(): void
    {
        $this->pemasukanChange = null;
        $this->nilaiBarangChange = null;
        $this->grandTotalChange = null;
        $this->transaksiChange = null;
    }

    private function calculateChange($current, $previous): ?float
    {
        if ($previous == 0) {
            return ($current > 0) ? 100.0 : (($current == 0) ? 0.0 : null); // If prev is 0, and current is also 0, change is 0%. If current > 0, it's 100% increase from nothing.
        }
        // Avoid division by zero if previous is zero and current is also zero (already handled by above)
        // if ($current == 0 && $previous == 0){
        //     return 0.0;
        // }
        return (($current - $previous) / $previous) * 100;
    }

    // REMOVED: protected function prepareDonationTrendData(): void { ... }


    protected function getTableQuery(): Builder
    {
        return $this->getBaseQuery($this->startDate, $this->endDate)
             ->with(['donatur', 'jenisDonasi', 'metodePembayaran', 'fundraiser', 'dicatatOleh']); //
    }

    public function table(Table $table): Table
    {
        // Table definition remains largely the same.
        // Removed ExportAction from headerActions here as it's moved to getHeaderActions.
        return $table
            ->query($this->getTableQuery()) //
            ->columns([
                TextColumn::make('nomor_transaksi_unik') //
                    ->label('No. Transaksi') //
                    ->searchable() //
                    ->sortable() //
                    ->copyable(), //
                
                TextColumn::make('tanggal_donasi') //
                    ->label('Tgl. Donasi') //
                    ->date('d M Y') //
                    ->sortable(), //
                
                TextColumn::make('donatur.nama') //
                    ->label('Donatur') //
                    ->searchable() //
                    ->sortable() //
                    ->formatStateUsing(fn ($state, $record) => $record->atas_nama_hamba_allah ? 'Hamba Allah' : $state), //
                
                TextColumn::make('jenisDonasi.nama') //
                    ->label('Jenis Donasi') //
                    ->badge() //
                    ->color(fn ($record) => $record->jenisDonasi?->apakah_barang ? 'warning' : 'primary') //
                    ->searchable() //
                    ->sortable(), //
                
                TextColumn::make('metodePembayaran.nama') //
                    ->label('Metode Bayar') //
                    ->toggleable() //
                    ->sortable(), //
                
                TextColumn::make('fundraiser.nama_fundraiser') //
                    ->label('Fundraiser') //
                    ->toggleable(isToggledHiddenByDefault: true) //
                    ->sortable(), //
                
                TextColumn::make('jumlah') //
                    ->label('Jumlah (Uang)') //
                    ->money('IDR') //
                    ->sortable() //
                    ->formatStateUsing(fn ($state, $record) => $record->jenisDonasi?->apakah_barang ? '-' : 'Rp ' . number_format($state, 0, ',', '.')), //
                
                TextColumn::make('perkiraan_nilai_barang') //
                    ->label('Nilai Barang') //
                    ->money('IDR') //
                    ->sortable() //
                    ->formatStateUsing(fn ($state, $record) => $record->jenisDonasi?->apakah_barang ? ('Rp ' . number_format($state, 0, ',', '.')) : '-'), //
                
                TextColumn::make('status_konfirmasi') //
                    ->label('Status') //
                    ->badge() //
                    ->color(fn (string $state): string => match ($state) { //
                        'pending' => 'warning',
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) { //
                        'pending' => 'Pending',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    })
                    ->searchable() //
                    ->sortable(), //
                
                IconColumn::make('atas_nama_hamba_allah') //
                    ->boolean() //
                    ->label('Anonim') //
                    ->toggleable(), //
                
                TextColumn::make('dicatatOleh.name') //
                    ->label('Dicatat Oleh') //
                    ->toggleable(isToggledHiddenByDefault: true) //
                    ->sortable(), //
                
                TextColumn::make('created_at') //
                    ->label('Tgl Input') //
                    ->dateTime('d M Y H:i') //
                    ->toggleable(isToggledHiddenByDefault: true) //
                    ->sortable(), //
            ])
            ->filters([ //
                // Filters remain the same
                Filter::make('tanggal_donasi')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_donasi', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_donasi', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators['dari_tanggal'] = 'Dari ' . Carbon::parse($data['dari_tanggal'])->translatedFormat('d M Y');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators['sampai_tanggal'] = 'Sampai ' . Carbon::parse($data['sampai_tanggal'])->translatedFormat('d M Y');
                        }
                        return $indicators;
                    }),
                
                SelectFilter::make('jenis_donasi_id') //
                    ->label('Jenis Donasi') //
                    ->relationship('jenisDonasi', 'nama') //
                    ->preload(), //
                
                SelectFilter::make('metode_pembayaran_id') //
                    ->label('Metode Pembayaran') //
                    ->relationship('metodePembayaran', 'nama') //
                    ->preload(), //
                
                SelectFilter::make('status_konfirmasi') //
                    ->label('Status Konfirmasi') //
                    ->options([ //
                        'pending' => 'Pending',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                    ]),
                
                Filter::make('jenis_donasi_barang') //
                    ->label('Jenis Donasi (Barang/Uang)') //
                    ->query(function (Builder $query, array $data): Builder { //
                        return $query->when(
                            $data['jenis'] ?? null,
                            function (Builder $query, $jenis): Builder {
                                if ($jenis === 'barang') {
                                    return $query->whereHas('jenisDonasi', fn ($q) => $q->where('apakah_barang', true));
                                } elseif ($jenis === 'uang') {
                                    return $query->whereHas('jenisDonasi', fn ($q) => $q->where('apakah_barang', false));
                                }
                                return $query;
                            }
                        );
                    })
                    ->form([ //
                        Select::make('jenis')
                            ->options([
                                'barang' => 'Hanya Donasi Barang',
                                'uang' => 'Hanya Donasi Uang',
                            ])
                            ->placeholder('Semua Jenis'),
                    ]),
                
                Filter::make('atas_nama_hamba_allah') //
                    ->label('Donasi Anonim') //
                    ->query(fn (Builder $query): Builder => $query->where('atas_nama_hamba_allah', true)) //
                    ->toggle(), //
            ])
            ->defaultSort('tanggal_donasi', 'desc') //
            ->actions([
                // Aksi per baris jika diperlukan
            ])
            ->bulkActions([ //
                ExportBulkAction::make() //
                    ->label('Ekspor Data Terpilih') //
                    ->exports([
                        ExcelExport::make()
                            ->withColumns([ // Columns definition as per original code
                                Column::make('nomor_transaksi_unik')->heading('No. Transaksi'),
                                Column::make('tanggal_donasi')->heading('Tanggal Donasi')->formatStateUsing(fn ($state) => Carbon::parse($state)->format('d/m/Y')),
                                Column::make('donatur.nama')->heading('Nama Donatur')->formatStateUsing(fn ($state, $record) => $record->atas_nama_hamba_allah ? 'Hamba Allah' : $state),
                                Column::make('jenisDonasi.nama')->heading('Jenis Donasi'),
                                Column::make('metodePembayaran.nama')->heading('Metode Pembayaran'),
                                Column::make('jumlah')->heading('Jumlah (Rp)')->formatStateUsing(fn ($state, $record) => $record->jenisDonasi?->apakah_barang ? 0 : $state),
                                Column::make('perkiraan_nilai_barang')->heading('Nilai Barang (Rp)')->formatStateUsing(fn ($state, $record) => $record->jenisDonasi?->apakah_barang ? $state : 0),
                                Column::make('status_konfirmasi')->heading('Status Konfirmasi'),
                                Column::make('atas_nama_hamba_allah')->heading('Anonim')->formatStateUsing(fn ($state) => $state ? 'Ya' : 'Tidak'),
                                Column::make('fundraiser.nama_fundraiser')->heading('Fundraiser'),
                                Column::make('dicatatOleh.name')->heading('Dicatat Oleh'),
                            ]) //
                    ])
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('refreshTable')
                    ->label('Refresh Tabel')
                    ->icon('heroicon-o-arrow-path')
                    ->color('secondary')
                    ->action(function () {
                        $this->calculateAllMetrics();
                        
                        Notification::make()
                            ->title('Tabel berhasil di-refresh')
                            ->success()
                            ->send();
                    }),
                ExportAction::make() // Keep the export action
            ]);
    }

    public function calculateSummaryByJenisDonasi(): void
    {
        // This uses the main table query, which now includes all filters via getBaseQuery
        $summary = $this->getTableQuery() 
            ->getQuery() 
            ->select(
                'jenis_donasi_id',
                DB::raw('MAX(jenis_donasis.nama) as jenis_donasi_name'), 
                DB::raw('SUM(donasis.jumlah) as total_jumlah_raw'), // Sum of 'jumlah' column
                DB::raw('SUM(donasis.perkiraan_nilai_barang) as total_nilai_barang_raw') // Sum of 'perkiraan_nilai_barang'
            )
            ->join('jenis_donasis', 'donasis.jenis_donasi_id', '=', 'jenis_donasis.id') 
            ->groupBy('jenis_donasi_id')
            ->get();
    
        $this->summaryByJenisDonasi = $summary->map(function ($item) {
            $jenisDonasi = JenisDonasi::find($item->jenis_donasi_id);
            $jumlahUang = 0;
            $nilaiBarang = 0;

            if ($jenisDonasi) {
                if ($jenisDonasi->apakah_barang) {
                    // If it's a goods donation, perkiraan_nilai_barang is its value. 'jumlah' should be 0 or ignored.
                    $nilaiBarang = $item->total_nilai_barang_raw;
                } else {
                    // If it's a money donation, 'jumlah' is its value. 'perkiraan_nilai_barang' should be 0 or ignored.
                    $jumlahUang = $item->total_jumlah_raw;
                }
            }
            
            return [
                'jenis_donasi_id' => $item->jenis_donasi_id, //
                'jenis_donasi_name' => $item->jenis_donasi_name, //
                'total_jumlah' => $jumlahUang, // Corrected to use processed sums
                'total_nilai_barang' => $nilaiBarang, // Corrected to use processed sums
                'total' => $jumlahUang + $nilaiBarang, //
            ];
        })->toArray();
    }
    
    // REMOVED: protected function getHeaderWidgets(): array { ... }
    // This is not needed as chart is removed.

    // Method for JS to call if needed for chart (now removed)
    // public function getDonationTrendDataForJs() { return null; } // Or remove if not used by anything else


    protected $queryString = [
        'startDate' => ['except' => ''], //
        'endDate' => ['except' => ''], //
        'jenisDonasiId' => ['except' => ''], //
        'metodePembayaranId' => ['except' => ''], //
        'fundraiserId' => ['except' => ''], //
        'statusKonfirmasi' => ['except' => 'verified'], //
        'groupByJenisDonasi' => ['except' => false], //
        'showBarangOnly' => ['except' => false], //
        'showUangOnly' => ['except' => false], //
        'showHambaAllahOnly' => ['except' => false], //
    ];
}