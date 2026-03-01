<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailAccountResource\Pages;
use App\Jobs\SyncEmailAccountJob;
use App\Models\EmailAccount;
use App\Services\ImapService;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailAccountResource extends Resource
{
    protected static ?string $model = EmailAccount::class;
    protected static ?string $navigationIcon  = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Emails';
    protected static ?string $navigationLabel = 'Account Settings';
    protected static ?int    $navigationSort  = 99;
    protected static ?string $modelLabel      = 'Email Account';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    // =========================================================================
    // Form
    // =========================================================================

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Account Identity')
                ->icon('heroicon-o-identification')
                ->compact()
                ->columns(4)
                ->schema([
                    TextInput::make('name')
                        ->label('Account Name')
                        ->placeholder('e.g. Support')
                        ->required()
                        ->maxLength(100),

                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Select::make('company')
                        ->label('Company')
                        ->options(EmailAccount::companyOptions())
                        ->required()
                        ->searchable(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),
                ]),

            Section::make('Credentials')
                ->icon('heroicon-o-key')
                ->compact()
                ->schema([
                    TextInput::make('password_plain')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->required(fn ($operation) => $operation === 'create')
                        ->helperText('Leave blank to keep existing password.')
                        ->maxLength(255),
                ]),

            Section::make('Server Settings')
                ->icon('heroicon-o-server')
                ->compact()
                ->columns(6)
                ->schema([
                    TextInput::make('imap_host')
                        ->label('IMAP Host')
                        ->default('mail.privateemail.com')
                        ->required()
                        ->columnSpan(2),

                    TextInput::make('imap_port')
                        ->label('IMAP Port')
                        ->numeric()
                        ->default(993)
                        ->required()
                        ->columnSpan(1),

                    Select::make('imap_encryption')
                        ->label('IMAP Enc.')
                        ->options(['ssl' => 'SSL', 'tls' => 'TLS', 'starttls' => 'STARTTLS'])
                        ->default('ssl')
                        ->required()
                        ->columnSpan(1),

                    TextInput::make('smtp_host')
                        ->label('SMTP Host')
                        ->default('mail.privateemail.com')
                        ->required()
                        ->columnSpan(2),

                    TextInput::make('smtp_port')
                        ->label('SMTP Port')
                        ->numeric()
                        ->default(465)
                        ->required()
                        ->columnSpan(1),

                    Select::make('smtp_encryption')
                        ->label('SMTP Enc.')
                        ->options(['ssl' => 'SSL', 'tls' => 'TLS', 'starttls' => 'STARTTLS'])
                        ->default('ssl')
                        ->required()
                        ->columnSpan(1),
                ]),
        ]);
    }

    // =========================================================================
    // Table
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Account')
                    ->weight(\Filament\Support\Enums\FontWeight::Medium)
                    ->description(fn (EmailAccount $r) => $r->email),

                TextColumn::make('company')
                    ->label('Company')
                    ->badge()
                    ->color(fn (string $state) => EmailAccount::companyColor($state))
                    ->formatStateUsing(fn (string $state) => EmailAccount::companyOptions()[$state] ?? $state),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('sync_status')
                    ->label('Sync Status')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'idle'    => 'success',
                        'syncing' => 'warning',
                        'error'   => 'danger',
                        default   => 'gray',
                    }),

                TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime('M j, H:i')
                    ->timezone('Asia/Shanghai')
                    ->placeholder('Never'),

                TextColumn::make('messages_count')
                    ->label('Messages')
                    ->counts('messages')
                    ->badge()
                    ->color('gray'),
            ])
            ->actions([
                Action::make('test_connection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-wifi')
                    ->color('gray')
                    ->iconButton()
                    ->tooltip('Test IMAP Connection')
                    ->action(function (EmailAccount $record) {
                        try {
                            app(ImapService::class)->testConnection($record);
                            Notification::make()
                                ->title('Connection successful')
                                ->body("IMAP connected to {$record->imap_host}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Connection failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('sync_now')
                    ->label('Sync Now')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->iconButton()
                    ->tooltip('Sync Now')
                    ->action(function (EmailAccount $record) {
                        SyncEmailAccountJob::dispatch($record);
                        Notification::make()
                            ->title('Sync queued')
                            ->body("Email sync for {$record->email} has been queued.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // =========================================================================
    // Pages
    // =========================================================================

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmailAccounts::route('/'),
            'create' => Pages\CreateEmailAccount::route('/create'),
            'edit'   => Pages\EditEmailAccount::route('/{record}/edit'),
        ];
    }
}
