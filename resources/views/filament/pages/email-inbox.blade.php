<x-filament-panels::page>
@php
    $stats        = $this->getInboxStats();
    $accounts     = $this->getAccountsForCompany();
    $email        = $this->getSelectedMessage();
    $messages     = $this->fetchMessages();
    $companyLabel = \App\Models\EmailAccount::companyOptions()[$activeCompany] ?? $activeCompany;
    $companyIcon  = \App\Models\EmailAccount::companyIcon($activeCompany);
@endphp

{{-- ── Stats (Filament native style) ── --}}
<div class="fi-wi-stats-overview-stats-ctn grid gap-6 md:grid-cols-3 mb-6">
    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="flex items-center gap-x-2">
                <x-filament::icon icon="heroicon-o-envelope" class="fi-wi-stats-overview-stat-icon h-5 w-5 text-gray-400 dark:text-gray-500" />
                <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">Unread</span>
            </div>
            <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $stats['unread'] }}</div>
        </div>
    </div>
    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="flex items-center gap-x-2">
                <x-filament::icon icon="heroicon-o-inbox" class="fi-wi-stats-overview-stat-icon h-5 w-5 text-gray-400 dark:text-gray-500" />
                <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">Total in Inbox</span>
            </div>
            <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $stats['total'] }}</div>
        </div>
    </div>
    <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="grid gap-y-2">
            <div class="flex items-center gap-x-2">
                <x-filament::icon icon="heroicon-o-star" class="fi-wi-stats-overview-stat-icon h-5 w-5 text-gray-400 dark:text-gray-500" />
                <span class="fi-wi-stats-overview-stat-label text-sm font-medium text-gray-500 dark:text-gray-400">Starred</span>
            </div>
            <div class="fi-wi-stats-overview-stat-value text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $stats['starred'] }}</div>
        </div>
    </div>
</div>

{{-- ── Three-Column Email Client ── --}}
<div class="flex h-[calc(100vh-26rem)] min-h-[440px] rounded-xl overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm">

    {{-- ───── Column 1: Sidebar ───── --}}
    <aside class="w-48 flex-shrink-0 bg-gray-50 dark:bg-gray-800/80 border-r border-gray-200 dark:border-gray-700 flex flex-col">

        {{-- Company header --}}
        <div class="px-3 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2 bg-white dark:bg-gray-900">
            <x-filament::icon icon="{{ $companyIcon }}" class="w-4 h-4 text-primary-500 flex-shrink-0" />
            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $companyLabel }}</span>
        </div>

        {{-- Sync button --}}
        <div class="px-2 py-2 border-b border-gray-200 dark:border-gray-700">
            <button wire:click="syncNow" wire:loading.attr="disabled"
                class="w-full flex items-center justify-center gap-1.5 px-2 py-1.5 text-xs font-medium text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-primary-900/30 hover:bg-primary-100 dark:hover:bg-primary-900/50 border border-primary-200 dark:border-primary-800 rounded-lg transition-colors disabled:opacity-50">
                <x-filament::icon icon="heroicon-o-arrow-path" class="w-3.5 h-3.5" wire:loading.class="animate-spin" wire:target="syncNow" />
                Sync Now
            </button>
        </div>

        {{-- Account info --}}
        @if ($accounts->count() > 1)
            <div class="px-3 pt-2 pb-0.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Accounts</div>
            @foreach ($accounts as $account)
                <button wire:click="selectAccount({{ $account->id }})"
                    class="w-full text-left px-3 py-1.5 text-xs transition-colors
                        {{ $activeAccountId === $account->id
                            ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <div class="truncate">{{ $account->name }}</div>
                </button>
            @endforeach
            <div class="mx-3 my-1 border-t border-gray-200 dark:border-gray-700"></div>
        @elseif ($accounts->count() === 1)
            <div class="px-3 py-2">
                <div class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ $accounts->first()->name }}</div>
                <div class="text-xs text-gray-400 truncate">{{ $accounts->first()->email }}</div>
            </div>
            <div class="mx-3 border-t border-gray-200 dark:border-gray-700"></div>
        @else
            <div class="px-3 py-3 text-xs text-gray-400 italic">No accounts configured.</div>
        @endif

        {{-- Folders --}}
        <nav class="flex-1 px-2 py-1 space-y-0.5 overflow-y-auto">
            @foreach ([
                'INBOX'   => ['icon' => 'heroicon-o-inbox',         'label' => 'Inbox'],
                'Sent'    => ['icon' => 'heroicon-o-paper-airplane', 'label' => 'Sent'],
                'Starred' => ['icon' => 'heroicon-o-star',           'label' => 'Starred'],
            ] as $folder => $cfg)
                <button wire:click="selectFolder('{{ $folder }}')"
                    class="w-full flex items-center gap-2 px-2.5 py-1.5 text-xs rounded-lg transition-colors
                        {{ $activeFolder === $folder
                            ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 font-semibold'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <x-filament::icon icon="{{ $cfg['icon'] }}" class="w-3.5 h-3.5 flex-shrink-0" />
                    <span class="flex-1 text-left">{{ $cfg['label'] }}</span>
                    @if ($folder === 'INBOX' && $stats['unread'] > 0)
                        <span class="text-xs font-bold text-white bg-danger-500 rounded-full px-1.5 py-px leading-none">{{ $stats['unread'] }}</span>
                    @elseif ($folder === 'Starred' && $stats['starred'] > 0)
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $stats['starred'] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>

        {{-- Compose --}}
        <div class="px-2 py-2 border-t border-gray-200 dark:border-gray-700">
            <button wire:click="openCompose()"
                class="w-full flex items-center justify-center gap-1.5 px-2 py-1.5 text-xs font-medium text-white bg-success-600 hover:bg-success-700 rounded-lg transition-colors">
                <x-filament::icon icon="heroicon-o-pencil-square" class="w-3.5 h-3.5" />
                Compose
            </button>
        </div>
    </aside>

    {{-- ───── Column 2: Message List ───── --}}
    <div class="w-64 flex-shrink-0 flex flex-col border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">

        {{-- Search --}}
        <div class="px-2 py-2 border-b border-gray-200 dark:border-gray-700">
            <div class="relative">
                <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400 pointer-events-none" />
                <input type="text" wire:model.live.debounce.400ms="searchQuery" placeholder="Search…"
                    class="w-full pl-8 pr-2 py-1.5 text-xs bg-gray-100 dark:bg-gray-800 border-0 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:outline-none" />
            </div>
        </div>

        {{-- Message list --}}
        <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
            @if (!$activeAccountId)
                <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                    <x-filament::icon icon="heroicon-o-envelope" class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" />
                    <p class="text-xs text-gray-400">No account selected</p>
                </div>
            @else
                @forelse ($messages as $msg)
                    <div wire:click="selectMessage({{ $msg->id }})"
                        class="relative cursor-pointer px-3 py-2.5 transition-colors
                            {{ $selectedMessageId === $msg->id
                                ? 'bg-primary-50 dark:bg-primary-900/20 border-l-2 border-l-primary-500'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-800/60 border-l-2 border-l-transparent' }}">

                        {{-- Unread dot --}}
                        @if (!$msg->is_read)
                            <span class="absolute left-0.5 top-3.5 w-1.5 h-1.5 rounded-full bg-primary-500"></span>
                        @endif

                        {{-- Sender + date --}}
                        <div class="flex items-baseline justify-between gap-1 mb-0.5">
                            <span class="text-xs truncate {{ !$msg->is_read ? 'font-semibold text-gray-900 dark:text-white' : 'font-medium text-gray-600 dark:text-gray-400' }}">
                                {{ $msg->from_name ?: $msg->from_email ?: 'Unknown' }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0 whitespace-nowrap">
                                {{ $msg->sent_at?->timezone('Asia/Shanghai')->format('M j') ?? '' }}
                            </span>
                        </div>

                        {{-- Subject --}}
                        <div class="text-xs leading-snug truncate {{ !$msg->is_read ? 'font-medium text-gray-800 dark:text-gray-200' : 'text-gray-500 dark:text-gray-500' }}">
                            {{ $msg->subject ?: '(No Subject)' }}
                        </div>

                        {{-- Icons row --}}
                        <div class="flex items-center justify-between mt-0.5">
                            <span class="text-xs text-gray-400 dark:text-gray-600 truncate">
                                {{ mb_substr(strip_tags($msg->body_html ?? $msg->body_text ?? ''), 0, 55) }}
                            </span>
                            <div class="flex items-center gap-1 ml-1 flex-shrink-0">
                                @if ($msg->is_starred)
                                    <x-filament::icon icon="heroicon-s-star" class="w-3 h-3 text-warning-400" />
                                @endif
                                @if ($msg->has_attachments)
                                    <x-filament::icon icon="heroicon-o-paper-clip" class="w-3 h-3 text-gray-400" />
                                @endif
                                @if ($msg->ai_summary)
                                    <x-filament::icon icon="heroicon-o-sparkles" class="w-3 h-3 text-violet-400" />
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                        <x-filament::icon icon="heroicon-o-inbox" class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" />
                        <p class="text-xs text-gray-400">No messages in {{ $activeFolder }}</p>
                    </div>
                @endforelse
            @endif
        </div>
    </div>

    {{-- ───── Column 3: Detail / Compose ───── --}}
    <div class="flex-1 flex flex-col min-w-0 bg-white dark:bg-gray-900">

        {{-- ─── Compose Panel ─── --}}
        @if ($isComposing)
            <div class="flex flex-col h-full">

                {{-- Compose toolbar --}}
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-pencil-square" class="w-4 h-4 text-primary-500" />
                        {{ $replyToId ? 'Reply' : 'New Message' }}
                    </h2>
                    <div class="flex items-center gap-2">
                        <button wire:click="doSendEmail" wire:loading.attr="disabled"
                            class="flex items-center gap-1.5 px-4 py-1.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors disabled:opacity-60">
                            <x-filament::icon icon="heroicon-o-paper-airplane" class="w-4 h-4" />
                            <span wire:loading.remove wire:target="doSendEmail">Send</span>
                            <span wire:loading wire:target="doSendEmail">Sending…</span>
                        </button>
                        <button wire:click="closeCompose"
                            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {{-- Compose fields --}}
                <div class="flex-1 overflow-y-auto px-5 py-4 flex flex-col gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">To</label>
                        <input type="email" wire:model="composeTo" placeholder="recipient@example.com"
                            class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent focus:outline-none" />
                        @error('composeTo') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Subject</label>
                        <input type="text" wire:model="composeSubject" placeholder="Subject line"
                            class="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent focus:outline-none" />
                        @error('composeSubject') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-1 flex flex-col min-h-0">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide">Message</label>
                        <textarea wire:model="composeBody" placeholder="Write your message here…"
                            class="flex-1 w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent focus:outline-none resize-none leading-relaxed min-h-[200px]"></textarea>
                        @error('composeBody') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

        {{-- ─── Email Detail ─── --}}
        @elseif ($selectedMessageId && $email)
            <div class="flex flex-col h-full min-h-0">

                {{-- Header --}}
                <div class="flex items-start justify-between gap-4 px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white leading-snug mb-1 break-words">
                            {{ $email->subject ?: '(No Subject)' }}
                        </h2>
                        <div class="text-xs text-gray-500 dark:text-gray-400 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                            <span><span class="font-medium text-gray-600 dark:text-gray-300">From:</span> {{ $email->from_display }}</span>
                            <span class="text-gray-300 dark:text-gray-600">·</span>
                            <span>{{ $email->sent_at?->timezone('Asia/Shanghai')->format('Y-m-d H:i') }}</span>
                        </div>
                        @if (!empty($email->to_addresses))
                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                <span class="font-medium text-gray-500 dark:text-gray-400">To:</span>
                                {{ collect($email->to_addresses)->map(fn($a) => $a['name'] ? "{$a['name']} <{$a['email']}>" : $a['email'])->join(', ') }}
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button wire:click="toggleStar({{ $email->id }})"
                            class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            title="{{ $email->is_starred ? 'Unstar' : 'Star' }}">
                            <x-filament::icon
                                icon="{{ $email->is_starred ? 'heroicon-s-star' : 'heroicon-o-star' }}"
                                class="w-4 h-4 {{ $email->is_starred ? 'text-warning-400' : 'text-gray-400' }}" />
                        </button>
                        <button wire:click="openCompose({{ $email->id }})"
                            class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="w-3.5 h-3.5" />
                            Reply
                        </button>
                        <button wire:click="deleteMessage({{ $email->id }})"
                            wire:confirm="Delete this message permanently?"
                            class="p-1.5 rounded-lg hover:bg-danger-50 dark:hover:bg-danger-900/20 transition-colors">
                            <x-filament::icon icon="heroicon-o-trash" class="w-4 h-4 text-danger-400" />
                        </button>
                    </div>
                </div>

                {{-- Attachments --}}
                @if ($email->has_attachments && $email->attachments->count() > 0)
                    <div class="flex flex-wrap items-center gap-2 px-5 py-2 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
                        <x-filament::icon icon="heroicon-o-paper-clip" class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                        @foreach ($email->attachments as $att)
                            <a href="{{ route('email.attachment.download', $att->id) }}"
                                class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                {{ $att->filename }}
                                <span class="text-gray-400">({{ $att->formatted_size }})</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- AI Summary --}}
                <div class="flex-shrink-0 border-b border-violet-200 dark:border-violet-800/50 bg-violet-50 dark:bg-violet-900/10">
                    <div class="flex items-center justify-between px-5 py-2">
                        <div class="flex items-center gap-1.5">
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-3.5 h-3.5 text-violet-500" />
                            <span class="text-xs font-semibold text-violet-700 dark:text-violet-300">AI Summary</span>
                            @if ($email->ai_summarized_at)
                                <span class="text-xs text-violet-400 dark:text-violet-500">· {{ $email->ai_summarized_at->diffForHumans() }}</span>
                            @endif
                        </div>
                        <button wire:click="aiSummarize" wire:loading.attr="disabled"
                            class="flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-violet-700 dark:text-violet-300 bg-violet-100 dark:bg-violet-800/50 hover:bg-violet-200 dark:hover:bg-violet-800 rounded-md transition-colors disabled:opacity-50">
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-3 h-3" wire:loading.class="animate-pulse" wire:target="aiSummarize" />
                            <span wire:loading.remove wire:target="aiSummarize">{{ ($aiSummary || $email->ai_summary) ? 'Re-summarize' : 'Summarize' }}</span>
                            <span wire:loading wire:target="aiSummarize">Analyzing…</span>
                        </button>
                    </div>
                    @if ($aiSummary || $email->ai_summary)
                        <div class="px-5 pb-3 text-xs text-violet-800 dark:text-violet-200 leading-relaxed">
                            {{ $aiSummary ?: $email->ai_summary }}
                        </div>
                    @endif
                </div>

                {{-- Email body — overflow-x: auto so wide tables scroll instead of overflow --}}
                <div class="flex-1 overflow-y-auto overflow-x-hidden px-5 py-4 min-h-0">
                    @if ($email->body_html)
                        <div class="email-body text-sm text-gray-800 dark:text-gray-200 leading-relaxed">
                            {!! $email->body_html !!}
                        </div>
                    @elseif ($email->body_text)
                        <pre class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-sans leading-relaxed break-words">{{ $email->body_text }}</pre>
                    @else
                        <p class="text-sm text-gray-400 italic">No content.</p>
                    @endif
                </div>
            </div>

        {{-- ─── Empty State ─── --}}
        @else
            <div class="flex-1 flex flex-col items-center justify-center text-center p-12">
                <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                    <x-filament::icon icon="heroicon-o-envelope-open" class="w-7 h-7 text-gray-300 dark:text-gray-600" />
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Select an email to read</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Choose a message from the list on the left.</p>
            </div>
        @endif
    </div>
</div>

<style>
    /* Force HTML email content to respect container bounds */
    .email-body { overflow-x: auto; overflow-wrap: break-word; word-break: break-word; }
    .email-body img { max-width: 100% !important; height: auto !important; }
    .email-body a { color: #2563eb; text-decoration: underline; }
    .email-body table { max-width: 100%; border-collapse: collapse; }
    /* Wide tables get a horizontal scrollbar instead of overflowing */
    .email-body > table,
    .email-body > div > table,
    .email-body > center > table { display: block; overflow-x: auto; }
</style>
</x-filament-panels::page>
