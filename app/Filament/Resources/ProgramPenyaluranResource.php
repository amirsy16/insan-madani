<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramPenyaluranResource\Pages;
use App\Filament\Resources\ProgramPenyaluranResource\RelationManagers;
use App\Models\ProgramPenyaluran;
use App\Models\Asnaf;
use App\Models\BidangProgram;
use App\Models\SumberDanaPenyaluran;
use App\Models\JenisDonasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
// Import yang ditambahkan/diperbaiki
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use App\Services\DanaService; // <-- TAMBAHKAN IMPORT INI
use Filament\Forms\Components\Placeholder; 

class ProgramPenyaluranResource extends Resource
{
    protected static ?string $model = ProgramPenyaluran::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';
    protected static ?string $navigationGroup = 'Program';
    protected static ?string $navigationLabel = 'Penyaluran Dana';
    protected static ?string $modelLabel = 'Program Penyaluran Dana';
    protected static ?int $navigationSort = 2;


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
                            Forms\Components\TextInput::make('jumlah_dana')
                                ->required()
                                ->numeric()
                                ->prefix('Rp'),
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
                                ->live() // live() sangat penting untuk langkah ini
                                ->afterStateUpdated(fn (Set $set) => $set('jumlah_dana', null)) // Reset jumlah dana jika sumber berubah
                                ->required(),
                            
                            // Menampilkan Saldo Tersedia (Placeholder)
                            Placeholder::make('saldo_tersedia')
                                ->label('Saldo Tersedia')
                                ->content(function (Get $get, DanaService $danaService): string {
                                    $sumberDanaId = $get('sumber_dana_penyaluran_id');
                                    if (!$sumberDanaId) {
                                        return 'Pilih sumber dana untuk melihat saldo.';
                                    }
                                    $saldo = $danaService->getSaldoTersedia($sumberDanaId);
                                    return 'Rp ' . number_format($saldo, 0, ',', '.');
                                })->columnSpanFull(),

                            // ... (Select untuk Asnaf dan Bidang Program yang sudah ada)
                            
                        ]),

                  Wizard\Step::make('Detail Penyaluran') // Mengganti nama langkah agar lebih sesuai
                        ->schema([
                             Forms\Components\TextInput::make('jumlah_dana')
                                ->required()
                                ->numeric()
                                ->prefix('Rp')
                                ->live(onBlur: true) // Untuk memicu validasi saat user keluar dari field
                                ->rule(function (Get $get, DanaService $danaService) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $danaService) {
                                        $sumberDanaId = $get('sumber_dana_penyaluran_id');
                                        if (!$sumberDanaId) {
                                            // Seharusnya tidak terjadi karena field sumber dana required
                                            return;
                                        }
                                        $saldoTersedia = $danaService->getSaldoTersedia($sumberDanaId);
                                        if (floatval($value) > $saldoTersedia) {
                                            $fail("Jumlah penyaluran tidak boleh melebihi saldo tersedia: Rp " . number_format($saldoTersedia, 0, ',', '.'));
                                        }
                                    };
                                }),

                            // ... (sisa field dari langkah 3 sebelumnya: penerima manfaat, bukti, dll.)
                            Forms\Components\TextInput::make('penerima_manfaat_individu')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('penerima_manfaat_lembaga')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('jumlah_penerima_manfaat')
                                ->numeric()
                                ->default(1),
                            Forms\Components\FileUpload::make('bukti_penyaluran')
                                ->directory('bukti-penyaluran')
                                ->image()
                                ->imageEditor(),
                        ])->columns(2),

                ])->columnSpanFull()
                  // ... (nextAction dan previousAction yang sudah ada)
                  ,

                Forms\Components\Hidden::make('dicatat_oleh_id')
                    ->default(Auth::id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_program')
                    ->label('Nama Program')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('tanggal_penyaluran')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jumlah_dana')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sumberDanaPenyaluran.nama_sumber_dana')
                    ->label('Sumber Dana')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('alokasi')
                    ->label('Alokasi')
                    ->html()
                    ->getStateUsing(function (ProgramPenyaluran $record) {
                        if ($record->asnaf) {
                            return '<strong>Asnaf:</strong> ' . $record->asnaf->nama_asnaf;
                        }
                        if ($record->bidangProgram) {
                            return '<strong>Bidang:</strong> ' . $record->bidangProgram->nama_bidang;
                        }
                        return '-';
                    }),
                Tables\Columns\TextColumn::make('penerimaManfaat')
                    ->label('Penerima Manfaat')
                    ->getStateUsing(fn (ProgramPenyaluran $record) => $record->namaPenerimaManfaat),
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
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_penyaluran', '>=', $date)
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_penyaluran', '<=', $date)
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'view' => Pages\ViewProgramPenyaluran::route('/{record}'),
            'edit' => Pages\EditProgramPenyaluran::route('/{record}/edit'),
        ];
    }
}


