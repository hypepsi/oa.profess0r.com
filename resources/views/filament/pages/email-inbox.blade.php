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

{{-- ── Three-column email client ── --}}
<div class="flex h-[calc(100vh-22rem)] min-h-[420px] rounded-xl overflow-hidden ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm">

    {{-- ─── Column 1: Sidebar ─── --}}
    <aside class="w-52 flex-shrink-0 overflow-hidden flex flex-col bg-gray-50 dark:bg-gray-800/80 border-r border-gray-200 dark:border-gray-700">

        {{-- Company header + Sync icon --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-900 flex-shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <x-filament::icon icon="{{ $companyIcon }}" class="w-4 h-4 text-primary-500 flex-shrink-0" />
                <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ $companyLabel }}</span>
            </div>
            <button wire:click="syncNow" wire:loading.attr="disabled"
                class="ml-2 flex-shrink-0 p-1.5 rounded-md text-gray-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition-colors disabled:opacity-40"
                title="Sync Now">
                <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4" wire:loading.class="animate-spin" wire:target="syncNow" />
            </button>
        </div>

        {{-- Compose button --}}
        <div class="px-3 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <button wire:click="openCompose()"
                class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-500 rounded-lg transition-colors shadow-sm">
                <x-filament::icon icon="heroicon-o-pencil-square" class="w-4 h-4" />
                Compose
            </button>
        </div>

        {{-- Accounts (if multiple) --}}
        @if ($accounts->count() > 1)
            <div class="px-4 pt-3 pb-1 text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex-shrink-0">Accounts</div>
            @foreach ($accounts as $account)
                <button wire:click="selectAccount({{ $account->id }})"
                    class="w-full text-left px-4 py-2 text-sm overflow-hidden transition-colors flex-shrink-0
                        {{ $activeAccountId === $account->id ? 'text-primary-600 dark:text-primary-400 font-medium bg-primary-50 dark:bg-primary-900/20' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/50' }}">
                    <div class="truncate">{{ $account->name }}</div>
                </button>
            @endforeach
            <div class="mx-4 my-2 border-t border-gray-200 dark:border-gray-700 flex-shrink-0"></div>
        @elseif ($accounts->count() === 1)
            <div class="px-4 py-2.5 flex-shrink-0 overflow-hidden border-b border-gray-100 dark:border-gray-800">
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">{{ $accounts->first()->name }}</div>
                <div class="text-xs text-gray-400 truncate mt-0.5">{{ $accounts->first()->email }}</div>
            </div>
        @endif

        {{-- Folders --}}
        <nav class="flex-1 overflow-y-auto overflow-x-hidden px-2 py-2 space-y-0.5">
            @foreach ([
                'INBOX'   => ['icon' => 'heroicon-o-inbox',         'label' => 'Inbox'],
                'Sent'    => ['icon' => 'heroicon-o-paper-airplane', 'label' => 'Sent'],
                'Starred' => ['icon' => 'heroicon-o-star',           'label' => 'Starred'],
            ] as $folder => $cfg)
                <button wire:click="selectFolder('{{ $folder }}')"
                    class="w-full flex items-center gap-3 px-3 py-2 text-sm rounded-lg transition-colors
                        {{ $activeFolder === $folder
                            ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 font-semibold'
                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200/60 dark:hover:bg-gray-700/50' }}">
                    <x-filament::icon icon="{{ $cfg['icon'] }}" class="w-4 h-4 flex-shrink-0" />
                    <span class="flex-1 text-left">{{ $cfg['label'] }}</span>
                    @if ($folder === 'INBOX' && $stats['unread'] > 0)
                        <span class="flex-shrink-0 text-xs font-bold text-white bg-danger-500 rounded-full px-1.5 py-0.5 leading-none">{{ $stats['unread'] }}</span>
                    @elseif ($folder === 'Starred' && $stats['starred'] > 0)
                        <span class="flex-shrink-0 text-xs font-medium text-gray-400">{{ $stats['starred'] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </aside>

    {{-- ─── Column 2: Message List ─── --}}
    <div class="w-80 flex-shrink-0 overflow-hidden flex flex-col border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">

        {{-- Search --}}
        <div class="px-3 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <div class="relative">
                <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                <input type="text" wire:model.live.debounce.400ms="searchQuery" placeholder="Search…"
                    class="w-full pl-9 pr-3 py-2 text-sm bg-gray-100 dark:bg-gray-800 border-0 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:outline-none" />
            </div>
        </div>

        {{-- Message rows --}}
        <div class="flex-1 overflow-y-auto overflow-x-hidden divide-y divide-gray-100 dark:divide-gray-800">
            @if (!$activeAccountId)
                <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                    <x-filament::icon icon="heroicon-o-envelope" class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-3" />
                    <p class="text-sm text-gray-400">No account selected</p>
                </div>
            @else
                @forelse ($messages as $msg)
                    <div wire:click="selectMessage({{ $msg->id }})"
                        class="relative cursor-pointer overflow-hidden px-4 py-3.5 transition-colors
                            {{ $selectedMessageId === $msg->id
                                ? 'bg-primary-50 dark:bg-primary-900/20 border-l-[3px] border-primary-500'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-800/50 border-l-[3px] border-transparent' }}">

                        {{-- Unread indicator --}}
                        @if (!$msg->is_read)
                            <span class="absolute right-3 top-4 w-2 h-2 rounded-full bg-primary-500"></span>
                        @endif

                        {{-- Sender + date --}}
                        <div class="flex items-baseline justify-between gap-2 mb-1 min-w-0">
                            <span class="text-sm min-w-0 truncate {{ !$msg->is_read ? 'font-bold text-gray-900 dark:text-white' : 'font-medium text-gray-600 dark:text-gray-300' }}">
                                {{ $msg->from_name ?: ($msg->from_email ?: 'Unknown') }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">
                                {{ $msg->sent_at?->timezone('Asia/Shanghai')->format('M j') ?? '' }}
                            </span>
                        </div>

                        {{-- Subject --}}
                        <div class="text-sm truncate {{ !$msg->is_read ? 'font-medium text-gray-800 dark:text-gray-100' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ $msg->subject ?: '(No Subject)' }}
                        </div>

                        {{-- Preview --}}
                        <div class="flex items-center justify-between gap-2 mt-1 min-w-0">
                            <span class="text-xs text-gray-400 dark:text-gray-500 truncate min-w-0">
                                {{ mb_substr(strip_tags($msg->body_html ?? $msg->body_text ?? ''), 0, 65) }}
                            </span>
                            <div class="flex items-center gap-1 flex-shrink-0">
                                @if ($msg->is_starred)
                                    <x-filament::icon icon="heroicon-s-star" class="w-3.5 h-3.5 text-warning-400" />
                                @endif
                                @if ($msg->has_attachments)
                                    <x-filament::icon icon="heroicon-o-paper-clip" class="w-3.5 h-3.5 text-gray-400" />
                                @endif
                                @if ($msg->ai_summary)
                                    <x-filament::icon icon="heroicon-o-sparkles" class="w-3.5 h-3.5 text-violet-400" />
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                        <x-filament::icon icon="heroicon-o-inbox" class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-3" />
                        <p class="text-sm text-gray-400">No messages in {{ $activeFolder }}</p>
                    </div>
                @endforelse
            @endif
        </div>
    </div>

    {{-- ─── Column 3: Detail / Compose ─── --}}
    <div class="flex-1 min-w-0 flex flex-col overflow-hidden bg-white dark:bg-gray-900">

        {{-- ── Compose ── --}}
        @if ($isComposing)
            <div class="flex flex-col h-full overflow-hidden">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-pencil-square" class="w-5 h-5 text-primary-500" />
                        {{ $replyToId ? 'Reply' : 'New Message' }}
                    </h2>
                    <div class="flex items-center gap-2">
                        <button wire:click="doSendEmail" wire:loading.attr="disabled"
                            class="flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-500 rounded-lg transition-colors shadow-sm disabled:opacity-60">
                            <x-filament::icon icon="heroicon-o-paper-airplane" class="w-4 h-4" />
                            <span wire:loading.remove wire:target="doSendEmail">Send</span>
                            <span wire:loading wire:target="doSendEmail">Sending…</span>
                        </button>
                        <button wire:click="closeCompose"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto overflow-x-hidden px-6 py-5 flex flex-col gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">To</label>
                        <input type="email" wire:model="composeTo" placeholder="recipient@example.com"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent focus:outline-none" />
                        @error('composeTo') <p class="text-sm text-danger-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Subject</label>
                        <input type="text" wire:model="composeSubject" placeholder="Subject line"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent focus:outline-none" />
                        @error('composeSubject') <p class="text-sm text-danger-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-1 flex flex-col min-h-0">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Message</label>
                        <textarea wire:model="composeBody" placeholder="Write your message here…"
                            class="flex-1 w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent focus:outline-none resize-none leading-relaxed min-h-[240px]"></textarea>
                        @error('composeBody') <p class="text-sm text-danger-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

        {{-- ── Email Detail ── --}}
        @elseif ($selectedMessageId && $email)
            <div class="flex flex-col h-full min-h-0 overflow-hidden">

                {{-- Header --}}
                <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">

                    {{-- Subject + action buttons --}}
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white leading-snug break-words min-w-0 flex-1">
                            {{ $email->subject ?: '(No Subject)' }}
                        </h2>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button wire:click="toggleStar({{ $email->id }})"
                                class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                title="{{ $email->is_starred ? 'Unstar' : 'Star' }}">
                                <x-filament::icon
                                    icon="{{ $email->is_starred ? 'heroicon-s-star' : 'heroicon-o-star' }}"
                                    class="w-5 h-5 {{ $email->is_starred ? 'text-warning-400' : 'text-gray-400' }}" />
                            </button>
                            <button wire:click="openCompose({{ $email->id }})"
                                class="flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="w-4 h-4" />
                                Reply
                            </button>
                            <button wire:click="deleteMessage({{ $email->id }})"
                                wire:confirm="Delete this message permanently?"
                                class="p-2 rounded-lg hover:bg-danger-50 dark:hover:bg-danger-900/20 text-gray-400 hover:text-danger-500 transition-colors">
                                <x-filament::icon icon="heroicon-o-trash" class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    {{-- Sender row with avatar --}}
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center text-primary-700 dark:text-primary-300 font-bold text-sm flex-shrink-0 select-none">
                                {{ strtoupper(mb_substr($email->from_name ?: $email->from_email ?: '?', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $email->from_name ?: $email->from_email }}
                                </div>
                                @if ($email->from_name)
                                    <div class="text-xs text-gray-400 dark:text-gray-500 truncate">&lt;{{ $email->from_email }}&gt;</div>
                                @endif
                            </div>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400 flex-shrink-0">
                            {{ $email->sent_at?->timezone('Asia/Shanghai')->format('M j, Y · H:i') }}
                        </span>
                    </div>

                    @if (!empty($email->to_addresses))
                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400 break-words">
                            <span class="font-medium text-gray-700 dark:text-gray-300">To:</span>
                            {{ collect($email->to_addresses)->map(fn($a) => $a['name'] ? "{$a['name']} <{$a['email']}>" : ($a['email'] ?? ''))->filter()->join(', ') }}
                        </div>
                    @endif
                </div>

                {{-- Attachments --}}
                @if ($email->has_attachments && $email->attachments->count() > 0)
                    <div class="flex flex-wrap items-center gap-2 px-6 py-3 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex-shrink-0">
                        <x-filament::icon icon="heroicon-o-paper-clip" class="w-4 h-4 text-gray-400 flex-shrink-0" />
                        @foreach ($email->attachments as $att)
                            <a href="{{ route('email.attachment.download', $att->id) }}"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-3.5 h-3.5 text-gray-400" />
                                {{ $att->filename }}
                                <span class="text-gray-400 text-xs">({{ $att->formatted_size }})</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- AI Summary --}}
                <div class="flex-shrink-0 border-b border-violet-100 dark:border-violet-800/30 bg-violet-50/70 dark:bg-violet-900/10">
                    <div class="flex items-center justify-between px-6 py-2.5">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-4 h-4 text-violet-500" />
                            <span class="text-sm font-semibold text-violet-700 dark:text-violet-300">AI Summary</span>
                            @if ($email->ai_summarized_at)
                                <span class="text-xs text-violet-400">· {{ $email->ai_summarized_at->diffForHumans() }}</span>
                            @endif
                        </div>
                        <button wire:click="aiSummarize" wire:loading.attr="disabled"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-violet-700 dark:text-violet-300 bg-violet-100 dark:bg-violet-800/50 hover:bg-violet-200 dark:hover:bg-violet-800 rounded-lg transition-colors disabled:opacity-50">
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-3.5 h-3.5" wire:loading.class="animate-pulse" wire:target="aiSummarize" />
                            <span wire:loading.remove wire:target="aiSummarize">{{ ($aiSummary || $email->ai_summary) ? 'Re-summarize' : 'Summarize' }}</span>
                            <span wire:loading wire:target="aiSummarize">Analyzing…</span>
                        </button>
                    </div>
                    @if ($aiSummary || $email->ai_summary)
                        <div class="px-6 pb-3 text-sm text-violet-900 dark:text-violet-200 leading-relaxed">
                            {{ $aiSummary ?: $email->ai_summary }}
                        </div>
                    @endif
                </div>

                {{-- Email body --}}
                <div class="flex-1 min-h-0 overflow-y-auto overflow-x-auto px-6 py-5">
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

        {{-- ── Empty State ── --}}
        @else
            <div class="flex-1 flex flex-col items-center justify-center text-center p-12">
                <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                    <x-filament::icon icon="heroicon-o-envelope-open" class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                </div>
                <p class="text-base font-semibold text-gray-500 dark:text-gray-400 mb-1">Select an email to read</p>
                <p class="text-sm text-gray-400 dark:text-gray-500">Choose a message from the list on the left.</p>
            </div>
        @endif
    </div>
</div>

<style>
    .email-body { word-break: break-word; overflow-wrap: break-word; }
    .email-body img { max-width: 100% !important; height: auto !important; }
    .email-body a { color: #2563eb; text-decoration: underline; word-break: break-all; }
    .email-body table { max-width: 100%; border-collapse: collapse; }
    .email-body > table, .email-body > div > table, .email-body > center > table,
    .email-body > center { display: block; overflow-x: auto; max-width: 100%; }
</style>
</x-filament-panels::page>
