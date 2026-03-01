<?php

namespace App\Filament\Pages;

use App\Jobs\SyncEmailAccountJob;
use App\Models\EmailAccount;
use App\Models\EmailMessage;
use App\Services\DeepSeekService;
use App\Services\SmtpMailService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
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
    public bool   $isComposing    = false;
    public string $composeTo      = '';
    public string $composeSubject = '';
    public string $composeBody    = '';
    public ?int   $replyToId      = null;

    // AI state
    public string $aiSummary = '';

    // Search
    public string $searchQuery = '';

    // Pagination
    public int $page = 1;

    // =========================================================================
    // Lifecycle
    // =========================================================================

    public function mount(): void
    {
        if (!$this->activeAccountId) {
            $account = EmailAccount::where('company', $this->activeCompany)
                ->where('is_active', true)
                ->first();
            $this->activeAccountId = $account?->id;
        }
    }

    // =========================================================================
    // Navigation: one item per company
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
    // Data accessors for Blade
    // NOTE: Do NOT name any method getMessages() — Livewire's WithValidation
    //       trait calls $this->getMessages() internally for custom error bags,
    //       causing a type conflict with a paginator return value.
    // =========================================================================

    public function getAccountsForCompany(): \Illuminate\Database\Eloquent\Collection
    {
        return EmailAccount::where('company', $this->activeCompany)
            ->where('is_active', true)
            ->get();
    }

    /** Returns paginated email list. Named fetchMessages() to avoid Livewire conflict. */
    public function fetchMessages(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = EmailMessage::where('email_account_id', $this->activeAccountId);

        // Starred is a virtual cross-folder view, not a real folder
        if ($this->activeFolder === 'Starred') {
            $query->where('is_starred', true);
        } else {
            $query->where('folder', $this->activeFolder);
        }

        $query->orderBy('sent_at', 'desc');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('subject', 'like', "%{$this->searchQuery}%")
                  ->orWhere('from_email', 'like', "%{$this->searchQuery}%")
                  ->orWhere('from_name', 'like', "%{$this->searchQuery}%");
            });
        }

        return $query->paginate(30, ['*'], 'msg_page', $this->page);
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function prevPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function updatedSearchQuery(): void
    {
        $this->page = 1;
    }

    public function getSelectedMessage(): ?EmailMessage
    {
        if (!$this->selectedMessageId) return null;
        return EmailMessage::with('attachments')->find($this->selectedMessageId);
    }

    public function getInboxStats(): array
    {
        if (!$this->activeAccountId) {
            return ['unread' => 0, 'total' => 0, 'starred' => 0];
        }
        return [
            'unread'  => EmailMessage::where('email_account_id', $this->activeAccountId)
                ->where('folder', 'INBOX')->where('is_read', false)->count(),
            'total'   => EmailMessage::where('email_account_id', $this->activeAccountId)
                ->where('folder', 'INBOX')->count(),
            'starred' => EmailMessage::where('email_account_id', $this->activeAccountId)
                ->where('is_starred', true)->count(),
        ];
    }

    // =========================================================================
    // Actions
    // =========================================================================

    public function selectAccount(int $accountId): void
    {
        $this->activeAccountId   = $accountId;
        $this->selectedMessageId = null;
        $this->aiSummary         = '';
        $this->page              = 1;
    }

    public function selectFolder(string $folder): void
    {
        $this->activeFolder      = $folder;
        $this->selectedMessageId = null;
        $this->aiSummary         = '';
        $this->page              = 1;
    }

    public function selectMessage(int $messageId): void
    {
        $this->selectedMessageId = $messageId;
        $this->aiSummary         = '';
        $this->isComposing       = false;

        EmailMessage::where('id', $messageId)->where('is_read', false)->update(['is_read' => true]);
    }

    public function toggleStar(int $messageId): void
    {
        $msg = EmailMessage::find($messageId);
        if ($msg) {
            $msg->update(['is_starred' => !$msg->is_starred]);
        }
    }

    public function markAllAsRead(): void
    {
        if (!$this->activeAccountId) return;

        $updated = EmailMessage::where('email_account_id', $this->activeAccountId)
            ->where('folder', $this->activeFolder)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        Notification::make()
            ->title($updated > 0 ? "Marked {$updated} messages as read" : 'No unread messages')
            ->success()
            ->send();
    }

    public function syncNow(): void
    {
        if (!$this->activeAccountId) return;
        $account = EmailAccount::find($this->activeAccountId);
        if ($account) {
            SyncEmailAccountJob::dispatch($account);
            Notification::make()->title('Syncing…')->body('New emails will appear shortly.')->info()->send();
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

    public function doSendEmail(): void
    {
        // Named doSendEmail() to avoid any future naming conflicts
        $this->validate([
            'composeTo'      => 'required|email',
            'composeSubject' => 'required|string|max:255',
            'composeBody'    => 'required|string',
        ]);

        $account = EmailAccount::find($this->activeAccountId);
        if (!$account) return;

        try {
            Log::info("[SMTP:{$account->email}] Sending to {$this->composeTo} subject: {$this->composeSubject}");

            app(SmtpMailService::class)->send(
                $account,
                $this->composeTo,
                $this->composeSubject,
                nl2br(e($this->composeBody)),
            );

            Log::info("[SMTP:{$account->email}] Send success");
            Notification::make()->title('Email sent')->success()->send();
            $this->closeCompose();

        } catch (\Exception $e) {
            Log::error("[SMTP:{$account->email}] Send failed: " . $e->getMessage());
            Notification::make()->title('Failed to send')->body($e->getMessage())->danger()->send();
        }
    }

    public function aiSummarize(): void
    {
        $message = $this->getSelectedMessage();
        if (!$message) return;

        // Return cached summary immediately
        if ($message->ai_summary) {
            $this->aiSummary = $message->ai_summary;
            return;
        }

        Log::info("[DeepSeek] Summarizing message id={$message->id} subject={$message->subject}");

        try {
            $this->aiSummary = app(DeepSeekService::class)->summarizeEmail($message);
            Log::info("[DeepSeek] Summary generated, length=" . mb_strlen($this->aiSummary));
        } catch (\Exception $e) {
            Log::error("[DeepSeek] Summarize failed: " . $e->getMessage());
            Notification::make()->title('AI failed')->body($e->getMessage())->danger()->send();
            $this->aiSummary = '';
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
