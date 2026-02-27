<x-filament-panels::page>
    <div class="space-y-5">

        {{-- ── Step 1: Filter ────────────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-funnel class="h-4 w-4 text-primary-500"/>
                    Select Class & Term
                </div>
            </x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Academic Session
                    </label>
                    <select wire:model.live="session_id"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">— Select Session —</option>
                        @foreach($this->sessions as $session)
                            <option value="{{ $session->id }}" @selected($session_id == $session->id)>{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Term
                    </label>
                    <select wire:model.live="term_id"
                        @disabled(!$session_id)
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        <option value="">— Select Term —</option>
                        @foreach($this->terms as $term)
                            <option value="{{ $term->id }}" @selected($term_id == $term->id)>{{ $term->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Class
                    </label>
                    <select wire:model.live="classroom_id"
                        @disabled(!$term_id)
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        <option value="">— Select Class —</option>
                        @foreach($this->classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected($classroom_id == $classroom->id)>{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>

        {{-- ── Step 2: Structure (only after class is chosen) ──────────────── --}}
        @if($classroom_id && $term_id && $session_id)

            @php
                $barColor = $totalScore === 100 ? 'bg-green-500' : ($totalScore > 100 ? 'bg-red-500' : 'bg-primary-500');
                $barWidth  = min($totalScore, 100);
            @endphp

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">

                {{-- Card header --}}
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Score Structure</span>

                        @if($locked)
                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 dark:bg-red-900/30 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:text-red-300">
                                <x-heroicon-s-lock-closed class="h-3 w-3"/> Locked
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/30 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:text-green-300">
                                <x-heroicon-s-lock-open class="h-3 w-3"/> Editable
                            </span>
                        @endif
                    </div>

                    <div class="flex items-baseline gap-1
                        {{ $totalScore > 100 ? 'text-red-600 dark:text-red-400' : ($totalScore === 100 ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400') }}">
                        <span class="text-2xl font-bold tabular-nums leading-none">{{ $totalScore }}</span>
                        <span class="text-sm font-normal">/ 100</span>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="h-1.5 w-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-full transition-all duration-300 {{ $barColor }}" style="width: {{ $barWidth }}%"></div>
                </div>

                <div class="p-5 space-y-4">

                    {{-- Items table --}}
                    @if(empty($items))
                        <div class="flex flex-col items-center justify-center py-10 text-center">
                            <x-heroicon-o-squares-plus class="h-10 w-10 text-gray-300 dark:text-gray-600 mb-2"/>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No score heads added yet</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Use the selector below to build this class's grading structure</p>
                        </div>
                    @else
                        <div class="overflow-hidden rounded-lg border border-gray-100 dark:border-gray-800">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-800/60 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        <th class="py-2.5 px-4 text-left font-semibold w-8">#</th>
                                        <th class="py-2.5 px-4 text-left font-semibold">Score Head</th>
                                        <th class="py-2.5 px-4 text-center font-semibold">Default</th>
                                        <th class="py-2.5 px-4 text-center font-semibold">Override</th>
                                        <th class="py-2.5 px-4 text-center font-semibold">Effective</th>
                                        <th class="py-2.5 px-2 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                    @foreach($items as $index => $item)
                                        @php $effective = (int)($item['max_score_override'] ?: $item['max_score']); @endphp
                                        <tr class="group bg-white dark:bg-gray-900 hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">

                                            <td class="py-3 px-4 text-xs text-gray-400 dark:text-gray-600">{{ $index + 1 }}</td>

                                            <td class="py-3 px-4 font-medium text-gray-800 dark:text-gray-100">
                                                {{ $item['name'] }}
                                            </td>

                                            <td class="py-3 px-4 text-center text-gray-500 dark:text-gray-400 tabular-nums">
                                                {{ $item['max_score'] }}
                                            </td>

                                            <td class="py-3 px-4 text-center">
                                                @if(! $locked || auth()->user()?->isSudo())
                                                    <input
                                                        type="number"
                                                        wire:model.live="items.{{ $index }}.max_score_override"
                                                        min="1"
                                                        max="{{ $item['max_score'] }}"
                                                        placeholder="{{ $item['max_score'] }}"
                                                        class="w-16 text-center rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-2 py-1 text-xs focus:ring-2 focus:ring-primary-400 focus:border-primary-400 focus:outline-none transition"
                                                    >
                                                @else
                                                    <span class="text-xs text-gray-400 dark:text-gray-600">{{ $item['max_score_override'] ?? '—' }}</span>
                                                @endif
                                            </td>

                                            <td class="py-3 px-4 text-center">
                                                <span class="inline-block font-bold tabular-nums px-2.5 py-0.5 rounded-full text-xs
                                                    {{ $item['max_score_override'] ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}">
                                                    {{ $effective }}
                                                </span>
                                            </td>

                                            <td class="py-3 px-2 text-center">
                                                @if(! $locked || auth()->user()?->isSudo())
                                                    <button
                                                        wire:click="removeItem({{ $index }})"
                                                        class="opacity-0 group-hover:opacity-100 rounded-md p-1 text-gray-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all"
                                                        title="Remove {{ $item['name'] }}"
                                                    >
                                                        <x-heroicon-o-x-mark class="h-4 w-4"/>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Validation banners --}}
                    @if($totalScore > 100)
                        <div class="flex items-start gap-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                            <x-heroicon-o-exclamation-circle class="mt-0.5 h-4 w-4 flex-shrink-0"/>
                            <span>Total exceeds 100. Use overrides or remove a score head before saving.</span>
                        </div>
                    @elseif($totalScore > 0 && $totalScore < 100)
                        <div class="flex items-start gap-3 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-700 dark:text-amber-300">
                            <x-heroicon-o-information-circle class="mt-0.5 h-4 w-4 flex-shrink-0"/>
                            <span>Total is {{ $totalScore }}/100. Add more score heads or adjust overrides to reach 100.</span>
                        </div>
                    @endif

                    {{-- Add score head section --}}
                    @if(! $locked || auth()->user()?->isSudo())
                        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50/60 dark:bg-gray-800/20 p-4 space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Add Score Head</p>
                            <div class="flex flex-wrap items-center gap-3">
                                <select wire:model="selectedScoreHeadId"
                                    class="flex-1 min-w-0 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition">
                                    <option value="">— Choose a score head —</option>
                                    @foreach($this->availableScoreHeads as $sh)
                                        <option value="{{ $sh->id }}">{{ $sh->name }} (default: {{ $sh->max_score }} pts)</option>
                                    @endforeach
                                </select>
                                <x-filament::button wire:click="addItem" wire:loading.attr="disabled" color="primary" size="sm" icon="heroicon-o-plus">
                                    Add
                                </x-filament::button>
                            </div>
                            @if($this->availableScoreHeads->isEmpty())
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    All active score heads have been added. Create more in <strong>Results → Score Heads</strong>.
                                </p>
                            @endif
                        </div>
                    @endif

                    {{-- Action row --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">

                        @if($structureId)
                            <x-filament::button
                                wire:click="toggleLock"
                                wire:loading.attr="disabled"
                                color="{{ $locked ? 'warning' : 'gray' }}"
                                size="sm"
                                icon="{{ $locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed' }}"
                            >
                                {{ $locked ? 'Unlock' : 'Lock Structure' }}
                            </x-filament::button>
                        @else
                            <div></div>
                        @endif

                        @if(! $locked || auth()->user()?->isSudo())
                            <x-filament::button
                                wire:click="saveStructure"
                                wire:loading.attr="disabled"
                                color="{{ $totalScore === 100 ? 'success' : 'primary' }}"
                                icon="heroicon-o-check"
                                :disabled="$totalScore > 100 || empty($items)"
                            >
                                <span wire:loading.remove wire:target="saveStructure">Save Structure</span>
                                <span wire:loading wire:target="saveStructure">Saving…</span>
                            </x-filament::button>
                        @endif

                    </div>

                </div>
            </div>

        @else
            {{-- Placeholder when filters incomplete --}}
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/20 py-16 text-center">
                <x-heroicon-o-academic-cap class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3"/>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Select a session, term, and class above</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">The grading structure for that combination will appear here</p>
            </div>
        @endif

    </div>
</x-filament-panels::page>
