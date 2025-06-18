<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Donasi;
use App\Models\Regency;
use App\Models\Village;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\District;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\JenisDonasi;
use Filament\Resources\Resource;
use App\Imports\SistemImportLengkap;
use App\Models\KategoriDanaNonHalal;
use App\Models\KategoriInfaqTerikat;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Imports\DonasiImporter;
use App\Services\InvoiceDeliveryService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use App\Filament\Resources\DonasiResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Tables\Actions\ImportAction; // Add this line
use App\Filament\Resources\DonasiResource\Widgets\DonasiStat;
use App\Models\Donatur; // Pastikan model Donatur diimpor jika digunakan di URL
use Filament\Tables\Actions\Action; // Changed from Filament\Pages\Actions\Action

class DonasiResource extends Resource
{
    protected static ?string $model = Donasi::class;
    
    // Konfigurasi navigasi
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Donasi';
    protected static ?string $modelLabel = 'Donasi';
    protected static ?string $pluralModelLabel = 'Donasi';
    protected static ?int $navigationSort = 2;
    
    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.groups.program');
    }

    /**
     * Definisi form untuk halaman create dan edit donasi
     */
    public static function form(Form $form): Form 
    {
        return $form->schema([
            // Bagian 1: Informasi Donatur dan Jenis Donasi
            Forms\Components\Section::make('Informasi Donatur')
                ->description('Data donatur dan jenis donasi')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    Select::make('donatur_id')
                        ->relationship('donatur', 'nama')
                        ->searchable()
                        ->preload()
                        ->required(fn (Get $get) => !$get('atas_nama_hamba_allah'))
                        ->visible(fn (Get $get) => !$get('atas_nama_hamba_allah'))
                        ->createOptionForm([
                            Forms\Components\Select::make('gender')
                                ->options([
                                    'male' => 'Laki-laki',
                                    'female' => 'Perempuan',
                                    'organization' => 'organisasi',
                                ])
                                ->required()
                                ->default('male')
                                ->label('Jenis Kelamin'),
                            Forms\Components\TextInput::make('nama')
                                ->required()
                                ->maxLength(255)
                                ->label('Nama'),
                            Forms\Components\TextInput::make('nomor_hp')
                                ->tel()
                                ->unique(Donatur::class, 'nomor_hp', ignoreRecord: true)
                                ->nullable()
                                ->prefix('62')
                                ->helperText('Masukkan nomor tanpa awalan 0, contoh: 81234567890')
                                ->label('Nomor HP'),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->unique(Donatur::class, 'email', ignoreRecord: true)
                                ->nullable()
                                ->label('Email'),
                            Forms\Components\Select::make('pekerjaan_id')
                                ->relationship('pekerjaan', 'nama')
                                ->searchable()
                                ->preload()
                                ->label('Pekerjaan'),
                            Forms\Components\Select::make('province_id')
                                ->relationship('province', 'name')
                                ->searchable()
                                ->preload()
                                ->label('Provinsi')
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('regency_id', null);
                                    $set('district_id', null);
                                    $set('village_id', null);
                                }),
                            Forms\Components\Select::make('regency_id')
                                ->label('Kota/Kabupaten')
                                ->options(function (Get $get) {
                                    $provinceId = $get('province_id');
                                    if (!$provinceId) {
                                        return [];
                                    }
                                    return Regency::where('province_id', $provinceId)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('district_id', null);
                                    $set('village_id', null);
                                }),
                            Forms\Components\Select::make('district_id')
                                ->label('Kecamatan')
                                ->options(function (Get $get) {
                                    $regencyId = $get('regency_id');
                                    if (!$regencyId) {
                                        return [];
                                    }
                                    return District::where('regency_id', $regencyId)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(fn (Set $set) => $set('village_id', null)),
                            Forms\Components\Select::make('village_id')
                                ->label('Desa/Kelurahan')
                                ->options(function (Get $get) {
                                    $districtId = $get('district_id');
                                    if (!$districtId) {
                                        return [];
                                    }
                                    return Village::where('district_id', $districtId)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->searchable(),
                            Forms\Components\Textarea::make('alamat_detail')
                                ->label('Detail Alamat')
                                ->placeholder('Jalan, Nomor Rumah, RT/RW, dll')
                                ->columnSpanFull(),
                        ])
                        ->label('Donatur'),
                    
                    Toggle::make('atas_nama_hamba_allah')
                        ->label('Sembunyikan Nama Donatur')
                        ->helperText('Donasi akan tercatat sebagai "Hamba Allah"')
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            if ($state) {
                                // When anonymous mode is enabled, clear the donor selection
                                $set('donatur_id', null);
                            }
                        }),
                    
                    // Conditional placeholder to show when anonymous mode is active
                    Forms\Components\Placeholder::make('anonymous_notice')
                        ->label('Mode Donasi Anonim')
                        ->content('✅ Donasi akan dicatat sebagai "Hamba Allah". Field donatur disembunyikan.')
                        ->visible(fn (Get $get) => $get('atas_nama_hamba_allah'))
                        ->extraAttributes(['class' => 'text-success-600 bg-success-50 p-3 rounded-lg border border-success-200']),
                    
                    Select::make('jenis_donasi_id')
                        ->relationship('jenisDonasi', 'nama', function ($query) {
                            return $query->where('aktif', true);
                        })
                        ->searchable() // Label changed to match DonaturResource
                        ->preload()// Placeholder changed to match DonaturResource
                        ->required()
                        ->label('Jenis Donasi')
                        ->reactive()
                        ->afterStateUpdated(function (callable $set) {
                            $set('keterangan_infak_khusus', null);
                            $set('deskripsi_barang', null);
                            $set('perkiraan_nilai_barang', null);
                        }),
                    
                    DatePicker::make('tanggal_donasi')
                        ->default(now())
                        ->required()
                        ->label('Tanggal Donasi')
                        ->maxDate(now())
                        ->displayFormat('d M Y'),
                ]),
            
            // Bagian 2: Detail Donasi (Jumlah/Barang)
            Forms\Components\Section::make('Detail Donasi')
                ->description('Informasi jumlah dan jenis donasi')
                ->icon('heroicon-o-currency-dollar')
                ->columns(2)
                ->schema([
                    TextInput::make('jumlah')
                        ->label('Jumlah Donasi (Uang)')
                        ->helperText('Masukkan jumlah donasi dalam Rupiah')
                        ->prefix('Rp')
                        // ->placeholder('100.000')
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
                        ->required(function (Get $get) {
                            $jenisDonasiId = $get('jenis_donasi_id');
                            if (!$jenisDonasiId) return true; // Jika jenis donasi belum dipilih, anggap wajib
                            $jenisDonasi = JenisDonasi::find($jenisDonasiId);
                            return !($jenisDonasi && $jenisDonasi->apakah_barang); // Wajib jika bukan barang
                        })
                        ->dehydrateStateUsing(fn ($state) => 
                            $state ? (float) str_replace(['.', ','], ['', '.'], $state) : null
                        )
                        ->formatStateUsing(fn ($state) => 
                            $state ? number_format($state, 0, ',', '.') : null
                        )
                        // Kondisi visible agar tidak muncul jika donasi barang
                        ->visible(function (Get $get) {
                            $jenisDonasiId = $get('jenis_donasi_id');
                            if (!$jenisDonasiId) return true; // Tampil jika jenis belum dipilih
                            $jenisDonasi = JenisDonasi::find($jenisDonasiId);
                            return !$jenisDonasi || !$jenisDonasi->apakah_barang; // Tampil jika bukan barang
                        }),
                    
                    Select::make('keterangan_infak_khusus')
                        ->label('Kategori Infaq Terikat/DSKL')
                        ->options(function () {
                            return KategoriInfaqTerikat::aktif()
                                ->urutan()
                                ->pluck('nama_kategori', 'nama_kategori');
                        })
                        ->searchable()
                        ->preload()
                        ->visible(function (Get $get) {
                            $jenisDonasiId = $get('jenis_donasi_id');
                            if (!$jenisDonasiId) return false;
                            $jenisDonasi = JenisDonasi::find($jenisDonasiId);
                            return $jenisDonasi && 
                                   $jenisDonasi->membutuhkan_keterangan_tambahan && 
                                   !$jenisDonasi->mengandung_dana_non_halal && 
                                   !$jenisDonasi->apakah_barang;
                        })
                        ->columnSpanFull(),
                    
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
                        })
                        ->columnSpanFull(),
                    
                    Textarea::make('deskripsi_barang')
                        ->label('Deskripsi Barang')
                        ->placeholder('Contoh: Beras 5kg kualitas premium, Pakaian layak pakai 10 pcs, dll')
                        ->visible(function (Get $get) {
                            $jenisDonasiId = $get('jenis_donasi_id');
                            if (!$jenisDonasiId) return false;
                            $jenisDonasi = JenisDonasi::find($jenisDonasiId);
                            return $jenisDonasi && $jenisDonasi->apakah_barang;
                        })
                        ->columnSpanFull(),
                    
                    TextInput::make('perkiraan_nilai_barang')
                        ->label('Perkiraan Nilai Barang')
                        ->helperText('Estimasi nilai barang dalam Rupiah')
                        ->prefix('Rp')
                        ->placeholder('70.000')
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
                        ->visible(function (Get $get) {
                            $jenisDonasiId = $get('jenis_donasi_id');
                            if (!$jenisDonasiId) return false;
                            $jenisDonasi = JenisDonasi::find($jenisDonasiId);
                            return $jenisDonasi && $jenisDonasi->apakah_barang;
                        })
                        ->dehydrateStateUsing(fn ($state) => 
                            $state ? (float) str_replace(['.', ','], ['', '.'], $state) : null
                        )
                         ->formatStateUsing(fn ($state) =>  // Menggunakan formatStateUsing untuk tampilan
                            $state ? number_format($state, 0, ',', '.') : null
                        ),
                ]),
            
            // Bagian 3: Metode Pembayaran dan Bukti
            Forms\Components\Section::make('Metode Pembayaran')
                ->description('Informasi cara pembayaran dan bukti')
                ->icon('heroicon-o-credit-card')
                ->columns(2)
                ->schema([
                    Select::make('metode_pembayaran_id')
                        ->relationship('metodePembayaran', 'nama', function ($query) {
                            return $query->where('aktif', true);
                        })
                        ->searchable()
                        ->preload()
                        ->label('Metode Pembayaran'),
                    
                    Select::make('fundraiser_id')
                        ->relationship('fundraiser', 'nama_fundraiser', function ($query) {
                            return $query->where('aktif', true);
                        })
                        ->searchable()
                        ->preload()
                        ->label('Fundraiser')
                        ->placeholder('Pilih jika donasi melalui fundraiser'),
                    
                    FileUpload::make('bukti_pembayaran')
                        ->directory('bukti-pembayaran')
                        ->image()
                        ->imageResizeMode('cover')
                        // ->imageCropAspectRatio('1:1') // Bisa diaktifkan jika perlu crop
                        ->imageResizeTargetWidth('500')
                        // ->imageResizeTargetHeight('500') // Biasanya width saja cukup, height akan menyesuaikan aspek rasio
                        ->label('Bukti Pembayaran')
                        ->helperText('Upload foto bukti transfer/pembayaran (opsional)')
                        ->columnSpanFull(),
                    
                    Textarea::make('catatan_donatur')
                        ->label('Catatan dari Donatur')
                        ->placeholder('Catatan atau pesan dari donatur (opsional)')
                        ->columnSpanFull(),
                ]),
            
            // Bagian 4: Status Konfirmasi (collapsible)
            Forms\Components\Section::make('Status Konfirmasi')
                ->description('Informasi verifikasi donasi')
                ->icon('heroicon-o-check-badge')
                ->schema([
                    Select::make('status_konfirmasi')
                        ->options([
                            'pending' => 'Pending',
                            'verified' => 'Terverifikasi',
                            'rejected' => 'Belum Terverifikasi',
                        ])
                        ->default('pending')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            if ($state !== 'pending') {
                                $set('dikofirmasi_oleh_user_id', Auth::id());
                                $set('dikonfirmasi_pada', now());
                            } else {
                                $set('dikofirmasi_oleh_user_id', null);
                                $set('dikonfirmasi_pada', null);
                            }
                        }),
                    
                    Textarea::make('catatan_konfirmasi')
                        ->label('Catatan Konfirmasi Admin')
                        ->placeholder('Catatan internal terkait verifikasi donasi')
                        ->columnSpanFull(),
                    
                    Forms\Components\Hidden::make('dikofirmasi_oleh_user_id')
                        ->dehydrated(),
                    
                    Forms\Components\Hidden::make('dikonfirmasi_pada')
                        ->dehydrated(),
                    
                    Placeholder::make('info_konfirmasi')
                        ->label('Informasi Konfirmasi')
                        ->content(function (?Donasi $record): ?string { // Type hint $record
                            if (!$record || !$record->dikonfirmasi_pada) {
                                return 'Donasi belum dikonfirmasi.';
                            }
                            
                            $konfirmasiOleh = $record->dikonfirmasiOleh?->name ?? 'Sistem';
                            $tanggal = Carbon::parse($record->dikonfirmasi_pada)->translatedFormat('d M Y H:i');
                            $status = match($record->status_konfirmasi) {
                                'verified' => 'Terverifikasi',
                                'rejected' => 'Ditolak',
                                default => ucfirst($record->status_konfirmasi)
                            };
                            
                            return "Status: {$status}<br>Dikonfirmasi oleh: {$konfirmasiOleh}<br>Pada: {$tanggal}";
                        })
                        ->hiddenOn('create'),
                ])
                ->collapsible(),
            
            // Bagian 5: Informasi Pencatatan (read-only)
            Placeholder::make('info_pencatatan') // Mengganti key agar unik dari field lain
                ->label('Informasi Pencatatan')
                ->content(function (?Donasi $record): string { // Type hint $record
                    $currentUser = Auth::user();
                    if (!$record || $record->wasRecentlyCreated || !$record->dicatatOleh) { // Cek jika record baru atau belum ada pencatat
                        return 'Akan dicatat oleh: ' . ($currentUser?->name ?? 'Sistem');
                    }
                    
                    $dicatatOleh = $record->dicatatOleh?->name ?? 'Sistem';
                    $tanggal = $record->created_at?->translatedFormat('d M Y H:i') ?? '-';
                    
                    return "Dicatat oleh: {$dicatatOleh}<br>Pada: {$tanggal}";
                })
                ->hiddenOn('edit'), // Tampil saat create dan view, sembunyi saat edit
            
            // Hidden fields for system tracking
            TextInput::make('nomor_transaksi_unik') // Dibuat visible untuk debug, bisa di-hidden lagi
                ->default(fn () => 'TRX' . strtoupper(uniqid()))
                ->dehydrated()
                ->required()
                ->unique(Donasi::class, 'nomor_transaksi_unik', ignoreRecord: true)
                ->readOnlyOn('edit'), // Hanya bisa diisi saat create
            
            Forms\Components\Hidden::make('dicatat_oleh_user_id')
                ->default(fn () => Auth::id())
                ->dehydrated(),
        ]);
    }

    /**
     * Definisi tabel untuk halaman list donasi
     */
    public static function table(Table $table): Table 
    {
        return $table
            ->columns([
                TextColumn::make('nomor_transaksi_unik')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->limit(12)
                    ->tooltip(fn (Donasi $record): ?string => $record->nomor_transaksi_unik),
                
                TextColumn::make('donatur.nama')
                    ->label('Donatur')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->getStateUsing(function (Donasi $record) {
                        if ($record->atas_nama_hamba_allah) {
                            return 'Hamba Allah';
                        }
                        if ($record->donatur) {
                            // Menambahkan prefix Bpk./Ibu berdasarkan jenis kelamin
                            $prefix = $record->donatur->gender === 'male' ? 'Bpk.' : 'Ibu';
                            return $prefix . ' ' . $record->donatur->nama;
                        }
                        return '-';
                    })
                    ->tooltip(function (Donasi $record): ?string {
                        if ($record->atas_nama_hamba_allah) {
                            return 'Donasi Anonim - Hamba Allah';
                        }
                        if ($record->donatur) {
                            $prefix = $record->donatur->gender === 'male' ? 'Bapak' : 'Ibu';
                            return $prefix . ' ' . $record->donatur->nama;
                        }
                        return null;
                    })
                    ->url(fn (Donasi $record) => $record->donatur && !$record->atas_nama_hamba_allah ? 
                        DonaturResource::getUrl('view', ['record' => $record->donatur_id]) : null)
                    ->color(fn (Donasi $record) => $record->atas_nama_hamba_allah ? 'gray' : 'primary'),
                
                TextColumn::make('jenisDonasi.nama')
                    ->label('Jenis Donasi')
                    ->searchable()
                    ->sortable()
                    ->limit(15)
                    ->tooltip(fn (Donasi $record): ?string => $record->jenisDonasi?->nama),
                
                TextColumn::make('jumlah')
                    ->label('Jumlah/Nilai')
                    ->sortable()
                    ->formatStateUsing(function ($state, Donasi $record) {
                        if ($record->jenisDonasi?->apakah_barang) {
                            // Jika donasi barang, tampilkan perkiraan nilai barang
                            $nilai = $record->perkiraan_nilai_barang ?? 0;
                            return 'Rp ' . number_format($nilai, 0, ',', '.') . ' (Barang)';
                        } else {
                            // Jika donasi uang, tampilkan jumlah donasi
                            return 'Rp ' . number_format($state ?? 0, 0, ',', '.');
                        }
                    })
                    ->tooltip(function (Donasi $record): ?string {
                        if ($record->jenisDonasi?->apakah_barang) {
                            return 'Perkiraan nilai: ' . $record->deskripsi_barang;
                        }
                        return 'Donasi tunai';
                    }),
                
                TextColumn::make('tanggal_donasi')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tgl Donasi'),
                
                TextColumn::make('status_konfirmasi')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Belum Terverifikasi',
                        default => ucfirst($state),
                    }),
                
                TextColumn::make('metodePembayaran.nama')
                    ->label('Metode Bayar')
                    ->sortable()
                    ->limit(12)
                    ->tooltip(fn (Donasi $record): ?string => $record->metodePembayaran?->nama)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                IconColumn::make('atas_nama_hamba_allah')
                    ->boolean()
                    ->label('Anonim')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('fundraiser.nama_fundraiser')
                    ->label('Fundraiser')
                    ->limit(15)
                    ->tooltip(fn (Donasi $record): ?string => $record->fundraiser?->nama_fundraiser)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('dicatatOleh.name') // Intelephense mungkin masih error di sini, tapi runtime harusnya OK
                    ->label('Dicatat Oleh')
                    ->sortable()
                    ->limit(15)
                    ->tooltip(fn (Donasi $record): ?string => $record->dicatatOleh?->name)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Tgl Input')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('catatan_donatur')
                    ->label('Catatan Donatur')
                    ->limit(30)
                    ->tooltip(fn (Donasi $record): ?string => $record->catatan_donatur)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('keterangan_infak_khusus')
                    ->label('Keterangan Infak')
                    ->limit(25)
                    ->tooltip(fn (Donasi $record): ?string => $record->keterangan_infak_khusus)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('kategoriDanaNonHalal.nama')
                    ->label('Kategori Dana Non Halal')
                    ->limit(25)
                    ->tooltip(fn (Donasi $record): ?string => $record->kategoriDanaNonHalal?->nama)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('deskripsi_barang')
                    ->label('Deskripsi Barang')
                    ->limit(25)
                    ->tooltip(fn (Donasi $record): ?string => $record->deskripsi_barang)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(filters: [
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
                        'rejected' => 'Ditolak',    ]),
            
                SelectFilter::make('fundraiser_id')
                        ->relationship('fundraiser', 'nama_fundraiser')
                        ->searchable()
                        ->preload()
                        ->label('Fundraiser'),
                
                SelectFilter::make('donatur.gender')
                    ->label('Jenis Kelamin Donatur')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $gender): Builder => $query->whereHas('donatur', 
                                fn (Builder $query) => $query->where('gender', $gender)
                            )
                        );
                    }),
            
                Tables\Filters\TernaryFilter::make('atas_nama_hamba_allah')
                    ->label('Donasi Anonim')
                    ->placeholder('Semua donasi')
                    ->trueLabel('Hanya donasi anonim')
                    ->falseLabel('Hanya donasi tidak anonim'),
                
                Tables\Filters\Filter::make('jumlah_donasi')
                    ->label('Range Nilai Donasi')
                    ->form([
                        Forms\Components\TextInput::make('min_donasi')
                            ->label('Minimal (Rp)')
                            ->placeholder('50.000')
                            ->helperText('Nilai minimal donasi (uang + nilai barang)')
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
                            ]),
                        Forms\Components\TextInput::make('max_donasi')
                            ->label('Maksimal (Rp)')
                            ->placeholder('5.000.000')
                            ->helperText('Nilai maksimal donasi (uang + nilai barang)')
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
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_donasi'] ?? null,
                                function (Builder $query, $min) {
                                    // Clean the value here in the query function
                                    $cleanMin = preg_replace('/[^0-9]/', '', $min);
                                    $cleanMin = $cleanMin ? (int) $cleanMin : 0;
                                    return $query->whereRaw('(jumlah + IFNULL(perkiraan_nilai_barang, 0)) >= ?', [$cleanMin]);
                                }
                            )
                            ->when(
                                $data['max_donasi'] ?? null,
                                function (Builder $query, $max) {
                                    // Clean the value here in the query function
                                    $cleanMax = preg_replace('/[^0-9]/', '', $max);
                                    $cleanMax = $cleanMax ? (int) $cleanMax : 999999999;
                                    return $query->whereRaw('(jumlah + IFNULL(perkiraan_nilai_barang, 0)) <= ?', [$cleanMax]);
                                }
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min_donasi'] ?? null) {
                            // Clean the value for display
                            $cleanMin = preg_replace('/[^0-9]/', '', $data['min_donasi']);
                            $cleanMin = $cleanMin ? (int) $cleanMin : 0;
                            $indicators['min_donasi'] = 'Min: Rp ' . number_format($cleanMin, 0, ',', '.');
                        }
                        if ($data['max_donasi'] ?? null) {
                            // Clean the value for display
                            $cleanMax = preg_replace('/[^0-9]/', '', $data['max_donasi']);
                            $cleanMax = $cleanMax ? (int) $cleanMax : 0;
                            $indicators['max_donasi'] = 'Max: Rp ' . number_format($cleanMax, 0, ',', '.');
                        }
                        return $indicators;
                    })
            ], layout: FiltersLayout::Modal)
            ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                // Action untuk Generate dan Kirim Invoice
                Tables\Actions\Action::make('generateInvoice')
                    ->label('Kirim Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(fn (Donasi $record) => $record->status_konfirmasi === 'verified')
                    ->form([
                        Forms\Components\Select::make('delivery_method')
                            ->label('Metode Pengiriman')
                            ->options(function (Donasi $record) {
                                $service = app(InvoiceDeliveryService::class);
                                return $service->formatDeliveryMethodsForUI($record->donatur);
                            })
                            ->default(function (Donasi $record) {
                                $service = app(InvoiceDeliveryService::class);
                                return $service->determineDeliveryMethod($record->donatur, $record);
                            })
                            ->required(),
                            
                        Forms\Components\Textarea::make('delivery_notes')
                            ->label('Catatan Pengiriman')
                            ->placeholder('Catatan tambahan untuk pengiriman invoice...')
                            ->columnSpanFull(),
                    ])
                    ->action(function (Donasi $record, array $data) {
                        $service = app(InvoiceDeliveryService::class);
                        $deliveryMethod = $data['delivery_method'];
                        $notes = $data['delivery_notes'] ?? '';
                        
                        // Process complete invoice delivery
                        $result = $service->processInvoiceDelivery($record, $deliveryMethod, $notes);
                        
                        if ($result['success']) {
                            $notification = Notification::make()
                                ->title('Invoice Berhasil Diproses')
                                ->body($result['message'])
                                ->success()
                                ->duration(5000);
                                
                            // Add download action if applicable
                            if (isset($result['download_url']) && $deliveryMethod === 'download') {
                                $notification->actions([
                                    \Filament\Notifications\Actions\Action::make('download')
                                        ->label('Download PDF')
                                        ->url($result['download_url'])
                                        ->openUrlInNewTab()
                                        ->button(),
                                ]);
                            }
                            
                            $notification->send();
                        } else {
                            Notification::make()
                                ->title('Gagal Memproses Invoice')
                                ->body($result['message'] ?? 'Terjadi kesalahan saat memproses invoice.')
                                ->danger()
                                ->duration(8000)
                                ->send();
                        }
                    })
                    ->modalHeading('Kirim Invoice Donasi')
                    ->modalSubheading(fn (Donasi $record) => "Donatur: " . ($record->atas_nama_hamba_allah ? 'Hamba Allah' : $record->donatur?->nama) . " | Jumlah: Rp " . number_format($record->jumlah, 0, ',', '.'))
                    ->modalWidth('md'),
                
                // Action untuk Download PDF Invoice
                Tables\Actions\Action::make('downloadPDF')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->visible(fn (Donasi $record) => $record->status_konfirmasi === 'verified')
                    ->action(function (Donasi $record) {
                        try {
                            $pdfService = app(\App\Services\InvoicePdfService::class);
                            return $pdfService->downloadInvoicePDF($record);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal Download PDF')
                                ->body('Terjadi kesalahan saat generate PDF: ' . $e->getMessage())
                                ->danger()
                                ->send();
                            return null;
                        }
                    })
                    ->requiresConfirmation(false),
                
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make(),
                    // ... any other existing bulk actions ...
                ]),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(DonasiImporter::class),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Definisi relasi yang tersedia untuk resource ini
     */
  public static function getRelations(): array
{
    return [
    ];
}

    /**
     * Definisi halaman yang tersedia untuk resource ini
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonasis::route('/'),
            'create' => Pages\CreateDonasi::route('/create'),
            'view' => Pages\ViewDonasi::route('/{record}'),
            'edit' => Pages\EditDonasi::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            DonasiResource\Widgets\DonasiStat::class,
        ];
    }

}











