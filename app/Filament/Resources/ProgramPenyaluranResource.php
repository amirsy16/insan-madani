<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Asnaf;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\JenisDonasi;
use App\Models\BidangProgram;
use App\Services\DanaService;
use Filament\Resources\Resource;
use App\Models\ProgramPenyaluran;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use App\Models\SumberDanaPenyaluran;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use App\Filament\Imports\ProgramPenyaluranImporter;
use App\Filament\Resources\ProgramPenyaluranResource\Pages;

class ProgramPenyaluranResource extends Resource
{
    protected static ?string $model = ProgramPenyaluran::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';
    protected static ?string $navigationGroup = 'Program';
    protected static ?string $navigationLabel = 'Penyaluran Dana';
    protected static ?string $modelLabel = 'Program Penyaluran Dana';
    protected static ?int $navigationSort = 2;
    
    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.groups.program');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Informasi Program')
                        ->schema([
                            Forms\Components\TextInput::make('nama_program')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Forms\Components\DatePicker::make('tanggal_penyaluran')
                                ->required()
                                ->default(now()),
                            Forms\Components\Textarea::make('lokasi_penyaluran')
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\RichEditor::make('keterangan')
                                ->columnSpanFull(),
                        ])->columns(2),

                    Wizard\Step::make('Sumber & Alokasi Dana')
                        ->schema([
                            Forms\Components\Select::make('sumber_dana_penyaluran_id')
                                ->relationship('sumberDanaPenyaluran', 'nama_sumber_dana')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    // Reset jumlah dana jika sumber berubah
                                    $set('jumlah_dana', null);
                                    
                                    // Cek apakah ini dana zakat, jika ya, tampilkan asnaf
                                    $sumberDana = SumberDanaPenyaluran::find($state);
                                    if ($sumberDana && strtolower($sumberDana->nama_sumber_dana) === 'dana zakat') {
                                        $set('show_asnaf', true);
                                    } else {
                                        $set('show_asnaf', false);
                                        $set('asnaf_id', null);
                                    }
                                })
                                ->required(),
                            
                            // Menampilkan Saldo Tersedia
                            Placeholder::make('saldo_tersedia')
                                ->label('Saldo Tersedia')
                                ->content(function (Get $get, DanaService $danaService): string {
                                    $sumberDanaId = $get('sumber_dana_penyaluran_id');
                                    if (!$sumberDanaId) {
                                        return 'Pilih sumber dana untuk melihat saldo.';
                                    }
                                    $saldo = $danaService->getSaldoTersedia($sumberDanaId);
                                    return 'Rp ' . number_format($saldo, 0, ',', '.');
                                }),
                                
                            Forms\Components\TextInput::make('jumlah_dana')
                                ->required()
                                ->numeric()
                                ->prefix('Rp')
                                ->live(onBlur: true)
                                ->rule(function (Get $get, DanaService $danaService) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $danaService) {
                                        $sumberDanaId = $get('sumber_dana_penyaluran_id');
                                        if (!$sumberDanaId) {
                                            return;
                                        }
                                        $saldoTersedia = $danaService->getSaldoTersedia($sumberDanaId);
                                        if (floatval($value) > $saldoTersedia) {
                                            $fail("Jumlah penyaluran tidak boleh melebihi saldo tersedia: Rp " . number_format($saldoTersedia, 0, ',', '.'));
                                        }
                                    };
                                }),
                                
                            // Hidden field untuk menentukan apakah asnaf ditampilkan
                            Forms\Components\Hidden::make('show_asnaf')
                                ->default(false),
                                
                            // Asnaf (hanya untuk dana zakat)
                            Forms\Components\Select::make('asnaf_id')
                                ->label('Asnaf (Penerima Zakat)')
                                ->relationship('asnaf', 'nama_asnaf')
                                ->searchable()
                                ->preload()
                                ->required(fn (Get $get): bool => $get('show_asnaf') === true)
                                ->visible(fn (Get $get): bool => $get('show_asnaf') === true)
                                ->helperText('Pilih kategori asnaf penerima zakat'),
                                
                            // Bidang Program (untuk semua jenis dana)
                            Forms\Components\Select::make('bidang_program_id')
                                ->label('Bidang Program')
                                ->relationship('bidangProgram', 'nama_bidang')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText('Pilih bidang program untuk penyaluran ini'),
                                
                            // Jenis Donasi Spesifik (opsional)
                            Forms\Components\Select::make('jenis_donasi_id')
                                ->label('Jenis Donasi Spesifik (Opsional)')
                                ->relationship('jenisDonasi', 'nama', function (Builder $query, Get $get) {
                                    $sumberDanaId = $get('sumber_dana_penyaluran_id');
                                    if ($sumberDanaId) {
                                        $query->where('sumber_dana_penyaluran_id', $sumberDanaId);
                                    }
                                })
                                ->searchable()
                                ->preload()
                                ->helperText('Opsional: Pilih jenis donasi spesifik jika penyaluran ini menggunakan dana dari jenis donasi tertentu'),
                        ]),

                    Wizard\Step::make('Detail Penerima')
                        ->schema([
                            Forms\Components\Radio::make('tipe_penerima')
                                ->label('Tipe Penerima Manfaat')
                                ->options([
                                    'individu' => 'Individu',
                                    'lembaga' => 'Lembaga/Kelompok',
                                ])
                                ->default('individu')
                                ->live()
                                ->required(),
                                
                            Forms\Components\TextInput::make('penerima_manfaat_individu')
                                ->label('Nama Penerima (Individu)')
                                ->maxLength(255)
                                ->required(fn (Get $get): bool => $get('tipe_penerima') === 'individu')
                                ->visible(fn (Get $get): bool => $get('tipe_penerima') === 'individu'),
                                
                            Forms\Components\TextInput::make('penerima_manfaat_lembaga')
                                ->label('Nama Lembaga/Kelompok')
                                ->maxLength(255)
                                ->required(fn (Get $get): bool => $get('tipe_penerima') === 'lembaga')
                                ->visible(fn (Get $get): bool => $get('tipe_penerima') === 'lembaga'),
                                
                            Forms\Components\TextInput::make('jumlah_penerima_manfaat')
                                ->label('Jumlah Penerima Manfaat')
                                ->numeric()
                                ->default(1)
                                ->helperText('Jumlah orang/KK yang menerima manfaat'),
                                
                            Forms\Components\FileUpload::make('bukti_penyaluran')
                                ->label('Bukti Penyaluran')
                                ->directory('bukti-penyaluran')
                                ->image()
                                ->imageEditor()
                                ->columnSpanFull(),
                        ])->columns(2),
                ])
                ->columnSpanFull()
                ->nextAction(
                    fn (Forms\Components\Actions\Action $action) => $action->label('Lanjut')
                )
                ->previousAction(
                    fn (Forms\Components\Actions\Action $action) => $action->label('Kembali')
                ),

                Forms\Components\Hidden::make('dicatat_oleh_id')
                    ->default(Auth::id()),
                    
                Forms\Components\Hidden::make('kode_program_penyaluran')
                    ->default(function () {
                        $prefix = 'PYL';
                        $date = now()->format('Ymd');
                        $lastRecord = ProgramPenyaluran::latest('id')->first();
                        $lastId = $lastRecord ? $lastRecord->id + 1 : 1;
                        return $prefix . $date . str_pad($lastId, 4, '0', STR_PAD_LEFT);
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_program_penyaluran')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_program')
                    ->label('Nama Program')
                    ->searchable()
                    ->wrap()
                    ->limit(30),
                Tables\Columns\TextColumn::make('tanggal_penyaluran')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jumlah_dana')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sumberDanaPenyaluran.nama_sumber_dana')
                    ->label('Sumber Dana')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dana Zakat' => 'success',
                        'Dana Infak/Sedekah' => 'warning',
                        'Dana DSKL' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('asnaf.nama_asnaf')
                    ->label('Asnaf')
                    ->badge()
                    ->color('success')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('bidangProgram.nama_bidang')
                    ->label('Bidang Program')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('penerimaManfaat')
                    ->label('Penerima Manfaat')
                    ->getStateUsing(fn (ProgramPenyaluran $record) => $record->getNamaPenerimaManfaatAttribute())
                    ->searchable(['penerima_manfaat_individu', 'penerima_manfaat_lembaga']),
                Tables\Columns\TextColumn::make('jumlah_penerima_manfaat')
                    ->label('Jumlah Penerima')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('dicatatOleh.name')
                    ->label('Dicatat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sumber_dana_penyaluran_id')
                    ->label('Sumber Dana')
                    ->relationship('sumberDanaPenyaluran', 'nama_sumber_dana'),
                Tables\Filters\SelectFilter::make('asnaf_id')
                    ->label('Asnaf')
                    ->relationship('asnaf', 'nama_asnaf'),
                Tables\Filters\SelectFilter::make('bidang_program_id')
                    ->label('Bidang Program')
                    ->relationship('bidangProgram', 'nama_bidang'),
                Tables\Filters\Filter::make('tanggal_penyaluran')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_penyaluran', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_penyaluran', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(ProgramPenyaluranImporter::class),
            ])
            ->defaultSort('tanggal_penyaluran', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProgramPenyalurans::route('/'),
            'create' => Pages\CreateProgramPenyaluran::route('/create'),
            'edit' => Pages\EditProgramPenyaluran::route('/{record}/edit'),
            'view' => Pages\ViewProgramPenyaluran::route('/{record}'),
        ];
    }
}



