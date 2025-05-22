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
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\HtmlString;

// Import untuk ekspor data
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;

class LaporanPemasukan extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.laporan-pemasukan';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $title = 'Laporan Pemasukan Donasi';
    protected static ?string $navigationLabel = 'Pemasukan Donasi';
    protected static ?int $navigationSort = 1;

    // Properti untuk menyimpan nilai filter
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $jenisDonasiId = null;
    public ?int $metodePembayaranId = null;
    public ?int $fundraiserId = null;
    public ?string $statusKonfirmasi = 'verified';
    public bool $groupByJenisDonasi = false;
    public bool $showBarangOnly = false;
    public bool $showUangOnly = false;
    public bool $showHambaAllahOnly = false;

    // Untuk menyimpan total pemasukan
    public float $totalPemasukan = 0;
    public float $totalNilaiBarang = 0;
    public float $grandTotalPemasukan = 0;
    public int $totalTransaksi = 0;
    public array $summaryByJenisDonasi = [];

    public function mount(): void
    {
        // Set default tanggal ke bulan ini
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        
        $this->calculateTotals();
        $this->calculateSummaryByJenisDonasi();
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Filter Laporan')
                ->description('Sesuaikan filter untuk melihat data yang diinginkan')
                ->icon('heroicon-o-funnel')
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
                                ->reactive()
                                ->maxDate(fn (?string $state, callable $get) => $get('endDate') ?: now())
                                ->default(now()->startOfMonth()),
                            DatePicker::make('endDate')
                                ->label('Sampai Tanggal')
                                ->reactive()
                                ->minDate(fn (?string $state, callable $get) => $get('startDate') ?: null)
                                ->default(now()),
                        ]),
                    
                    Select::make('jenisDonasiId')
                        ->label('Jenis Donasi')
                        ->options(JenisDonasi::where('aktif', true)->pluck('nama', 'id'))
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(fn () => $this->showBarangOnly = false),
                    
                    Select::make('metodePembayaranId')
                        ->label('Metode Pembayaran')
                        ->options(MetodePembayaran::where('aktif', true)->pluck('nama', 'id'))
                        ->searchable()
                        ->preload()
                        ->reactive(),
                    
                    Select::make('fundraiserId')
                        ->label('Fundraiser')
                        ->options(Fundraiser::where('aktif', true)->pluck('nama_fundraiser', 'id'))
                        ->searchable()
                        ->preload()
                        ->reactive(),
                    
                    Select::make('statusKonfirmasi')
                        ->label('Status Konfirmasi')
                        ->options([
                            'all' => 'Semua Status',
                            'pending' => 'Pending',
                            'verified' => 'Terverifikasi',
                            'rejected' => 'Ditolak',
                        ])
                        ->default('verified')
                        ->reactive(),
                    
                    Toggle::make('groupByJenisDonasi')
                        ->label('Kelompokkan per Jenis Donasi')
                        ->helperText('Menampilkan ringkasan per jenis donasi')
                        ->reactive()
                        ->afterStateUpdated(fn () => $this->calculateSummaryByJenisDonasi()),
                    
                    Toggle::make('showBarangOnly')
                        ->label('Hanya Donasi Barang')
                        ->reactive()
                        ->afterStateUpdated(function (bool $state) {
                            if ($state) {
                                $this->showUangOnly = false;
                                $this->jenisDonasiId = null;
                            }
                        }),
                    
                    Toggle::make('showUangOnly')
                        ->label('Hanya Donasi Uang')
                        ->reactive()
                        ->afterStateUpdated(function (bool $state) {
                            if ($state) {
                                $this->showBarangOnly = false;
                                $this->jenisDonasiId = null;
                            }
                        }),
                    
                    Toggle::make('showHambaAllahOnly')
                        ->label('Hanya Donasi Hamba Allah')
                        ->reactive(),
                ]),
        ];
    }

    public function updated($propertyName): void
    {
        $this->resetPage();
        $this->calculateTotals();
        
        if ($this->groupByJenisDonasi) {
            $this->calculateSummaryByJenisDonasi();
        }
    }

    protected function getTableQuery(): Builder
    {
        $query = Donasi::query()
            ->with(['donatur', 'jenisDonasi', 'metodePembayaran', 'fundraiser', 'dicatatOleh']);

        if ($this->startDate) {
            $query->whereDate('tanggal_donasi', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('tanggal_donasi', '<=', $this->endDate);
        }
        if ($this->jenisDonasiId) {
            $query->where('jenis_donasi_id', $this->jenisDonasiId);
        }
        if ($this->metodePembayaranId) {
            $query->where('metode_pembayaran_id', $this->metodePembayaranId);
        }
        if ($this->fundraiserId) {
            $query->where('fundraiser_id', $this->fundraiserId);
        }
        if ($this->statusKonfirmasi && $this->statusKonfirmasi !== 'all') {
            $query->where('status_konfirmasi', $this->statusKonfirmasi);
        }
        if ($this->showBarangOnly) {
            $query->whereHas('jenisDonasi', function ($q) {
                $q->where('apakah_barang', true);
            });
        }
        if ($this->showUangOnly) {
            $query->whereHas('jenisDonasi', function ($q) {
                $q->where('apakah_barang', false);
            });
        }
        if ($this->showHambaAllahOnly) {
            $query->where('atas_nama_hamba_allah', true);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('nomor_transaksi_unik')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                TextColumn::make('tanggal_donasi')
                    ->label('Tgl. Donasi')
                    ->date('d M Y')
                    ->sortable(),
                
                TextColumn::make('donatur.nama')
                    ->label('Donatur')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->atas_nama_hamba_allah ? 'Hamba Allah' : $state),
                
                TextColumn::make('jenisDonasi.nama')
                    ->label('Jenis Donasi')
                    ->badge()
                    ->color(fn ($record) => $record->jenisDonasi?->apakah_barang ? 'warning' : 'primary')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('metodePembayaran.nama')
                    ->label('Metode Bayar')
                    ->toggleable()
                    ->sortable(),
                
                TextColumn::make('fundraiser.nama_fundraiser')
                    ->label('Fundraiser')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                
                TextColumn::make('jumlah')
                    ->label('Jumlah (Uang)')
                    ->money('IDR')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->jenisDonasi?->apakah_barang ? '-' : 'Rp ' . number_format($state, 0, ',', '.')),
                
                TextColumn::make('perkiraan_nilai_barang')
                    ->label('Nilai Barang')
                    ->money('IDR')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->jenisDonasi?->apakah_barang ? ('Rp ' . number_format($state, 0, ',', '.')) : '-'),
                
                TextColumn::make('status_konfirmasi')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                        default => ucfirst($state),
                    })
                    ->searchable()
                    ->sortable(),
                
                IconColumn::make('atas_nama_hamba_allah')
                    ->boolean()
                    ->label('Anonim')
                    ->toggleable(),
                
                TextColumn::make('dicatatOleh.name')
                    ->label('Dicatat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Tgl Input')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
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
                
                SelectFilter::make('jenis_donasi_id')
                    ->label('Jenis Donasi')
                    ->relationship('jenisDonasi', 'nama')
                    ->preload(),
                
                SelectFilter::make('metode_pembayaran_id')
                    ->label('Metode Pembayaran')
                    ->relationship('metodePembayaran', 'nama')
                    ->preload(),
                
                SelectFilter::make('status_konfirmasi')
                    ->label('Status Konfirmasi')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                    ]),
                
                Filter::make('jenis_donasi_barang')
                    ->label('Jenis Donasi (Barang/Uang)')
                    ->query(function (Builder $query, array $data): Builder {
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
                    ->form([
                        Select::make('jenis')
                            ->options([
                                'barang' => 'Hanya Donasi Barang',
                                'uang' => 'Hanya Donasi Uang',
                            ])
                            ->placeholder('Semua Jenis'),
                    ]),
                
                Filter::make('atas_nama_hamba_allah')
                    ->label('Donasi Anonim')
                    ->query(fn (Builder $query): Builder => $query->where('atas_nama_hamba_allah', true))
                    ->toggle(),
            ])
            ->defaultSort('tanggal_donasi', 'desc')
            ->actions([
                // Aksi per baris jika diperlukan
            ])
            ->bulkActions([
                ExportBulkAction::make()
                    ->label('Ekspor Data Terpilih')
                    ->exports([
                        ExcelExport::make()
                            ->askForFilename()
                            ->askForWriterType()
                            ->withFilename(fn () => 'Laporan-Pemasukan-' . now()->format('Y-m-d'))
                            ->withColumns([
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
                            ])
                    ])
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Ekspor Semua Data')
                    ->color('success')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->iconPosition(IconPosition::After)
                    ->exports([
                        ExcelExport::make()
                            ->askForFilename()
                            ->askForWriterType()
                            ->withFilename(fn () => 'Laporan-Pemasukan-Lengkap-' . now()->format('Y-m-d'))
                            ->withColumns([
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
                            ])
                    ])
            ]);
    }

    public function calculateTotals(): void
    {
        $queryForTotals = $this->getTableQuery()->getQuery();
        $this->totalPemasukan = (clone $queryForTotals)->sum('jumlah');
        $this->totalNilaiBarang = (clone $queryForTotals)->sum('perkiraan_nilai_barang');
        $this->grandTotalPemasukan = $this->totalPemasukan + $this->totalNilaiBarang;
        $this->totalTransaksi = $queryForTotals->count();
    }

    public function calculateSummaryByJenisDonasi(): void
    {
        if (!$this->groupByJenisDonasi) {
            $this->summaryByJenisDonasi = [];
            return;
        }

        $summary = $this->getTableQuery()
            ->select('jenis_donasi_id', 'jenisDonasi.nama as jenis_donasi_name')
            ->selectRaw('SUM(jumlah) as total_jumlah')
            ->selectRaw('SUM(perkiraan_nilai_barang) as total_nilai_barang')
            ->groupBy('jenis_donasi_id', 'jenis_donasi_name')
            ->get();

        $this->summaryByJenisDonasi = $summary->map(function ($item) {
            return [
                'jenis_donasi_id' => $item->jenis_donasi_id,
                'jenis_donasi_name' => $item->jenis_donasi_name,
                'total_jumlah' => $item->total_jumlah,
                'total_nilai_barang' => $item->total_nilai_barang,
                'total' => $item->total_jumlah + $item->total_nilai_barang,
            ];
        })->toArray();
    }

    protected $queryString = [
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'jenisDonasiId' => ['except' => ''],
        'metodePembayaranId' => ['except' => ''],
        'fundraiserId' => ['except' => ''],
        'statusKonfirmasi' => ['except' => 'verified'],
        'groupByJenisDonasi' => ['except' => false],
        'showBarangOnly' => ['except' => false],
        'showUangOnly' => ['except' => false],
        'showHambaAllahOnly' => ['except' => false],
    ];
}
