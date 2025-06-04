<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceDonasiResource\Pages;
use App\Models\InvoiceDonasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class InvoiceDonasiResource extends Resource
{
    protected static ?string $model = InvoiceDonasi::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Invoice Donasi';
    
    protected static ?string $modelLabel = 'Invoice Donasi';
    
    protected static ?string $pluralModelLabel = 'Invoice Donasi';
    
    protected static ?string $navigationGroup = 'Manajemen Donasi';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Invoice')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_invoice')
                            ->label('Nomor Invoice')
                            ->required()
                            ->maxLength(50)
                            ->disabled(),
                            
                        Forms\Components\Select::make('donasi_id')
                            ->label('Donasi')
                            ->relationship('donasi', 'nomor_transaksi_unik')
                            ->required()
                            ->disabled(),
                            
                        Forms\Components\Select::make('delivery_method')
                            ->label('Metode Pengiriman')
                            ->options([
                                'email' => 'Email',
                                // 'whatsapp' => 'WhatsApp', // Akan diaktifkan setelah membeli WhatsApp blast
                                'sms' => 'SMS',
                                'download' => 'Download Manual',
                                'print' => 'Print untuk Pickup',
                            ])
                            ->required()
                            ->disabled(),
                            
                        Forms\Components\Select::make('delivery_status')
                            ->label('Status Pengiriman')
                            ->options([
                                'pending' => 'Menunggu',
                                'sent' => 'Terkirim',
                                'delivered' => 'Tersampaikan',
                                'failed' => 'Gagal',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Detail Pengiriman')
                    ->schema([
                        Forms\Components\TextInput::make('sent_to_email')
                            ->label('Email Tujuan')
                            ->email()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('sent_to_phone')
                            ->label('Nomor HP Tujuan')
                            ->maxLength(20),
                            
                        Forms\Components\Textarea::make('delivery_notes')
                            ->label('Catatan Pengiriman')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('error_message')
                            ->label('Pesan Error')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record?->delivery_status === 'failed'),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Informasi File')
                    ->schema([
                        Forms\Components\TextInput::make('pdf_file_path')
                            ->label('Path File PDF')
                            ->maxLength(500)
                            ->disabled(),
                            
                        Forms\Components\DateTimePicker::make('sent_at')
                            ->label('Waktu Terkirim')
                            ->disabled(),
                            
                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('Waktu Tersampaikan')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nomor_invoice')
                    ->label('Nomor Invoice')
                    ->searchable()
                    ->sortable(),
                    
                // Tables\Columns\TextColumn::make('donasi.nomor_transaksi_unik')
                //     ->label('No. Transaksi')
                //     ->searchable()
                //     ->sortable(),
                    
                Tables\Columns\TextColumn::make('donasi.donatur.nama')
                    ->label('Donatur')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => 
                        $record->donasi->atas_nama_hamba_allah 
                            ? 'Hamba Allah' 
                            : ($record->donasi->donatur->nama ?? '-')
                    ),
                    
                Tables\Columns\TextColumn::make('donasi.jumlah')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('delivery_method')
                    ->label('Metode')
                    ->colors([
                        'primary' => 'email',
                        'success' => 'whatsapp',
                        'warning' => 'sms',
                        'secondary' => 'download',
                        'info' => 'print',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                        'sms' => 'SMS',
                        'download' => 'Download',
                        'print' => 'Print',
                        default => $state
                    }),
                    
                Tables\Columns\BadgeColumn::make('delivery_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => ['sent', 'delivered'],
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Menunggu',
                        'sent' => 'Terkirim',
                        'delivered' => 'Tersampaikan',
                        'failed' => 'Gagal',
                        default => $state
                    }),
                    
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Terkirim')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('delivery_method')
                    ->label('Metode Pengiriman')
                    ->options([
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                        'sms' => 'SMS',
                        'download' => 'Download Manual',
                        'print' => 'Print untuk Pickup',
                    ]),
                    
                SelectFilter::make('delivery_status')
                    ->label('Status Pengiriman')
                    ->options([
                        'pending' => 'Menunggu',
                        'sent' => 'Terkirim',
                        'delivered' => 'Tersampaikan',
                        'failed' => 'Gagal',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (InvoiceDonasi $record) => $record->download_url)
                    ->openUrlInNewTab()
                    ->visible(fn (InvoiceDonasi $record) => file_exists($record->pdf_file_path)),
                    
                Action::make('resend')
                    ->label('Kirim Ulang')
                    ->icon('heroicon-o-paper-airplane')
                    ->action(function (InvoiceDonasi $record) {
                        if (!$record->canBeResent()) {
                            Notification::make()
                                ->title('Tidak dapat mengirim ulang')
                                ->body('Invoice dengan status ini tidak dapat dikirim ulang.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // TODO: Implement resend functionality
                        Notification::make()
                            ->title('Fitur belum tersedia')
                            ->body('Fitur kirim ulang akan segera tersedia.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn (InvoiceDonasi $record) => $record->canBeResent())
                    ->requiresConfirmation(),
                    
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('deleteAny', InvoiceDonasi::class)),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListInvoiceDonasi::route('/'),
            'create' => Pages\CreateInvoiceDonasi::route('/create'),
            'view' => Pages\ViewInvoiceDonasi::route('/{record}'),
            'edit' => Pages\EditInvoiceDonasi::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('delivery_status', 'pending')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::where('delivery_status', 'pending')->count();
        return $pendingCount > 0 ? 'warning' : 'success';
    }
}
