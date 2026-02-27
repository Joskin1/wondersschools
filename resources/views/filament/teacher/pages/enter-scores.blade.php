<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ── Filter Section ──────────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">Select Class & Subject</x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Session --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Session</label>
                    <select
                        wire:model.live="session_id"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="">— Session —</option>
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
                        <option value="">— Term —</option>
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
                        <option value="">— Class —</option>
                        @foreach($this->authorizedClassrooms as $classroom)
                            <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Subject --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                    <select
                        wire:model.live="subject_id"
                        @disabled(! $classroom_id)
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
                    >
                        <option value="">— Subject —</option>
                        @foreach($this->authorizedSubjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            {{-- Loading indicator --}}
            <div wire:loading wire:target="updatedSubjectId" class="mt-3 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                <svg class="animate-spin h-4 w-4 text-primary-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Loading scores…
            </div>
        </x-filament::section>

        {{-- ── Score Entry Section ─────────────────────────────────────────── --}}
        @if($loaded)

            {{-- No structure configured --}}
            @if(! $structureExists)
                <x-filament::section>
                    <div class="py-10 text-center">
                        <x-heroicon-o-exclamation-triangle class="mx-auto mb-3 h-12 w-12 text-yellow-400"/>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">No score structure configured for this class and term.</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Ask an administrator to set up the score structure before you can enter scores.
                        </p>
                    </div>
                </x-filament::section>

            {{-- No enrolled students --}}
            @elseif(empty($students))
                <x-filament::section>
                    <div class="py-10 text-center">
                        <x-heroicon-o-users class="mx-auto mb-3 h-12 w-12 text-gray-300 dark:text-gray-600"/>
                        <p class="text-gray-500 dark:text-gray-400">No students are enrolled in this class for the selected session.</p>
                    </div>
                </x-filament::section>

            {{-- Score entry table --}}
            @else
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex flex-wrap items-center gap-3">
                            <span>Score Entry</span>
                            <x-filament::badge color="gray">{{ count($students) }} students</x-filament::badge>
                            <x-filament::badge color="info">{{ count($scoreHeads) }} columns</x-filament::badge>
                        </div>
                    </x-slot>

                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm border-collapse">

                            {{-- Table header --}}
                            <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0 z-10">
                                <tr>
                                    <th class="py-3 px-4 text-left font-semibold text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 sticky left-0 bg-gray-50 dark:bg-gray-800 min-w-[180px]">
                                        Student
                                    </th>
                                    @foreach($scoreHeads as $sh)
                                        <th class="py-3 px-3 text-center font-semibold text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 min-w-[90px]">
                                            {{ $sh['name'] }}
                                            <div class="text-xs font-normal text-gray-400 dark:text-gray-500">
                                                max {{ $sh['effective_max'] }}
                                            </div>
                                        </th>
                                    @endforeach
                                    <th class="py-3 px-3 text-center font-semibold text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 min-w-[70px]">
                                        Total
                                    </th>
                                </tr>
                            </thead>

                            {{-- Table body --}}
                            <tbody>
                                @foreach($students as $student)
                                    @php
                                        $studentTotal = collect($scoreHeads)->sum(
                                            fn($sh) => (float) ($scores[$student['id']][$sh['id']] ?? 0)
                                        );
                                        $maxTotal = collect($scoreHeads)->sum(fn($sh) => $sh['effective_max']);
                                    @endphp
                                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-primary-50/30 dark:hover:bg-primary-900/10 transition-colors">

                                        {{-- Student name --}}
                                        <td class="py-2 px-4 font-medium text-gray-800 dark:text-gray-200 border-r border-gray-200 dark:border-gray-700 sticky left-0 bg-white dark:bg-gray-900 hover:bg-primary-50/30 dark:hover:bg-primary-900/10">
                                            {{ $student['full_name'] }}
                                        </td>

                                        {{-- Score inputs --}}
                                        @foreach($scoreHeads as $sh)
                                            @php $currentVal = $scores[$student['id']][$sh['id']] ?? ''; @endphp
                                            <td class="py-1 px-2 border-r border-gray-100 dark:border-gray-800">
                                                <input
                                                    type="number"
                                                    wire:model.lazy="scores.{{ $student['id'] }}.{{ $sh['id'] }}"
                                                    min="0"
                                                    max="{{ $sh['effective_max'] }}"
                                                    step="0.5"
                                                    placeholder="—"
                                                    class="w-full text-center rounded-md border px-2 py-1.5 text-sm transition-colors
                                                        focus:outline-none focus:ring-2 focus:ring-primary-400
                                                        bg-transparent
                                                        {{ $currentVal !== '' && (float)$currentVal > $sh['effective_max']
                                                            ? 'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300'
                                                            : 'border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:border-primary-300 dark:hover:border-primary-600' }}"
                                                >
                                            </td>
                                        @endforeach

                                        {{-- Row total --}}
                                        <td class="py-2 px-3 text-center">
                                            @if($studentTotal > 0)
                                                <span class="font-bold text-sm {{ $studentTotal === $maxTotal ? 'text-green-600 dark:text-green-400' : 'text-primary-600 dark:text-primary-400' }}">
                                                    {{ number_format($studentTotal, 1) }}
                                                </span>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600">—</span>
                                            @endif
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>

                    {{-- Save action --}}
                    <div class="mt-6 flex items-center justify-between">
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Leave a cell blank to clear that score. Scores are saved per student per score head.
                        </p>

                        <x-filament::button
                            wire:click="saveScores"
                            wire:loading.attr="disabled"
                            color="primary"
                            size="lg"
                            icon="heroicon-o-check"
                        >
                            <span wire:loading.remove wire:target="saveScores">Save All Scores</span>
                            <span wire:loading wire:target="saveScores">Saving…</span>
                        </x-filament::button>
                    </div>

                </x-filament::section>
            @endif

        @endif

    </div>
</x-filament-panels::page>
