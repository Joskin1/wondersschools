<x-filament-panels::page>
    @php
        $data = $this->getLogData();
        $entries = $data['entries'];
        $stats = $data['stats'];
        $availableFiles = $this->getAvailableLogFiles();
    @endphp

    {{-- Metrics Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        
        <x-filament::section>
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-gray-500, #6b7280);">
                        Total Log Entries
                    </div>
                    <div style="font-size: 1.75rem; font-weight: 800; margin-top: 0.25rem;">
                        {{ number_format($stats['total']) }}
                    </div>
                </div>
                <div style="padding: 0.75rem; border-radius: 0.5rem; background-color: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                    <x-heroicon-o-document-text style="width: 28px; height: 28px;" />
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #ef4444;">
                        Errors & Critical
                    </div>
                    <div style="font-size: 1.75rem; font-weight: 800; color: #ef4444; margin-top: 0.25rem;">
                        {{ number_format($stats['errors']) }}
                    </div>
                </div>
                <div style="padding: 0.75rem; border-radius: 0.5rem; background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">
                    <x-heroicon-o-exclamation-triangle style="width: 28px; height: 28px;" />
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #f59e0b;">
                        Warnings
                    </div>
                    <div style="font-size: 1.75rem; font-weight: 800; color: #f59e0b; margin-top: 0.25rem;">
                        {{ number_format($stats['warnings']) }}
                    </div>
                </div>
                <div style="padding: 0.75rem; border-radius: 0.5rem; background-color: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <x-heroicon-o-bell-alert style="width: 28px; height: 28px;" />
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-gray-500, #6b7280);">
                        Log File Size
                    </div>
                    <div style="font-size: 1.75rem; font-weight: 800; margin-top: 0.25rem;">
                        {{ $stats['file_size'] }}
                    </div>
                </div>
                <div style="padding: 0.75rem; border-radius: 0.5rem; background-color: rgba(107, 114, 128, 0.1); color: #9ca3af;">
                    <x-heroicon-o-circle-stack style="width: 28px; height: 28px;" />
                </div>
            </div>
        </x-filament::section>

    </div>

    {{-- Controls & Filter Bar --}}
    <x-filament::section style="margin-bottom: 1.5rem;">
        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;">
            
            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; flex: 1; min-width: 280px;">
                @if(count($availableFiles) > 1)
                    <div style="min-width: 160px;">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="selectedFile">
                                @foreach($availableFiles as $filename => $path)
                                    <option value="{{ $filename }}">{{ $filename }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                @endif

                <div style="flex: 1; min-width: 220px;">
                    <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                        <x-filament::input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search logs (message, trace, class...)"
                        />
                    </x-filament::input.wrapper>
                </div>

                <div style="min-width: 140px;">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="level">
                            <option value="all">All Levels</option>
                            <option value="error">Errors & Critical</option>
                            <option value="warning">Warnings</option>
                            <option value="info">Info & Notice</option>
                            <option value="debug">Debug</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>

                <div style="min-width: 140px;">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="dateFilter"
                        />
                    </x-filament::input.wrapper>
                </div>

                @if($search || $level !== 'all' || $dateFilter)
                    <x-filament::button color="gray" wire:click="resetFilters" size="sm">
                        Reset Filters
                    </x-filament::button>
                @endif
            </div>

            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <x-filament::button wire:click="$refresh" color="gray" icon="heroicon-m-arrow-path" size="sm">
                    Refresh
                </x-filament::button>

                <x-filament::button wire:click="downloadLogs" color="gray" icon="heroicon-m-arrow-down-tray" size="sm">
                    Download
                </x-filament::button>

                <x-filament::button
                    color="danger"
                    icon="heroicon-m-trash"
                    size="sm"
                    onclick="confirm('Are you sure you want to clear all logs? This cannot be undone.') || event.stopImmediatePropagation()"
                    wire:click="clearLogs"
                >
                    Clear Logs
                </x-filament::button>
            </div>

        </div>
    </x-filament::section>

    {{-- Log Entries List --}}
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @forelse($entries as $log)
            @php
                $badgeColor = match($log['level']) {
                    'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'danger',
                    'WARNING' => 'warning',
                    'INFO', 'NOTICE' => 'info',
                    default => 'gray',
                };
            @endphp

            <div x-data="{ open: false, copied: false }" style="border: 1px solid rgba(255,255,255,0.08); border-radius: 0.75rem; background-color: var(--color-gray-900, #111827); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">
                <div style="padding: 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; cursor: pointer;"
                     @click="open = !open">
                    
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1; min-width: 250px;">
                        <x-filament::badge :color="$badgeColor" size="sm">
                            {{ $log['level'] }}
                        </x-filament::badge>

                        <span style="font-size: 0.8125rem; font-family: monospace; opacity: 0.7; white-space: nowrap;">
                            {{ $log['timestamp'] }}
                        </span>

                        <span style="font-size: 0.75rem; font-family: monospace; padding: 0.15rem 0.4rem; border-radius: 0.25rem; background: rgba(255,255,255,0.06); opacity: 0.8;">
                            {{ $log['env'] }}
                        </span>

                        <span style="font-size: 0.875rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; min-width: 0;">
                            {{ $log['message'] }}
                        </span>
                    </div>

                    @if(!empty($log['stack']))
                        <div style="flex-shrink: 0; display: flex; align-items: center; gap: 0.25rem; font-size: 0.8125rem; font-weight: 500; color: #3b82f6;">
                            <span x-text="open ? 'Hide Trace' : 'View Trace'"></span>
                            <x-heroicon-m-chevron-down style="width: 16px; height: 16px; transition: transform 0.2s;" ::style="open ? 'transform: rotate(180deg);' : ''" />
                        </div>
                    @endif
                </div>

                @if(!empty($log['stack']))
                    <div x-show="open" x-cloak style="border-top: 1px solid rgba(255,255,255,0.08); background-color: #0d1117; padding: 1rem; font-family: monospace; font-size: 0.8125rem; overflow-x: auto;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.08);">
                            <span style="opacity: 0.6; font-size: 0.75rem;">Stack Trace / Exception Context</span>
                            <x-filament::button
                                size="xs"
                                color="gray"
                                icon="heroicon-m-clipboard"
                                type="button"
                                @click="navigator.clipboard.writeText(`{{ addslashes($log['message']) }}\n{{ addslashes($log['stack']) }}`); copied = true; setTimeout(() => copied = false, 2000)"
                            >
                                <span x-text="copied ? 'Copied!' : 'Copy Trace'"></span>
                            </x-filament::button>
                        </div>
                        <pre style="white-space: pre-wrap; word-break: break-all; line-height: 1.6; color: #e6edf3; margin: 0;">{{ $log['stack'] }}</pre>
                    </div>
                @endif
            </div>
        @empty
            <x-filament::section>
                <div style="padding: 2.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <x-heroicon-o-check-circle style="width: 56px; height: 56px; color: #10b981; margin-bottom: 0.75rem;" />
                    <h3 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.25rem;">No Log Entries Found</h3>
                    <p style="font-size: 0.875rem; opacity: 0.7; max-width: 400px;">
                        There are no log entries recorded matching your active filters.
                    </p>
                </div>
            </x-filament::section>
        @endforelse
    </div>
</x-filament-panels::page>
