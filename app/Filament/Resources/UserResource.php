<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Models\Donasi;
use App\Notifications\CustomResetPassword;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Improved navigation configuration
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Administrator';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Pengguna';
    protected static ?string $modelLabel = 'Pengguna';
    protected static ?string $pluralModelLabel = 'Pengguna';
    
    public static function getNavigationGroup(): ?string
    {
        return __('app.navigation.groups.administrator');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // User Identity Section
                Forms\Components\Section::make('Identitas Pengguna')
                    ->description('Informasi dasar pengguna sistem')
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama lengkap pengguna')
                            ->readOnly(false) // Disable read-only for now
                            ->helperText('Nama lengkap pengguna'),
                            
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('contoh@email.com')
                            ->readOnly(false) // Disable read-only for now
                            ->helperText('Email ini akan digunakan untuk login'),
                    ]),
                
                // Authentication Section
                Forms\Components\Section::make('Autentikasi')
                    ->description('Pengaturan kata sandi pengguna')
                    ->icon('heroicon-o-key')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Kata Sandi')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn ($record) => ! $record)
                            ->rule(PasswordRule::default())
                            ->autocomplete('new-password')
                            ->hiddenOn('view')
                            ->placeholder(fn ($record) => $record ? '••••••••' : 'Masukkan kata sandi')
                            ->visible(true) // Show for all users for now
                            ->helperText(fn ($record) => $record 
                                ? 'Biarkan kosong jika tidak ingin mengubah kata sandi' 
                                : 'Minimal 8 karakter'),
                            
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Konfirmasi Kata Sandi')
                            ->password()
                            ->required(fn ($record) => ! $record)
                            ->dehydrated(false)
                            ->placeholder('Konfirmasi kata sandi')
                            ->hiddenOn(['view', 'edit'])
                            ->visible(true), // Show for all users for now
                    ]),
                
                // Permissions Section
                Forms\Components\Section::make('Hak Akses')
                    ->description('Pengaturan peran dan izin pengguna')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Peran')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Pilih satu atau lebih peran untuk pengguna ini')
                            ->columnSpanFull(),
                    ]),
                
                // Additional Information Section (can be expanded later)
                Forms\Components\Section::make('Informasi Tambahan')
                    ->description('Data tambahan pengguna')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Dibuat Pada')
                            ->content(fn (?User $record): string => $record?->created_at?->diffForHumans() ?? '-')
                            ->visible(fn (?User $record) => $record !== null),
                            
                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->content(fn (?User $record): string => $record?->updated_at?->diffForHumans() ?? '-')
                            ->visible(fn (?User $record) => $record !== null),
                    ])
                    ->collapsed()
                    ->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-envelope'),
                    
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Tgl Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Peran')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                // Send Password Reset Email Action for Super Admin
                Action::make('sendPasswordReset')
                    ->label('Kirim Tautan Reset')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Kirim Tautan Reset Password')
                    ->modalDescription(fn (User $record) => 
                        "Apakah Anda yakin ingin mengirim tautan reset password ke email '{$record->email}'? " .
                        "Pengguna akan menerima email berisi tautan aman untuk mereset password mereka."
                    )
                    ->modalSubmitActionLabel('Ya, Kirim Email')
                    ->action(function (User $record) {
                        // Generate password reset token manually to use our custom notification
                        $token = Str::random(64);
                        
                        // Store the token in password_reset_tokens table using Laravel's expected format
                        DB::table('password_reset_tokens')->updateOrInsert(
                            ['email' => $record->email],
                            [
                                'email' => $record->email,
                                'token' => hash('sha256', $token), // Use SHA256 hash like Laravel's Password broker
                                'created_at' => now(),
                            ]
                        );
                        
                        try {
                            // Send our custom notification
                            $record->notify(new CustomResetPassword($token));
                            
                            Notification::make()
                                ->title('Email reset password berhasil dikirim')
                                ->body("Tautan reset password telah dikirim ke {$record->email}. Pengguna dapat menggunakan tautan tersebut untuk mengatur password baru.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal mengirim email')
                                ->body('Terjadi kesalahan saat mengirim email reset password. Silakan coba lagi.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(function (User $record) {
                        // Show reset button for all users except current user
                        return Auth::id() !== $record->id;
                    }),
                    
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}





