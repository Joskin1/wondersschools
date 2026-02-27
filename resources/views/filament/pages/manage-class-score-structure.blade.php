<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ── Filter Section ──────────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">Select Class & Term</x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Session --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Academic Session
                    </label>
                    <select
                        wire:model.live="session_id"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="">— Select Session —</option>
                        @foreach($this->sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Term --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Term</label>
                    <select
                        wire:model.live="term_id"
                        @disabled(! $session_id)
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
                    >
                        <option value="">— Select Term —</option>
                        @foreach($this->terms as $term)
                            <option value="{{ $term->id }}">{{ $term->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Class --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                    <select
                        wire:model.live="classroom_id"
                        @disabled(! $term_id)
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
                    >
                        <option value="">— Select Class —</option>
                        @foreach($this->classrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                </div>

            </div>
        </x-filament::section>

        {{-- ── Structure Panel (only when all 3 filters chosen) ─────────────── --}}
        @if($classroom_id && $term_id && $session_id)
            <x-filament::section>

                {{-- Section Heading with status badges --}}
                <x-slot name="heading">
                    <div class="flex flex-wrap items-center gap-3">
                        <span>Score Structure</span>

                        @if($locked)
                            <x-filament::badge color="danger" icon="heroicon-s-lock-closed">Locked</x-filament::badge>
                        @else
                            <x-filament::badge color="success" icon="heroicon-s-lock-open">Editable</x-filament::badge>
                        @endif

                        @if($totalScore > 0)
                            <x-filament::badge
                                color="{{ $totalScore === 100 ? 'success' : ($totalScore > 100 ? 'danger' : 'warning') }}"
                            >
                                Total: {{ $totalScore }} / 100
                            </x-filament::badge>
                        @endif
                    </div>
                </x-slot>

                {{-- Items table --}}
                @if(empty($items))
                    <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-squares-plus class="mx-auto mb-3 h-10 w-10 opacity-40"/>
                        <p>No score heads assigned yet. Add one below to get started.</p>
                    </div>
                @else
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="py-3 px-4 text-left font-semibold text-gray-700 dark:text-gray-300">Score Head</th>
                                    <th class="py-3 px-4 text-center font-semibold text-gray-700 dark:text-gray-300">Default Max</th>
                                    <th class="py-3 px-4 text-center font-semibold text-gray-700 dark:text-gray-300">Override</th>
                                    <th class="py-3 px-4 text-center font-semibold text-gray-700 dark:text-gray-300">Effective</th>
                                    <th class="py-3 px-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($items as $index => $item)
                                    <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors">

                                        <td class="py-3 px-4 font-medium text-gray-800 dark:text-gray-200">
                                            {{ $item['name'] }}
                                        </td>

                                        <td class="py-3 px-4 text-center text-gray-500 dark:text-gray-400">
                                            {{ $item['max_score'] }}
                                        </td>

                                        <td class="py-3 px-4 text-center">
                                            @if(! $locked || auth()->user()?->isSudo())
                                                <input
                                                    type="number"
                                                    wire:model.live="items.{{ $index }}.max_score_override"
                                                    min="1"
                                                    max="{{ $item['max_score'] }}"
                                                    placeholder="—"
                                                    class="w-16 text-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 px-2 py-1 text-xs focus:ring-2 focus:ring-primary-500 focus:outline-none"
                                                >
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">
                                                    {{ $item['max_score_override'] ?? '—' }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="py-3 px-4 text-center font-bold text-gray-900 dark:text-gray-100">
                                            {{ $item['max_score_override'] ?: $item['max_score'] }}
                                        </td>

                                        <td class="py-3 px-4 text-center">
                                            @if(! $locked || auth()->user()?->isSudo())
                                                <button
                                                    wire:click="removeItem({{ $index }})"
                                                    wire:loading.attr="disabled"
                                                    class="text-red-400 hover:text-red-600 dark:text-red-500 dark:hover:text-red-300 transition-colors"
                                                    title="Remove"
                                                >
                                                    <x-heroicon-o-trash class="h-4 w-4"/>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <td colspan="3" class="py-3 px-4 text-right font-semibold text-gray-700 dark:text-gray-300">
                                        Total
                                    </td>
                                    <td class="py-3 px-4 text-center font-bold text-lg {{ $totalScore > 100 ? 'text-red-600 dark:text-red-400' : ($totalScore === 100 ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400') }}">
                                        {{ $totalScore }}
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                {{-- Over-100 warning --}}
                @if($totalScore > 100)
                    <div class="mt-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-300 flex items-start gap-2">
                        <x-heroicon-o-exclamation-triangle class="mt-0.5 h-4 w-4 flex-shrink-0"/>
                        <span>Total exceeds 100. Use overrides or remove a score head before saving.</span>
                    </div>
                @endif

                {{-- Add score head row --}}
                @if(! $locked || auth()->user()?->isSudo())
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <select
                            wire:model="selectedScoreHeadId"
                            class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="">— Select a score head to add —</option>
                            @foreach($this->availableScoreHeads as $sh)
                                <option value="{{ $sh->id }}">{{ $sh->name }} ({{ $sh->max_score }} pts default)</option>
                            @endforeach
                        </select>

                        <x-filament::button
                            wire:click="addItem"
                            color="primary"
                            size="sm"
                            icon="heroicon-o-plus"
                        >
                            Add
                        </x-filament::button>
                    </div>
                @endif

                {{-- Action bar --}}
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">

                    {{-- Lock / Unlock --}}
                    @if($structureId)
                        <x-filament::button
                            wire:click="toggleLock"
                            color="{{ $locked ? 'warning' : 'gray' }}"
                            size="sm"
                            icon="{{ $locked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed' }}"
                        >
                            {{ $locked ? 'Unlock Structure' : 'Lock Structure' }}
                        </x-filament::button>
                    @else
                        <div></div>
                    @endif

                    {{-- Save --}}
                    @if(! $locked || auth()->user()?->isSudo())
                        <x-filament::button
                            wire:click="saveStructure"
                            wire:loading.attr="disabled"
                            color="primary"
                            icon="heroicon-o-check"
                            :disabled="$totalScore > 100 || empty($items)"
                        >
                            <span wire:loading.remove wire:target="saveStructure">Save Structure</span>
                            <span wire:loading wire:target="saveStructure">Saving…</span>
                        </x-filament::button>
                    @endif

                </div>

            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
