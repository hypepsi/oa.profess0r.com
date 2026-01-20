<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Registration Docs';
    protected static ?string $pluralModelLabel = 'Registration Docs';
    protected static ?string $modelLabel = 'Registration Doc';
    protected static ?string $navigationGroup = 'Documents';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Document Information')
                    ->description('Upload and manage company documents')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter document title')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->placeholder('Enter document description (optional)')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('file_path')
                            ->label('Document File')
                            ->disk('public')
                            ->directory('documents')
                            ->visibility('private')
                            ->downloadable()
                            ->openable()
                            ->previewable(true)
                            ->acceptedFileTypes([
                                // PDF
                                'application/pdf',
                                
                                // Word Documents
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-word',
                                
                                // Excel Spreadsheets
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/excel',
                                'application/x-excel',
                                'application/x-msexcel',
                                
                                // PowerPoint Presentations
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'application/mspowerpoint',
                                'application/powerpoint',
                                
                                // Text files
                                'text/plain',
                                'text/csv',
                                
                                // Images
                                'image/jpeg',
                                'image/jpg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                                'image/svg+xml',
                                
                                // Archives
                                'application/zip',
                                'application/x-zip',
                                'application/x-zip-compressed',
                                'application/x-rar',
                                'application/x-rar-compressed',
                                'application/octet-stream',
                                
                                // Other common formats
                                'application/rtf',
                                'application/x-7z-compressed',
                            ])
                            ->maxSize(51200) // 50MB
                            ->required()
                            ->helperText('Supported formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, CSV, Images, ZIP, RAR, 7Z (Max: 50MB)')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('file_name', $state->getClientOriginalName());
                                    $set('file_type', $state->getMimeType());
                                    $set('file_size', $state->getSize());
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->options([
                                'Contract' => 'Contract',
                                'Invoice' => 'Invoice',
                                'Agreement' => 'Agreement',
                                'Policy' => 'Policy',
                                'Report' => 'Report',
                                'Certificate' => 'Certificate',
                                'Other' => 'Other',
                            ])
                            ->searchable()
                            ->placeholder('Select category (optional)'),

                        Forms\Components\Hidden::make('file_name'),
                        Forms\Components\Hidden::make('file_type'),
                        Forms\Components\Hidden::make('file_size'),
                        Forms\Components\Hidden::make('uploaded_by_user_id')
                            ->default(Auth::id()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state = null) => match($state) {
                        'Contract' => 'success',
                        'Invoice' => 'warning',
                        'Agreement' => 'info',
                        'Policy' => 'danger',
                        'Report' => 'primary',
                        'Certificate' => 'success',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),

                Tables\Columns\TextColumn::make('file_type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state = null) => $state ? strtoupper(str_replace('application/', '', $state)) : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('formatted_file_size')
                    ->label('Size')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderBy('file_size', $direction);
                    }),

                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('Uploaded By')
                    ->default('—')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded At')
                    ->dateTime('Y-m-d H:i', 'Asia/Shanghai')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'Contract' => 'Contract',
                        'Invoice' => 'Invoice',
                        'Agreement' => 'Agreement',
                        'Policy' => 'Policy',
                        'Report' => 'Report',
                        'Certificate' => 'Certificate',
                        'Other' => 'Other',
                    ]),
                Tables\Filters\SelectFilter::make('uploaded_by_user_id')
                    ->label('Uploaded By')
                    ->relationship('uploadedBy', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalContent(fn (Document $record) => view('filament.resources.document-resource.preview', ['record' => $record]))
                    ->modalWidth('7xl')
                    ->modalHeading(fn (Document $record) => 'Preview: ' . $record->title)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->visible(fn (Document $record) => in_array($record->file_type, [
                        'application/pdf',
                        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/plain',
                    ])),
                
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(function (Document $record) {
                        if (!Storage::disk('public')->exists($record->file_path)) {
                            Notification::make()
                                ->title('File not found')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        return Storage::disk('public')->download($record->file_path, $record->file_name);
                    }),
                
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->before(function (Document $record) {
                        if (Storage::disk('public')->exists($record->file_path)) {
                            Storage::disk('public')->delete($record->file_path);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Delete files from storage
                            foreach ($records as $record) {
                                if (Storage::disk('public')->exists($record->file_path)) {
                                    Storage::disk('public')->delete($record->file_path);
                                }
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
