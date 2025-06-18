<?php

namespace App\Filament\Resources\DonaturResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use App\Models\JenisDonasi;
use App\Models\KategoriInfaqTerikat;
use App\Models\KategoriDanaNonHalal;
use App\Models\Donasi;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Carbon\Carbon;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


class DonasisRelationManager extends RelationManager
{
    protected static string $relationship = 'donasis';

    protected static ?string $title = 'Donasi';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('jenis_donasi_id')
                    ->relationship('jenisDonasi', 'nama')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Jenis Donasi')
                    ->reactive()
                    ->afterStateUpdated(function (Set $set) {
                        $set('keterangan_infak_khusus', null);
                        $set('deskripsi_barang', null);
                        $set('perkiraan_nilai_barang', null);
                    }),
                
                Select::make('metode_pembayaran_id')
                    ->relationship('metodePembayaran', 'nama')
                    ->searchable()
                    ->preload()
                    ->label('Metode Pembayaran'),
                
                TextInput::make('jumlah')
                    ->placeholder('7.000')
                    ->prefix('Rp')
                    ->extraInputAttributes([
                        'x-data' => '{
                            formatNumber(value) {
                                // Remove all non-digit characters
                                let numbers = value.replace(/\D/g, "");
                                // Add thousand separators
                                return numbers.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        }',
                        'x-on:input' => '$event.target.value = formatNumber($event.target.value)',
                        'x-on:paste' => 'setTimeout(() => { $event.target.value = formatNumber($event.target.value) }, 10)'
                    ])
                    ->required()
                    ->dehydrateStateUsing(fn ($state) => 
                        $state ? (float) str_replace(['.', ','], ['', '.'], $state) : null
                    )
                    ->formatStateUsing(fn ($state) => 
                        $state ? number_format($state, 0, ',', '.') : null
                    )
                    ->visible(function (Get $get) {
                        $jenisDonasiId = $get('jenis_donasi_id');
                        if (!$jenisDonasiId) return true;
                        $jenisDonasi = JenisDonasi::find($jenisDonasiId);
                        return $jenisDonasi && !$jenisDonasi->apakah_barang;
                    }),
                
                Select::make('keterangan_infak_khusus')
                    ->label('Kategori Infaq Terikat/DSKL')
                    ->options(function () {
                        return \App\Models\KategoriInfaqTerikat::aktif()
                            ->urutan()
                            ->pluck('nama_kategori', 'nama_kategori');
                    })
                    ->searchable()
                    ->visible(function (Get $get) {
                        $jenisDonasiId = $get('jenis_donasi_id');
                        if (!$jenisDonasiId) return false;
                        $jenisDonasi = JenisDonasi::find($jenisDonasiId);
                        return $jenisDonasi && 
                               $jenisDonasi->membutuhkan_keterangan_tambahan && 
                               !$jenisDonasi->mengandung_dana_non_halal && 
                               !$jenisDonasi->apakah_barang;
                    }),
                
                Select::make('kategori_dana_non_halal_id')
                    ->label('Kategori Dana Non Halal')
                    ->options(function () {
                        return \App\Models\KategoriDanaNonHalal::aktif()
                            ->urutan()
                            ->pluck('nama', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->visible(function (Get $get) {
                        $jenisDonasiId = $get('jenis_donasi_id');
                        if (!$jenisDonasiId) return false;
                        $jenisDonasi = JenisDonasi::find($jenisDonasiId);
                        return $jenisDonasi && $jenisDonasi->mengandung_dana_non_halal && !$jenisDonasi->apakah_barang;
                    }),
                
                Textarea::make('deskripsi_barang')
                    ->label('Deskripsi Barang')
                    ->visible(function (Get $get) {
                        $jenisDonasiId = $get('jenis_donasi_id');
                        if (!$jenisDonasiId) return false;
                        $jenisDonasi = JenisDonasi::find($jenisDonasiId);
                        return $jenisDonasi && $jenisDonasi->apakah_barang;
                    }),
                
                TextInput::make('perkiraan_nilai_barang')
                    ->placeholder('70.000')
                    ->prefix('Rp')
                    ->label('Perkiraan Nilai Barang')
                    ->extraInputAttributes([
                        'x-data' => '{
                            formatNumber(value) {
                                // Remove all non-digit characters
                                let numbers = value.replace(/\D/g, "");
                                // Add thousand separators
                                return numbers.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        }',
                        'x-on:input' => '$event.target.value = formatNumber($event.target.value)',
                        'x-on:paste' => 'setTimeout(() => { $event.target.value = formatNumber($event.target.value) }, 10)'
                    ])
                    ->dehydrateStateUsing(fn ($state) => 
                        $state ? (float) str_replace(['.', ','], ['', '.'], $state) : null
                    )
                    ->formatStateUsing(fn ($state) => 
                        $state ? number_format($state, 0, ',', '.') : null
                    )
                    ->visible(function (Get $get) {
                        $jenisDonasiId = $get('jenis_donasi_id');
                        if (!$jenisDonasiId) return false;
                        $jenisDonasi = JenisDonasi::find($jenisDonasiId);
                        return $jenisDonasi && $jenisDonasi->apakah_barang;
                    }),
                
                DatePicker::make('tanggal_donasi')
                    ->default(now())
                    ->required()
                    ->label('Tanggal Donasi'),
                
                FileUpload::make('bukti_pembayaran')
                    ->directory('bukti-pembayaran')
                    ->image()
                    ->label('Bukti Pembayaran'),
                
                Textarea::make('catatan_donatur')
                    ->label('Catatan dari Donatur'),
                
                Toggle::make('atas_nama_hamba_allah')
                    ->label('Sembunyikan Nama Donatur (Hamba Allah)')
                    ->live()
                    ->helperText('Donasi akan ditampilkan sebagai "Hamba Allah" dalam laporan'),
                
                Select::make('status_konfirmasi')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                    ])
                    ->default('pending')
                    ->required(),
                
                Textarea::make('catatan_konfirmasi')
                    ->label('Catatan Konfirmasi Admin'),
                
                // Hidden field for transaction number - will be auto-generated
                Hidden::make('nomor_transaksi_unik')
                    ->default(fn () => 'TRX' . strtoupper(uniqid()))
                    ->dehydrated()
                    ->required()
                    ->unique(Donasi::class, 'nomor_transaksi_unik', ignoreRecord: true),
                
                // Hidden field for tracking who created the record
                Hidden::make('dicatat_oleh_user_id')
                    ->default(fn () => Auth::id())
                    ->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nomor_transaksi_unik')
            ->columns([
                Tables\Columns\TextColumn::make('nomor_transaksi_unik')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('jenisDonasi.nama')
                    ->label('Jenis Donasi')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah/Nilai')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->jenisDonasi?->apakah_barang) {
                            // Jika donasi barang, tampilkan perkiraan nilai barang
                            $nilai = $record->perkiraan_nilai_barang ?? 0;
                            return 'Rp ' . number_format($nilai, 0, ',', '.') . ' (Barang)';
                        } else {
                            // Jika donasi uang, tampilkan jumlah donasi
                            return 'Rp ' . number_format($state ?? 0, 0, ',', '.');
                        }
                    })
                    ->tooltip(function ($record): ?string {
                        if ($record->jenisDonasi?->apakah_barang) {
                            return 'Perkiraan nilai: ' . $record->deskripsi_barang;
                        }
                        return 'Donasi tunai';
                    }),
                
                Tables\Columns\TextColumn::make('tanggal_donasi')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tgl Donasi'),
                
                Tables\Columns\TextColumn::make('metodePembayaran.nama')
                    ->label('Metode Bayar'),
                
                Tables\Columns\TextColumn::make('status_konfirmasi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('atas_nama_hamba_allah')
                    ->boolean()
                    ->label('Anonim'),
            ])
            ->filters([
                Tables\Filters\Filter::make('tanggal_donasi')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
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
                            $indicators['dari_tanggal'] = 'Dari tanggal ' . Carbon::parse($data['dari_tanggal'])->format('d M Y');
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators['sampai_tanggal'] = 'Sampai tanggal ' . Carbon::parse($data['sampai_tanggal'])->format('d M Y');
                        }
                        return $indicators;
                    }),
                
                Tables\Filters\SelectFilter::make('jenis_donasi_id')
                    ->label('Jenis Donasi')
                    ->relationship('jenisDonasi', 'nama')
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('status_konfirmasi')
                    ->label('Status Konfirmasi')
                    ->options(['pending' => 'Pending', 'verified' => 'Terverifikasi', 'rejected' => 'Ditolak']),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_donasi', 'desc');
    }
    public function isReadOnly(): bool
{
    return false;
}

    
}




