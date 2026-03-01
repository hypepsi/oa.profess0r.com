<x-filament-panels::page>
@php
    $stats        = $this->getInboxStats();
    $accounts     = $this->getAccountsForCompany();
    $messages     = $this->fetchMessages();
    $email        = $this->getSelectedMessage();
    $companyLabel = \App\Models\EmailAccount::companyOptions()[$activeCompany] ?? $activeCompany;
    $companyIcon  = \App\Models\EmailAccount::companyIcon($activeCompany);
@endphp

{{-- ══════════════════════════════
     Stats Cards (Filament native)
     ══════════════════════════════ --}}
<div class="fi-wi-stats-overview-stats-ctn grid gap-6 md:grid-cols-3 mb-6">
    @foreach ([
        ['icon' => 'heroicon-o-envelope', 'label' => 'Unread',   'value' => $stats['unread']],
        ['icon' => 'heroicon-o-inbox',    'label' => 'In Inbox', 'value' => $stats['total']],
        ['icon' => 'heroicon-o-star',     'label' => 'Starred',  'value' => $stats['starred']],
    ] as $s)
    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="flex items-center gap-x-2">
                <x-filament::icon
                    :icon="$s['icon']"
                    class="fi-wi-stats-overview-stat-icon h-5 w-5 text-gray-400 dark:text-gray-500"
                />
                <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ $s['label'] }}
                </span>
            </div>
            <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ $s['value'] }}
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ══════════════════════════════
     3-Column Email Panel
     Col 1 w-56 | Col 2 w-72 | Col 3 flex-1
     ══════════════════════════════ --}}
<div class="flex rounded-xl overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm"
     style="height: calc(100vh - 17rem); min-height: 480px;">

    {{-- ──────────────────────────
         COL 1 · Sidebar · w-56
         ────────────────────────── --}}
    <aside class="w-56 flex-shrink-0 flex flex-col bg-gray-50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700">

        {{-- Company + Sync --}}
        <div class="flex items-center justify-between gap-2 px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <x-filament::icon :icon="$companyIcon" class="w-4 h-4 text-primary-500 flex-shrink-0" />
                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                    {{ $companyLabel }}
                </span>
            </div>
            <x-filament::icon-button
                wire:click="syncNow"
                icon="heroicon-o-arrow-path"
                label="Sync"
                color="gray"
                size="sm"
                tooltip="Sync Now"
            />
        </div>

        {{-- Compose Button --}}
        <div class="px-3 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <x-filament::button
                wire:click="openCompose()"
                icon="heroicon-o-pencil-square"
                color="primary"
                size="sm"
                class="w-full justify-center"
            >Compose</x-filament::button>
        </div>

        {{-- Account Info --}}
        @if ($accounts->count() === 1)
        <div class="px-4 py-2.5 border-b border-gray-100 dark:border-gray-800 flex-shrink-0 overflow-hidden">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-0.5">Account</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $accounts->first()->email }}</p>
        </div>
        @elseif ($accounts->count() > 1)
        <div class="px-4 pt-2.5 pb-1 flex-shrink-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Accounts</p>
        </div>
        @foreach ($accounts as $acc)
        <button wire:click="selectAccount({{ $acc->id }})"
            class="w-full text-left px-4 py-1.5 text-xs truncate flex-shrink-0 transition-colors
                {{ $activeAccountId === $acc->id
                    ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 font-semibold'
                    : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700/50' }}">
            {{ $acc->name }}
        </button>
        @endforeach
        <div class="my-1 mx-4 border-t border-gray-200 dark:border-gray-700 flex-shrink-0"></div>
        @endif

        {{-- Folder Navigation --}}
        <nav class="flex-1 overflow-y-auto px-2 py-2 space-y-0.5">
            @foreach ([
                'INBOX'   => ['icon' => 'heroicon-o-inbox',         'label' => 'Inbox',   'badge' => $stats['unread']],
                'Sent'    => ['icon' => 'heroicon-o-paper-airplane', 'label' => 'Sent',    'badge' => null],
                'Starred' => ['icon' => 'heroicon-o-star',           'label' => 'Starred', 'badge' => $stats['starred']],
            ] as $folder => $f)
            <button wire:click="selectFolder('{{ $folder }}')"
                class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors
                    {{ $activeFolder === $folder
                        ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 font-semibold'
                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200/70 dark:hover:bg-gray-700/50' }}">
                <x-filament::icon :icon="$f['icon']" class="w-4 h-4 flex-shrink-0" />
                <span class="flex-1 text-left">{{ $f['label'] }}</span>
                @if ($f['badge'])
                <x-filament::badge color="danger" size="xs">{{ $f['badge'] }}</x-filament::badge>
                @endif
            </button>
            @endforeach
        </nav>

    </aside>

    {{-- ──────────────────────────
         COL 2 · Message List · w-72
         ────────────────────────── --}}
    <div class="w-72 flex-shrink-0 flex flex-col border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">

        {{-- Search + Mark all read --}}
        <div class="px-3 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 flex items-center gap-2">
            <div class="flex-1 min-w-0">
                <x-filament::input.wrapper prefixIcon="heroicon-o-magnifying-glass">
                    <x-filament::input
                        type="text"
                        wire:model.live.debounce.400ms="searchQuery"
                        placeholder="Search…"
                    />
                </x-filament::input.wrapper>
            </div>
            <x-filament::icon-button
                wire:click="markAllAsRead"
                icon="heroicon-o-check-circle"
                color="gray"
                size="sm"
                tooltip="Mark all as read"
                label="Mark all read"
            />
        </div>

        {{-- Messages --}}
        <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">

            @if (!$activeAccountId)
            <div class="h-full flex flex-col items-center justify-center p-8 text-center">
                <x-filament::icon icon="heroicon-o-envelope" class="w-10 h-10 text-gray-200 dark:text-gray-700 mb-3" />
                <p class="text-sm text-gray-400">No account selected</p>
            </div>
            @else

            @forelse ($messages as $msg)
            <button
                wire:key="msg-{{ $msg->id }}"
                wire:click="selectMessage({{ $msg->id }})"
                class="w-full text-left block overflow-hidden transition-colors
                    {{ $selectedMessageId === $msg->id
                        ? 'bg-primary-50 dark:bg-primary-900/20 border-l-[3px] border-primary-500'
                        : 'bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">
                <div class="px-4 py-3.5">
                    {{-- Sender + Date --}}
                    <div class="flex items-baseline justify-between gap-2 mb-1">
                        <span class="text-sm truncate min-w-0 flex-1
                            {{ !$msg->is_read
                                ? 'font-semibold text-gray-900 dark:text-white'
                                : 'font-medium text-gray-600 dark:text-gray-300' }}">
                            {{ $msg->from_name ?: ($msg->from_email ?: 'Unknown') }}
                        </span>
                        <span class="text-xs text-gray-400 flex-shrink-0 ml-1">
                            {{ $msg->sent_at?->timezone('Asia/Shanghai')->format('M j') ?? '' }}
                        </span>
                    </div>
                    {{-- Subject --}}
                    <p class="text-sm truncate mb-1
                        {{ !$msg->is_read
                            ? 'font-medium text-gray-800 dark:text-gray-100'
                            : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $msg->subject ?: '(No Subject)' }}
                    </p>
                    {{-- Preview + Status Icons --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400 truncate flex-1 min-w-0">
                            {{ $msg->preview }}
                        </span>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            @if (!$msg->is_read)
                                <span class="w-2 h-2 rounded-full bg-primary-500 inline-block"></span>
                            @endif
                            @if ($msg->is_starred)
                                <x-filament::icon icon="heroicon-s-star" class="w-3.5 h-3.5 text-warning-400" />
                            @endif
                            @if ($msg->has_attachments)
                                <x-filament::icon icon="heroicon-o-paper-clip" class="w-3.5 h-3.5 text-gray-400" />
                            @endif
                        </div>
                    </div>
                </div>
            </button>
            @empty
            <div class="h-full flex flex-col items-center justify-center p-8 text-center">
                <x-filament::icon icon="heroicon-o-inbox" class="w-10 h-10 text-gray-200 dark:text-gray-700 mb-3" />
                <p class="text-sm text-gray-400">No messages in {{ $activeFolder }}</p>
            </div>
            @endforelse

            @endif
        </div>

        {{-- Pagination --}}
        @if ($messages->hasPages())
        <div class="flex items-center justify-between px-4 py-2 border-t border-gray-100 dark:border-gray-800 flex-shrink-0">
            <button
                wire:click="prevPage"
                @if ($messages->onFirstPage()) disabled @endif
                class="flex items-center gap-1 text-xs px-2 py-1 rounded transition-colors
                    {{ $messages->onFirstPage()
                        ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                        : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                <x-filament::icon icon="heroicon-o-chevron-left" class="w-3.5 h-3.5" />
                Prev
            </button>
            <span class="text-xs text-gray-400">
                {{ $messages->currentPage() }} / {{ $messages->lastPage() }}
            </span>
            <button
                wire:click="nextPage"
                @if (!$messages->hasMorePages()) disabled @endif
                class="flex items-center gap-1 text-xs px-2 py-1 rounded transition-colors
                    {{ !$messages->hasMorePages()
                        ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                        : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                Next
                <x-filament::icon icon="heroicon-o-chevron-right" class="w-3.5 h-3.5" />
            </button>
        </div>
        @endif

    </div>

    {{-- ──────────────────────────
         COL 3 · Detail · flex-1
         ────────────────────────── --}}
    <div class="flex-1 min-w-0 flex flex-col bg-white dark:bg-gray-900">

        {{-- ┌──────────────────────┐
             │  COMPOSE             │
             └──────────────────────┘ --}}
        @if ($isComposing)
        <div class="flex-1 flex flex-col min-h-0 overflow-hidden">

            <div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-pencil-square" class="w-4 h-4 text-primary-500" />
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ $replyToId ? 'Reply' : 'New Message' }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <x-filament::button
                        wire:click="doSendEmail"
                        wire:loading.attr="disabled"
                        icon="heroicon-o-paper-airplane"
                        color="primary"
                        size="sm"
                    >
                        <span wire:loading.remove wire:target="doSendEmail">Send</span>
                        <span wire:loading wire:target="doSendEmail">Sending…</span>
                    </x-filament::button>
                    <x-filament::icon-button
                        wire:click="closeCompose"
                        icon="heroicon-o-x-mark"
                        color="gray"
                        label="Close"
                        size="sm"
                    />
                </div>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-6">
                <div class="max-w-2xl space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">To</label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="email"
                                wire:model="composeTo"
                                placeholder="recipient@example.com"
                            />
                        </x-filament::input.wrapper>
                        @error('composeTo')
                        <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Subject</label>
                        <x-filament::input.wrapper>
                            <x-filament::input
                                type="text"
                                wire:model="composeSubject"
                                placeholder="Subject"
                            />
                        </x-filament::input.wrapper>
                        @error('composeSubject')
                        <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Message</label>
                        <textarea
                            wire:model="composeBody"
                            placeholder="Write your message…"
                            rows="16"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none resize-none"
                        ></textarea>
                        @error('composeBody')
                        <p class="text-xs text-danger-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

        </div>

        {{-- ┌──────────────────────┐
             │  EMAIL DETAIL        │
             └──────────────────────┘ --}}
        @elseif ($email)
        <div wire:key="detail-{{ $email->id }}" class="flex-1 flex flex-col min-h-0 overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">

                {{-- Row 1: Subject + Actions --}}
                <div class="flex items-start justify-between gap-4 mb-2.5">
                    <h1 class="flex-1 min-w-0 text-lg font-semibold text-gray-900 dark:text-white leading-snug break-words">
                        {{ $email->subject ?: '(No Subject)' }}
                    </h1>
                    <div class="flex items-center gap-1 flex-shrink-0 pt-0.5">
                        <x-filament::icon-button
                            wire:click="toggleStar({{ $email->id }})"
                            icon="{{ $email->is_starred ? 'heroicon-s-star' : 'heroicon-o-star' }}"
                            color="{{ $email->is_starred ? 'warning' : 'gray' }}"
                            label="{{ $email->is_starred ? 'Unstar' : 'Star' }}"
                            size="sm"
                            tooltip="{{ $email->is_starred ? 'Unstar' : 'Star' }}"
                        />
                        <x-filament::button
                            wire:click="openCompose({{ $email->id }})"
                            icon="heroicon-o-arrow-uturn-left"
                            color="gray"
                            size="sm"
                            outlined
                        >Reply</x-filament::button>
                        <x-filament::icon-button
                            wire:click="deleteMessage({{ $email->id }})"
                            wire:confirm="Delete this message permanently?"
                            icon="heroicon-o-trash"
                            color="danger"
                            label="Delete"
                            size="sm"
                            tooltip="Delete"
                        />
                    </div>
                </div>

                {{-- Row 2: Sender + Date --}}
                <div class="flex items-center justify-between gap-4 min-w-0">
                    <div class="flex items-center gap-1.5 min-w-0 overflow-hidden text-sm">
                        <span class="font-medium text-gray-800 dark:text-gray-200 truncate">
                            {{ $email->from_name ?: $email->from_email }}
                        </span>
                        @if ($email->from_name)
                        <span class="text-xs text-gray-400 truncate flex-shrink-0">
                            &lt;{{ $email->from_email }}&gt;
                        </span>
                        @endif
                        @if (!empty($email->to_addresses))
                        <span class="text-gray-300 dark:text-gray-600 flex-shrink-0">·</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            to {{ collect($email->to_addresses)->map(fn($a) => $a['email'] ?? '')->filter()->first() }}
                            @if (count($email->to_addresses) > 1)
                            <span class="text-gray-400">+{{ count($email->to_addresses) - 1 }}</span>
                            @endif
                        </span>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0 whitespace-nowrap">
                        {{ $email->sent_at?->timezone('Asia/Shanghai')->format('M j, Y · H:i') }}
                    </span>
                </div>
            </div>

            {{-- Attachments --}}
            @if ($email->has_attachments && $email->attachments->count() > 0)
            <div class="flex flex-wrap gap-2 px-6 py-2.5 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 flex-shrink-0">
                @foreach ($email->attachments as $att)
                <x-filament::button
                    :href="route('email.attachment.download', $att->id)"
                    tag="a"
                    icon="heroicon-o-arrow-down-tray"
                    color="gray"
                    size="xs"
                    outlined
                >{{ $att->filename }} <span class="text-gray-400 ml-1 font-normal">({{ $att->formatted_size }})</span>
                </x-filament::button>
                @endforeach
            </div>
            @endif

            {{-- AI Summary Bar --}}
            <div class="flex-shrink-0 bg-violet-50/60 dark:bg-violet-900/10 border-b border-violet-100 dark:border-violet-900/30">
                <div class="flex items-center justify-between px-6 py-2.5">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-sparkles" class="w-4 h-4 text-violet-500" />
                        <span class="text-sm font-medium text-violet-700 dark:text-violet-300">AI Summary</span>
                        @if ($email->ai_summarized_at)
                        <span class="text-xs text-violet-400">· {{ $email->ai_summarized_at->diffForHumans() }}</span>
                        @endif
                    </div>
                    <x-filament::button
                        wire:click="aiSummarize"
                        wire:loading.attr="disabled"
                        icon="heroicon-o-sparkles"
                        color="primary"
                        size="xs"
                        outlined
                    >
                        <span wire:loading.remove wire:target="aiSummarize">
                            {{ ($aiSummary || $email->ai_summary) ? 'Refresh' : 'Summarize' }}
                        </span>
                        <span wire:loading wire:target="aiSummarize">Analyzing…</span>
                    </x-filament::button>
                </div>
                @if ($aiSummary || $email->ai_summary)
                <p class="px-6 pb-3 text-sm text-violet-900 dark:text-violet-200 leading-relaxed">
                    {{ $aiSummary ?: $email->ai_summary }}
                </p>
                @endif
            </div>

            {{-- Email Body --}}
            <div class="flex-1 min-h-0 overflow-y-auto">
                <div wire:key="body-{{ $email->id }}" class="max-w-3xl mx-auto px-8 py-6">
                    @if ($email->body_html)
                        <div class="email-body text-sm text-gray-800 dark:text-gray-200 leading-relaxed">
                            {!! $email->body_html !!}
                        </div>
                    @elseif ($email->body_text)
                        <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-sans leading-relaxed break-words">{{ $email->body_text }}</pre>
                    @else
                        <div class="py-16 flex flex-col items-center text-center">
                            <x-filament::icon icon="heroicon-o-document" class="w-8 h-8 text-gray-200 dark:text-gray-700 mb-3" />
                            <p class="text-sm text-gray-400 italic">No readable content.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- ┌──────────────────────┐
             │  EMPTY STATE         │
             └──────────────────────┘ --}}
        @else
        <div class="flex-1 flex flex-col items-center justify-center text-center p-12">
            <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                <x-filament::icon icon="heroicon-o-envelope-open" class="w-8 h-8 text-gray-300 dark:text-gray-600" />
            </div>
            <p class="text-base font-semibold text-gray-500 dark:text-gray-400 mb-1">Select an email to read</p>
            <p class="text-sm text-gray-400 dark:text-gray-500">Choose a message from the list on the left.</p>
        </div>
        @endif

    </div>{{-- end col 3 --}}

</div>{{-- end panel --}}

<style>
.email-body { word-break: break-word; overflow-wrap: break-word; }
.email-body img { max-width: 100% !important; height: auto !important; }
.email-body a { color: #2563eb; text-decoration: underline; word-break: break-all; }
.email-body table { border-collapse: collapse; max-width: 100%; }
.email-body > table,
.email-body > div > table,
.email-body > center > table,
.email-body > center { display: block; overflow-x: auto; }
</style>

</x-filament-panels::page>
