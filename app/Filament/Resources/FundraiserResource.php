<?php

namespace App\Filament\Resources;

use App\Filament\Imports\FundraiserImporter;
use App\Filament\Resources\FundraiserResource\Pages;
use App\Models\Fundraiser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ImportAction;

class FundraiserResource extends Resource
{
    protected static ?string $model = Fundraiser::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Fundraiser';
    
    protected static ?string $pluralModelLabel = 'Fundraiser';
    
    protected static ?string $modelLabel = 'Fundraiser';
    
    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.groups.program');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_fundraiser')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Fundraiser'),
                    
                Forms\Components\TextInput::make('nomor_identitas')
                    ->maxLength(50)
                    ->label('Nomor Identitas (KTP/SIM)'),
                    
                Forms\Components\TextInput::make('nomor_hp')
                    ->tel()
                    ->maxLength(20)
                    ->label('Nomor HP'),
                    
                Forms\Components\Textarea::make('alamat')
                    ->columnSpanFull()
                    ->label('Alamat'),
                    
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('User Akun (Opsional)')
                    ->placeholder('Pilih user jika fundraiser memiliki akun'),
                    
                Forms\Components\Toggle::make('aktif')
                    ->default(true)
                    ->label('Status Aktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_fundraiser')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Fundraiser'),
                    
                Tables\Columns\TextColumn::make('nomor_identitas')
                    ->searchable()
                    ->label('Nomor Identitas'),
                    
                Tables\Columns\TextColumn::make('nomor_hp')
                    ->searchable()
                    ->label('Nomor HP'),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('User Akun'),
                    
                Tables\Columns\IconColumn::make('aktif')
                    ->boolean()
                    ->sortable()
                    ->label('Status Aktif'),
                    
                Tables\Columns\TextColumn::make('donatur_count')
                    ->getStateUsing(function ($record) {
                        return $record->donasis()->distinct('donatur_id')->count('donatur_id');
                    })
                    ->label('Jumlah Donatur')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->label('Tgl Daftar')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('aktif')
                    ->label('Status Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
                    
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->label('User Akun'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(FundraiserImporter::class),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // Remove the DonasisRelationManager reference
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFundraisers::route('/'),
            'create' => Pages\CreateFundraiser::route('/create'),
            'edit' => Pages\EditFundraiser::route('/{record}/edit'),
            // Remove the ViewFundraiser reference
        ];
    }
}

