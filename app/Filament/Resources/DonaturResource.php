<?php

namespace App\Filament\Resources;

use App\Filament\Imports\DonaturImporter;
use App\Filament\Resources\DonaturResource\Pages;
use App\Filament\Resources\DonaturResource\RelationManagers\DonasisRelationManager;
use App\Models\Donatur;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Tables\Filters\TernaryFilter;
use Carbon\Carbon;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class DonaturResource extends Resource
{
    protected static ?string $model = Donatur::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Donatur';
    
    protected static ?string $pluralModelLabel = 'Donatur';
    
    protected static ?string $modelLabel = 'Donatur';
    
    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.groups.program');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dasar')
                    ->schema([
                        Forms\Components\TextInput::make('kode_donatur')
                            ->label('ID Donatur')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Kode akan digenerate otomatis saat donatur disimpan')
                            ->hiddenOn('create'),
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
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nomor_hp')
                            ->tel()
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->prefix('62')
                            ->helperText('Masukkan nomor tanpa awalan 0, contoh: 81234567890'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->nullable(),
                        Forms\Components\Select::make('pekerjaan_id')
                            ->relationship('pekerjaan', 'nama')
                            ->searchable()
                            ->preload()
                            ->label('Pekerjaan'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Alamat Lengkap')
                    ->schema([
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
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_donatur')
                    ->label('ID Donatur')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('nama', 'like', "%{$search}%");
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('nama', $direction);
                    })
                    ->label('Nama'),
                Tables\Columns\TextColumn::make('nomor_hp')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ? '62' . ltrim($state, '0') : null),
                Tables\Columns\TextColumn::make('donasis_count')
                    ->counts('donasis')
                    ->label('Jml Transaksi')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->label('Tgl Daftar')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                        'organization' => 'organisasi',
                    ])
                    ->label('Jenis Kelamin'),
                
                Filter::make('created_at')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
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
                    })
                    ->label('Tanggal Pendaftaran'),
                
                SelectFilter::make('pekerjaan_id')
                    ->relationship('pekerjaan', 'nama')
                    ->searchable()
                    ->preload()
                    ->label('Pekerjaan'),
            ])
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make() // Dari pxlrbt/filament-excel
                ]),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(DonaturImporter::class)
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            DonasisRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonaturs::route('/'),
            'create' => Pages\CreateDonatur::route('/create'),
            'view' => Pages\ViewDonatur::route('/{record}'),
            'edit' => Pages\EditDonatur::route('/{record}/edit'),
        ];
    }

       public static function getWidgets(): array
    {
        return [
            DonaturResource\Widgets\DonaturStats::class,
        ];
    }
}


