<x-filament-panels::page>
    @php
        $data = $this->getLogData();
        $entries = $data['entries'];
        $stats = $data['stats'];
        $availableFiles = $this->getAvailableLogFiles();
    @endphp

    {{-- Top Metrics Overview --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Log Entries</p>
                <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total']) }}</p>
            </div>
            <div class="p-3 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                <x-heroicon-o-document-text class="w-6 h-6" style="width: 24px; height: 24px;" />
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-red-500 uppercase tracking-wider">Errors & Critical</p>
                <p class="text-2xl font-extrabold text-red-600 dark:text-red-400 mt-1">{{ number_format($stats['errors']) }}</p>
            </div>
            <div class="p-3 bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6" style="width: 24px; height: 24px;" />
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-amber-500 uppercase tracking-wider">Warnings</p>
                <p class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 mt-1">{{ number_format($stats['warnings']) }}</p>
            </div>
            <div class="p-3 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg">
                <x-heroicon-o-bell-alert class="w-6 h-6" style="width: 24px; height: 24px;" />
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Log File Size</p>
                <p class="text-2xl font-extrabold text-gray-800 dark:text-gray-200 mt-1">{{ $stats['file_size'] }}</p>
            </div>
            <div class="p-3 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg">
                <x-heroicon-o-circle-stack class="w-6 h-6" style="width: 24px; height: 24px;" />
            </div>
        </div>
    </div>

    {{-- Controls & Filter Bar --}}
    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-6 space-y-4">
        <div class="flex flex-col lg:flex-row gap-3 items-stretch lg:items-center justify-between">
            
            {{-- Search and Level Filter --}}
            <div class="flex flex-col sm:flex-row gap-3 flex-1">
                @if(count($availableFiles) > 1)
                    {{-- File Selector --}}
                    <select wire:model.live="selectedFile"
                            class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-sm rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white font-mono">
                        @foreach($availableFiles as $filename => $path)
                            <option value="{{ $filename }}">{{ $filename }}</option>
                        @endforeach
                    </select>
                @endif

                {{-- Search Input --}}
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4" style="width: 16px; height: 16px;" />
                    </div>
                    <input type="text"
                           wire:model.live.debounce.300ms="search"
                           placeholder="Search logs (message, stack trace, class...)"
                           class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary-500 focus:border-primary-500 dark:text-white">
                </div>

                {{-- Level Dropdown --}}
                <select wire:model.live="level"
                        class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-sm rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white">
                    <option value="all">All Levels</option>
                    <option value="error">Errors & Critical</option>
                    <option value="warning">Warnings</option>
                    <option value="info">Info & Notice</option>
                    <option value="debug">Debug</option>
                </select>

                {{-- Date Filter --}}
                <input type="date"
                       wire:model.live="dateFilter"
                       class="bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 text-sm rounded-lg px-3 py-2 focus:ring-primary-500 focus:border-primary-500 dark:text-white">

                @if($search || $level !== 'all' || $dateFilter)
                    <button wire:click="resetFilters"
                            type="button"
                            class="px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                        Reset Filters
                    </button>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2">
                <button wire:click="$refresh"
                        type="button"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm transition">
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-1.5" style="width: 16px; height: 16px;" />
                    Refresh
                </button>

                <button wire:click="downloadLogs"
                        type="button"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-lg text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 shadow-sm transition">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-1.5" style="width: 16px; height: 16px;" />
                    Download
                </button>

                <button onclick="confirm('Are you sure you want to clear all logs? This cannot be undone.') || event.stopImmediatePropagation()"
                        wire:click="clearLogs"
                        type="button"
                        class="inline-flex items-center px-3 py-2 text-xs font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 shadow-sm transition">
                    <x-heroicon-o-trash class="w-4 h-4 mr-1.5" style="width: 16px; height: 16px;" />
                    Clear Logs
                </button>
            </div>

        </div>
    </div>

    {{-- Log Entries List --}}
    <div class="space-y-3">
        @forelse($entries as $log)
            @php
                $badgeClasses = match($log['level']) {
                    'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 border-red-200 dark:border-red-800',
                    'WARNING' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                    'INFO', 'NOTICE' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600',
                };
            @endphp

            <div x-data="{ open: false, copied: false }" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden transition">
                <div class="p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 cursor-pointer hover:bg-gray-50/50 dark:hover:bg-gray-750"
                     @click="open = !open">
                    
                    <div class="flex items-start sm:items-center gap-3 flex-1 min-w-0">
                        {{-- Level Badge --}}
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeClasses }}">
                            {{ $log['level'] }}
                        </span>

                        {{-- Timestamp & Env --}}
                        <span class="text-xs font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $log['timestamp'] }}
                        </span>
                        
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-mono">
                            {{ $log['env'] }}
                        </span>

                        {{-- Log Message --}}
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate flex-1">
                            {{ $log['message'] }}
                        </p>
                    </div>

                    {{-- Toggle Button --}}
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if(!empty($log['stack']))
                            <button type="button" class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:underline flex items-center">
                                <span x-text="open ? 'Hide Trace' : 'View Trace'"></span>
                                <x-heroicon-o-chevron-down class="w-4 h-4 ml-1 transition-transform" style="width: 16px; height: 16px;" ::class="open ? 'rotate-180' : ''" />
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Stack Trace / Details Accordion --}}
                @if(!empty($log['stack']))
                    <div x-show="open" x-collapse x-cloak class="border-t border-gray-200 dark:border-gray-700 bg-gray-900 text-gray-100 p-4 text-xs font-mono overflow-x-auto relative">
                        <div class="flex justify-between items-center mb-2 pb-2 border-b border-gray-800">
                            <span class="text-gray-400 font-sans text-xs">Stack Trace / Details</span>
                            <button type="button"
                                    @click="navigator.clipboard.writeText(`{{ addslashes($log['message']) }}\n{{ addslashes($log['stack']) }}`); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="px-2.5 py-1 text-xs bg-gray-800 hover:bg-gray-700 text-gray-200 rounded transition flex items-center">
                                <x-heroicon-o-clipboard class="w-3.5 h-3.5 mr-1" style="width: 14px; height: 14px;" />
                                <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                            </button>
                        </div>
                        <pre class="whitespace-pre-wrap break-all leading-relaxed text-gray-300">{{ $log['stack'] }}</pre>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center flex flex-col items-center justify-center">
                <x-heroicon-o-check-circle class="w-12 h-12 text-emerald-500 mb-3" style="width: 48px; height: 48px;" />
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">No Log Entries Found</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">There are no log entries matching your current filter criteria.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
