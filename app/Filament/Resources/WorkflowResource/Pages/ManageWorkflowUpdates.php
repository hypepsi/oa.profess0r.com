<?php

namespace App\Filament\Resources\WorkflowResource\Pages;

use App\Filament\Resources\WorkflowResource;
use App\Models\Workflow;
use App\Models\WorkflowUpdate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Pages\Page; // ✅ 资源内页面应继承这个
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class ManageWorkflowUpdates extends Page implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;
    use WithFileUploads;

    protected static string $resource = WorkflowResource::class;

    protected static string $view = 'filament.resources.workflow-resource.pages.manage-workflow-updates';

    public Workflow $record;

    public ?array $data = [];

    public function mount($record): void
    {
        $this->record = Workflow::findOrFail($record);
        $this->form->fill([
            'content' => '',
            'attachments' => [],
            'mark_updated' => true,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('content')
                ->label('Update content')
                ->rows(4)
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('attachments')
                ->label('Attachments')
                ->multiple()
                ->reorderable()
                ->directory('workflow-updates/' . $this->record->id)
                ->disk('public')
                ->preserveFilenames()
                ->downloadable()
                ->openable()
                ->columnSpanFull(),

            Forms\Components\Toggle::make('mark_updated')
                ->label('Mark parent workflow as Updated after submit')
                ->default(true),
        ])->columns(1)->statePath('data');
    }

    public function createUpdate(): void
    {
        $data = $this->form->getState();

        WorkflowUpdate::create([
            'workflow_id' => $this->record->id,
            'user_id'     => Auth::id(),
            'content'     => $data['content'] ?? null,
            'attachments' => $data['attachments'] ?? [],
        ]);

        if (!empty($data['mark_updated'])) {
            $this->record->update(['status' => 'updated']);
        }

        $this->form->fill([
            'content' => '',
            'attachments' => [],
            'mark_updated' => true,
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkflowUpdate::query()
                    ->where('workflow_id', $this->record->id)
                    ->latest('id')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('User')->sortable(),
                Tables\Columns\TextColumn::make('content')->label('Content')->limit(60),
                Tables\Columns\TextColumn::make('attachmentsCount')->label('Files')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime('Y-m-d H:i'),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('submit')
                ->label('Submit Update')
                ->submit('createUpdate')
                ->color('primary'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Updates';
    }

    public function getTitle(): string
    {
        return 'Workflow Updates: ' . $this->record->title;
    }

    protected function getForms(): array
    {
        return ['form'];
    }
}
