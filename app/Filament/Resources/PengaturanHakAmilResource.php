<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengaturanHakAmilResource\Pages;
use App\Models\SumberDanaPenyaluran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PengaturanHakAmilResource extends Resource
{
    protected static ?string $model = SumberDanaPenyaluran::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Administrator';
    protected static ?string $modelLabel = 'Pengaturan Hak Amil';
    protected static ?string $pluralModelLabel = 'Pengaturan Hak Amil';
    protected static ?int $navigationSort = 1;
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pengaturan Hak Amil per Sumber Dana')
                    ->description('Setiap sumber dana dapat memiliki persentase hak amil yang berbeda')
                    ->schema([
                        Forms\Components\TextInput::make('nama_sumber_dana')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama Sumber Dana')
                            ->disabled()
                            ->helperText('Nama sumber dana penyaluran'),
                            
                        Forms\Components\TextInput::make('persentase_hak_amil')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.1)
                            ->suffix('%')
                            ->default(12)
                            ->label('Persentase Hak Amil')
                            ->helperText('Masukkan persentase hak amil (0-100%). Default: 12%'),
                            
                        Forms\Components\Textarea::make('deskripsi')
                            ->maxLength(500)
                            ->label('Deskripsi')
                            ->disabled()
                            ->placeholder('Deskripsi sumber dana'),
                            
                        Forms\Components\Toggle::make('aktif')
                            ->default(true)
                            ->label('Status Aktif')
                            ->disabled(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_sumber_dana')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Sumber Dana'),
                    
                Tables\Columns\TextColumn::make('persentase_hak_amil')
                    ->suffix('%')
                    ->sortable()
                    ->label('Persentase Hak Amil')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state, 1))
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                    
                Tables\Columns\TextColumn::make('deskripsi')
                    ->limit(50)
                    ->label('Deskripsi')
                    ->placeholder('-')
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('aktif')
                    ->boolean()
                    ->sortable()
                    ->label('Status'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->label('Dibuat')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('aktif')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tidak ada bulk actions untuk menghindari penghapusan sumber dana
            ])
            ->defaultSort('nama_sumber_dana', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPengaturanHakAmils::route('/'),
            'create' => Pages\CreatePengaturanHakAmil::route('/create'),
            'edit' => Pages\EditPengaturanHakAmil::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('aktif', true)->count();
    }
}
