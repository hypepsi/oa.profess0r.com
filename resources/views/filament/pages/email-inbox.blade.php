<x-filament-panels::page>
    {{-- =====================================================================
         Email Client — Three-Column Layout
         ===================================================================== --}}

    {{-- Company Tab Bar --}}
    <div class="flex items-center gap-1 mb-4 border-b border-gray-200 dark:border-gray-700 pb-0">
        @foreach ($this->getCompanies() as $key => $label)
            <button
                wire:click="selectCompany('{{ $key }}')"
                class="px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition-colors
                    {{ $activeCompany === $key
                        ? 'border-primary-600 text-primary-600 dark:text-primary-400 dark:border-primary-400 bg-primary-50 dark:bg-primary-900/20'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}"
            >
                <x-filament::icon icon="{{ \App\Models\EmailAccount::companyIcon($key) }}" class="inline w-4 h-4 mr-1 -mt-0.5" />
                {{ $label }}

                {{-- Unread count badge --}}
                @php
                    $unread = \App\Models\EmailMessage::whereHas('account', fn($q) => $q->where('company', $key))
                        ->where('is_read', false)->where('folder', 'INBOX')->count();
                @endphp
                @if ($unread > 0)
                    <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold bg-danger-500 text-white rounded-full">
                        {{ $unread }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Main 3-Column Layout --}}
    <div class="flex gap-0 h-[calc(100vh-14rem)] rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm bg-white dark:bg-gray-900">

        {{-- ── Column 1: Sidebar (accounts + folders) ── --}}
        <aside class="w-52 flex-shrink-0 bg-gray-50 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">

            {{-- Sync button --}}
            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                <button
                    wire:click="syncNow"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors disabled:opacity-50"
                >
                    <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4" wire:loading.class="animate-spin" wire:target="syncNow" />
                    Sync Email
                </button>
            </div>

            {{-- Accounts for company --}}
            @php $accounts = $this->getAccountsForCompany(); @endphp

            @if ($accounts->count() > 1)
                <div class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    Accounts
                </div>
                @foreach ($accounts as $account)
                    <button
                        wire:click="selectAccount({{ $account->id }})"
                        class="w-full text-left px-3 py-2 text-sm transition-colors
                            {{ $activeAccountId === $account->id
                                ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >
                        <div class="truncate font-medium">{{ $account->name }}</div>
                        <div class="truncate text-xs text-gray-400">{{ $account->email }}</div>
                    </button>
                @endforeach
                <div class="h-px bg-gray-200 dark:border-gray-700 mx-3 my-1"></div>
            @elseif ($accounts->count() === 1)
                <div class="px-3 py-2">
                    <div class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $accounts->first()->name }}</div>
                    <div class="text-xs text-gray-400 truncate">{{ $accounts->first()->email }}</div>
                </div>
                <div class="h-px bg-gray-200 dark:border-gray-700 mx-3 my-1"></div>
            @else
                <div class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 italic">
                    No accounts configured.
                </div>
            @endif

            {{-- Folders --}}
            <nav class="flex-1 px-2 py-1 space-y-0.5">
                @foreach ([
                    'INBOX' => ['icon' => 'heroicon-o-inbox', 'label' => 'Inbox'],
                    'Sent'  => ['icon' => 'heroicon-o-paper-airplane', 'label' => 'Sent'],
                    'Starred' => ['icon' => 'heroicon-o-star', 'label' => 'Starred'],
                ] as $folder => $config)
                    <button
                        wire:click="selectFolder('{{ $folder }}')"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-colors
                            {{ $activeFolder === $folder
                                ? 'bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-300 font-medium'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                    >
                        <x-filament::icon icon="{{ $config['icon'] }}" class="w-4 h-4 flex-shrink-0" />
                        <span>{{ $config['label'] }}</span>

                        @if ($folder === 'INBOX' && $activeAccountId)
                            @php
                                $unread = \App\Models\EmailMessage::where('email_account_id', $activeAccountId)
                                    ->where('is_read', false)->where('folder', 'INBOX')->count();
                            @endphp
                            @if ($unread > 0)
                                <span class="ml-auto text-xs font-bold text-white bg-danger-500 rounded-full px-1.5 py-0.5">
                                    {{ $unread }}
                                </span>
                            @endif
                        @endif
                    </button>
                @endforeach
            </nav>

            {{-- Compose button --}}
            <div class="p-3 border-t border-gray-200 dark:border-gray-700">
                <button
                    wire:click="openCompose()"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-white bg-success-600 hover:bg-success-700 rounded-lg transition-colors"
                >
                    <x-filament::icon icon="heroicon-o-pencil-square" class="w-4 h-4" />
                    Compose
                </button>
            </div>
        </aside>

        {{-- ── Column 2: Message List ── --}}
        <div class="w-72 flex-shrink-0 flex flex-col border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">

            {{-- Search bar --}}
            <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                <div class="relative">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" />
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="searchQuery"
                        placeholder="Search emails..."
                        class="w-full pl-8 pr-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-800 border-0 rounded-lg text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:outline-none"
                    />
                </div>
            </div>

            {{-- Message list --}}
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                @if (!$activeAccountId)
                    <div class="p-6 text-center text-sm text-gray-400">
                        <x-filament::icon icon="heroicon-o-envelope" class="w-8 h-8 mx-auto mb-2 opacity-40" />
                        No account selected.
                    </div>
                @else
                    @php $messages = $this->getMessages(); @endphp

                    @forelse ($messages as $message)
                        <div
                            wire:click="selectMessage({{ $message->id }})"
                            class="cursor-pointer px-4 py-3 transition-colors relative
                                {{ $selectedMessageId === $message->id
                                    ? 'bg-primary-50 dark:bg-primary-900/30'
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}"
                        >
                            {{-- Unread indicator --}}
                            @if (!$message->is_read)
                                <span class="absolute left-1.5 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-primary-500"></span>
                            @endif

                            <div class="pl-2">
                                {{-- From + Time --}}
                                <div class="flex items-start justify-between gap-1 mb-0.5">
                                    <span class="text-sm truncate {{ !$message->is_read ? 'font-semibold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-300' }}">
                                        {{ $message->from_name ?: $message->from_email ?: 'Unknown' }}
                                    </span>
                                    <span class="text-xs text-gray-400 flex-shrink-0 ml-1">
                                        {{ $message->sent_at?->timezone('Asia/Shanghai')->format('M j') ?? '' }}
                                    </span>
                                </div>

                                {{-- Subject --}}
                                <div class="text-sm truncate {{ !$message->is_read ? 'font-medium text-gray-800 dark:text-gray-200' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ $message->subject ?: '(No Subject)' }}
                                </div>

                                {{-- Preview --}}
                                <div class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">
                                    {{ $message->preview }}
                                </div>

                                {{-- Indicators --}}
                                <div class="flex items-center gap-2 mt-1">
                                    @if ($message->is_starred)
                                        <x-filament::icon icon="heroicon-s-star" class="w-3 h-3 text-warning-500" />
                                    @endif
                                    @if ($message->has_attachments)
                                        <x-filament::icon icon="heroicon-o-paper-clip" class="w-3 h-3 text-gray-400" />
                                    @endif
                                    @if ($message->ai_summary)
                                        <x-filament::icon icon="heroicon-o-sparkles" class="w-3 h-3 text-violet-400" title="AI Summary available" />
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-400">
                            <x-filament::icon icon="heroicon-o-inbox" class="w-10 h-10 mx-auto mb-3 opacity-30" />
                            <p>No messages in {{ $activeFolder }}.</p>
                        </div>
                    @endforelse
                @endif
            </div>
        </div>

        {{-- ── Column 3: Message Detail or Compose ── --}}
        <div class="flex-1 flex flex-col overflow-hidden bg-white dark:bg-gray-900">

            {{-- ---- Compose Panel ---- --}}
            @if ($isComposing)
                <div class="flex-1 flex flex-col p-6 overflow-y-auto">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-pencil-square" class="w-5 h-5 text-primary-500" />
                            {{ $replyToId ? 'Reply' : 'New Message' }}
                        </h2>
                        <button wire:click="closeCompose" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded">
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="space-y-4 flex-1">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                            <input type="email" wire:model="composeTo" placeholder="recipient@example.com"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 focus:outline-none" />
                            @error('composeTo') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                            <input type="text" wire:model="composeSubject" placeholder="Subject"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 focus:outline-none" />
                            @error('composeSubject') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                            <textarea
                                wire:model="composeBody"
                                rows="14"
                                placeholder="Write your message..."
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 focus:outline-none resize-none"
                            ></textarea>
                            @error('composeBody') <p class="text-xs text-danger-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            wire:click="sendEmail"
                            wire:loading.attr="disabled"
                            class="flex items-center gap-2 px-5 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors disabled:opacity-50"
                        >
                            <x-filament::icon icon="heroicon-o-paper-airplane" class="w-4 h-4" />
                            <span wire:loading.remove wire:target="sendEmail">Send</span>
                            <span wire:loading wire:target="sendEmail">Sending...</span>
                        </button>
                        <button wire:click="closeCompose" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>

            {{-- ---- Message Detail Panel ---- --}}
            @elseif ($selectedMessageId && ($email = $this->getSelectedMessage()))
                <div class="flex-1 overflow-y-auto">

                    {{-- Email header --}}
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white leading-snug mb-1">
                                    {{ $email->subject ?: '(No Subject)' }}
                                </h2>
                                <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                    <span>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">From:</span>
                                        {{ $email->from_display }}
                                    </span>
                                    <span class="text-gray-300 dark:text-gray-600">•</span>
                                    <span>{{ $email->sent_at?->timezone('Asia/Shanghai')->format('Y-m-d H:i') }}</span>
                                </div>
                                @if (!empty($email->to_addresses))
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                        <span class="font-medium text-gray-600 dark:text-gray-400">To:</span>
                                        {{ collect($email->to_addresses)->pluck('email')->join(', ') }}
                                    </div>
                                @endif
                            </div>

                            {{-- Action buttons --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button
                                    wire:click="toggleStar({{ $email->id }})"
                                    class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                    title="{{ $email->is_starred ? 'Unstar' : 'Star' }}"
                                >
                                    <x-filament::icon
                                        icon="{{ $email->is_starred ? 'heroicon-s-star' : 'heroicon-o-star' }}"
                                        class="w-5 h-5 {{ $email->is_starred ? 'text-warning-500' : 'text-gray-400' }}"
                                    />
                                </button>
                                <button
                                    wire:click="openCompose({{ $email->id }})"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                >
                                    <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="w-4 h-4" />
                                    Reply
                                </button>
                                <button
                                    wire:click="deleteMessage({{ $email->id }})"
                                    wire:confirm="Delete this message?"
                                    class="p-1.5 rounded-lg hover:bg-danger-50 dark:hover:bg-danger-900/20 transition-colors"
                                    title="Delete"
                                >
                                    <x-filament::icon icon="heroicon-o-trash" class="w-5 h-5 text-danger-500" />
                                </button>
                            </div>
                        </div>

                        {{-- Attachments --}}
                        @if ($email->has_attachments && $email->attachments->count() > 0)
                            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                                @foreach ($email->attachments as $att)
                                    <a
                                        href="{{ route('email.attachment.download', $att->id) }}"
                                        class="flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        <x-filament::icon icon="heroicon-o-paper-clip" class="w-3.5 h-3.5" />
                                        {{ $att->filename }}
                                        <span class="text-gray-400">({{ $att->formatted_size }})</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- AI Summary Panel --}}
                    <div class="px-6 py-3 bg-violet-50 dark:bg-violet-900/10 border-b border-violet-200 dark:border-violet-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-filament::icon icon="heroicon-o-sparkles" class="w-4 h-4 text-violet-500" />
                                <span class="text-sm font-medium text-violet-700 dark:text-violet-300">AI Summary</span>
                                @if ($email->ai_summarized_at)
                                    <span class="text-xs text-violet-400">({{ $email->ai_summarized_at->diffForHumans() }})</span>
                                @endif
                            </div>
                            <button
                                wire:click="aiSummarize"
                                wire:loading.attr="disabled"
                                class="flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-violet-700 dark:text-violet-300 bg-violet-100 dark:bg-violet-800/40 hover:bg-violet-200 dark:hover:bg-violet-800/60 rounded-lg transition-colors disabled:opacity-50"
                            >
                                <x-filament::icon icon="heroicon-o-sparkles" class="w-3.5 h-3.5" wire:loading.class="animate-pulse" wire:target="aiSummarize" />
                                <span wire:loading.remove wire:target="aiSummarize">{{ $aiSummary || $email->ai_summary ? 'Re-summarize' : 'Summarize with AI' }}</span>
                                <span wire:loading wire:target="aiSummarize">Analyzing...</span>
                            </button>
                        </div>

                        @if ($aiSummary || $email->ai_summary)
                            <div class="mt-3 text-sm text-violet-800 dark:text-violet-200 leading-relaxed whitespace-pre-wrap">
                                {{ $aiSummary ?: $email->ai_summary }}
                            </div>
                        @endif
                    </div>

                    {{-- Email Body --}}
                    <div class="px-6 py-4">
                        @if ($email->body_html)
                            <div class="email-body text-sm text-gray-800 dark:text-gray-200 leading-relaxed max-w-none">
                                {!! $email->body_html !!}
                            </div>
                        @elseif ($email->body_text)
                            <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-sans leading-relaxed">{{ $email->body_text }}</pre>
                        @else
                            <p class="text-sm text-gray-400 italic">No content.</p>
                        @endif
                    </div>
                </div>

            {{-- ---- Empty State ---- --}}
            @else
                <div class="flex-1 flex flex-col items-center justify-center text-center p-12">
                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                        <x-filament::icon icon="heroicon-o-envelope-open" class="w-8 h-8 text-gray-300 dark:text-gray-600" />
                    </div>
                    <h3 class="text-base font-medium text-gray-600 dark:text-gray-400 mb-1">Select an email to read</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Choose a message from the list on the left.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Email body scoped styles --}}
    <style>
        .email-body img { max-width: 100%; height: auto; }
        .email-body a { color: #2563eb; text-decoration: underline; }
        .email-body table { border-collapse: collapse; max-width: 100%; overflow-x: auto; display: block; }
        .email-body td, .email-body th { padding: 4px 8px; }
    </style>
</x-filament-panels::page>
