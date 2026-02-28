<?php

namespace App\Filament\Pages;

use App\Jobs\SyncEmailAccountJob;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Services\DeepSeekService;
use App\Services\SmtpMailService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;

class EmailInbox extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Emails';
    protected static ?string $navigationLabel = 'Inbox';
    protected static ?int    $navigationSort  = 10;
    protected static ?string $title           = 'Email Inbox';

    protected static string $view = 'filament.pages.email-inbox';

    // =========================================================================
    // State
    // =========================================================================

    #[Url]
    public string $activeCompany = 'bunnycommunications';

    #[Url]
    public ?int $activeAccountId = null;

    #[Url]
    public string $activeFolder = 'INBOX';

    public ?int $selectedMessageId = null;

    // Compose state
    public bool   $isComposing   = false;
    public string $composeTo     = '';
    public string $composeSubject = '';
    public string $composeBody   = '';
    public ?int   $replyToId     = null;

    // AI state
    public bool   $aiLoading  = false;
    public string $aiSummary  = '';

    // Search
    public string $searchQuery = '';

    // =========================================================================
    // Lifecycle
    // =========================================================================

    public function mount(): void
    {
        // Auto-select first account of active company if not set
        if (!$this->activeAccountId) {
            $account = EmailAccount::where('company', $this->activeCompany)
                ->where('is_active', true)
                ->first();
            $this->activeAccountId = $account?->id;
        }
    }

    // =========================================================================
    // Navigation: show per-company items
    // =========================================================================

    public static function getNavigationItems(): array
    {
        $companies = EmailAccount::companyOptions();
        $items = [];

        foreach ($companies as $key => $label) {
            $items[] = \Filament\Navigation\NavigationItem::make($label)
                ->url(static::getUrl(['activeCompany' => $key]))
                ->icon(EmailAccount::companyIcon($key))
                ->group('Emails')
                ->sort(array_search($key, array_keys($companies)) + 10)
                ->badge(fn () => static::getUnreadBadge($key), 'danger')
                ->isActiveWhen(fn () => request()->query('activeCompany', 'bunnycommunications') === $key
                    && request()->routeIs('filament.admin.pages.email-inbox'));
        }

        return $items;
    }

    private static function getUnreadBadge(string $company): ?string
    {
        $count = EmailMessage::whereHas('account', fn ($q) => $q->where('company', $company))
            ->where('is_read', false)
            ->where('folder', 'INBOX')
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    // =========================================================================
    // Data accessors (for Blade)
    // =========================================================================

    public function getCompanies(): array
    {
        return EmailAccount::companyOptions();
    }

    public function getAccountsForCompany(): \Illuminate\Database\Eloquent\Collection
    {
        return EmailAccount::where('company', $this->activeCompany)
            ->where('is_active', true)
            ->get();
    }

    public function getMessages(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = EmailMessage::where('email_account_id', $this->activeAccountId)
            ->where('folder', $this->activeFolder)
            ->orderBy('sent_at', 'desc');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('subject', 'like', "%{$this->searchQuery}%")
                  ->orWhere('from_email', 'like', "%{$this->searchQuery}%")
                  ->orWhere('from_name', 'like', "%{$this->searchQuery}%");
            });
        }

        return $query->paginate(30);
    }

    public function getSelectedMessage(): ?EmailMessage
    {
        if (!$this->selectedMessageId) return null;
        return EmailMessage::with('attachments')->find($this->selectedMessageId);
    }

    // =========================================================================
    // Actions
    // =========================================================================

    public function selectCompany(string $company): void
    {
        $this->activeCompany     = $company;
        $this->activeFolder      = 'INBOX';
        $this->selectedMessageId = null;
        $this->aiSummary         = '';

        $account = EmailAccount::where('company', $company)
            ->where('is_active', true)
            ->first();
        $this->activeAccountId = $account?->id;
    }

    public function selectAccount(int $accountId): void
    {
        $this->activeAccountId   = $accountId;
        $this->selectedMessageId = null;
        $this->aiSummary         = '';
    }

    public function selectFolder(string $folder): void
    {
        $this->activeFolder      = $folder;
        $this->selectedMessageId = null;
        $this->aiSummary         = '';
    }

    public function selectMessage(int $messageId): void
    {
        $this->selectedMessageId = $messageId;
        $this->aiSummary         = '';
        $this->isComposing       = false;

        // Mark as read
        EmailMessage::where('id', $messageId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function toggleStar(int $messageId): void
    {
        $message = EmailMessage::find($messageId);
        if ($message) {
            $message->update(['is_starred' => !$message->is_starred]);
        }
    }

    public function syncNow(): void
    {
        if (!$this->activeAccountId) return;
        $account = EmailAccount::find($this->activeAccountId);
        if ($account) {
            SyncEmailAccountJob::dispatch($account);
            Notification::make()
                ->title('Syncing email...')
                ->body('New emails will appear shortly.')
                ->info()
                ->send();
        }
    }

    public function openCompose(?int $replyToId = null): void
    {
        $this->isComposing       = true;
        $this->selectedMessageId = null;
        $this->replyToId         = $replyToId;
        $this->composeTo         = '';
        $this->composeSubject    = '';
        $this->composeBody       = '';

        if ($replyToId) {
            $original = EmailMessage::find($replyToId);
            if ($original) {
                $this->composeTo      = $original->from_email ?? '';
                $this->composeSubject = 'Re: ' . $original->subject;
            }
        }
    }

    public function closeCompose(): void
    {
        $this->isComposing = false;
        $this->replyToId   = null;
    }

    public function sendEmail(): void
    {
        $this->validate([
            'composeTo'      => 'required|email',
            'composeSubject' => 'required|string|max:255',
            'composeBody'    => 'required|string',
        ]);

        $account = EmailAccount::find($this->activeAccountId);
        if (!$account) return;

        try {
            app(SmtpMailService::class)->send(
                $account,
                $this->composeTo,
                $this->composeSubject,
                nl2br(e($this->composeBody)),
                $this->replyToId ? (string) $this->replyToId : null
            );

            Notification::make()
                ->title('Email sent')
                ->success()
                ->send();

            $this->closeCompose();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to send email')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function aiSummarize(): void
    {
        $message = $this->getSelectedMessage();
        if (!$message) return;

        // Use cached summary if available
        if ($message->ai_summary) {
            $this->aiSummary = $message->ai_summary;
            return;
        }

        $this->aiLoading = true;

        try {
            $this->aiSummary = app(DeepSeekService::class)->summarizeEmail($message);
        } catch (\Exception $e) {
            Notification::make()
                ->title('AI summarization failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
            $this->aiSummary = '';
        } finally {
            $this->aiLoading = false;
        }
    }

    public function deleteMessage(int $messageId): void
    {
        EmailMessage::find($messageId)?->delete();

        if ($this->selectedMessageId === $messageId) {
            $this->selectedMessageId = null;
            $this->aiSummary         = '';
        }

        Notification::make()->title('Message deleted')->success()->send();
    }
}
