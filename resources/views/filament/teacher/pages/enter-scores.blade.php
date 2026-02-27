<x-filament-panels::page>
    <div class="space-y-5">

        {{-- ── Filter Section ─────────────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-funnel class="h-4 w-4 text-primary-500"/>
                    Select Class &amp; Subject
                </div>
            </x-slot>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Session --}}
                <div class="space-y-1">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Academic Session
                    </label>
                    <select
                        wire:model.live="session_id"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">— Select Session —</option>
                        @foreach($this->sessions as $session)
                            <option value="{{ $session->id }}" @selected($session_id == $session->id)>{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Term --}}
                <div class="space-y-1">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Term
                    </label>
                    <select
                        wire:model.live="term_id"
                        @disabled(! $session_id)
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <option value="">— Select Term —</option>
                        @foreach($this->terms as $term)
                            <option value="{{ $term->id }}" @selected($term_id == $term->id)>{{ $term->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Class --}}
                <div class="space-y-1">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Class
                    </label>
                    <select
                        wire:model.live="classroom_id"
                        @disabled(! $term_id)
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <option value="">— Select Class —</option>
                        @foreach($this->authorizedClassrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected($classroom_id == $classroom->id)>{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Subject --}}
                <div class="space-y-1">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Subject
                    </label>
                    <select
                        wire:model.live="subject_id"
                        @disabled(! $classroom_id)
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm shadow-sm transition focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <option value="">— Select Subject —</option>
                        @foreach($this->authorizedSubjects as $subject)
                            <option value="{{ $subject->id }}" @selected($subject_id == $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            {{-- Loading indicator --}}
            <div wire:loading wire:target="updatedSubjectId,updatedClassroomId" class="mt-3 flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500">
                <svg class="animate-spin h-4 w-4 text-primary-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Loading scores…
            </div>
        </x-filament::section>

        {{-- ── Score Entry Section ───────────────────────────────────────────── --}}
        @if($loaded)

            {{-- No structure configured --}}
            @if(! $structureExists)
                <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/10 py-16 text-center">
                    <x-heroicon-o-exclamation-triangle class="h-12 w-12 text-amber-400 mb-3"/>
                    <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">No score structure for this class &amp; term</p>
                    <p class="mt-1 text-xs text-amber-600/70 dark:text-amber-400/60">
                        Ask an administrator to set up the score structure before entering scores.
                    </p>
                </div>

            {{-- No enrolled students --}}
            @elseif(empty($students))
                <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/20 py-16 text-center">
                    <x-heroicon-o-users class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3"/>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No students enrolled in this class</p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Students must be enrolled before scores can be entered.</p>
                </div>

            {{-- Score entry table --}}
            @else
                @php
                    $maxTotal = collect($scoreHeads)->sum(fn($sh) => $sh['effective_max']);
                @endphp

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">

                    {{-- Card header --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">Score Entry</span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">
                                <x-heroicon-s-users class="h-3 w-3"/>
                                {{ count($students) }} {{ Str::plural('student', count($students)) }}
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 dark:bg-primary-900/20 px-2.5 py-0.5 text-xs font-medium text-primary-700 dark:text-primary-300">
                                <x-heroicon-s-squares-2x2 class="h-3 w-3"/>
                                {{ count($scoreHeads) }} {{ Str::plural('column', count($scoreHeads)) }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            Max total: <strong class="text-gray-600 dark:text-gray-300">{{ $maxTotal }} pts</strong>
                        </span>
                    </div>

                    {{-- Score table --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-collapse">

                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/70 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                                    <th class="py-3 px-4 text-left font-semibold sticky left-0 bg-gray-50 dark:bg-gray-800/70 min-w-[180px] border-r border-gray-100 dark:border-gray-800 z-10">
                                        Student
                                    </th>
                                    @foreach($scoreHeads as $sh)
                                        <th class="py-3 px-3 text-center font-semibold min-w-[90px]">
                                            <div>{{ $sh['name'] }}</div>
                                            <div class="text-[10px] font-normal text-gray-400 dark:text-gray-500 normal-case tracking-normal">
                                                max {{ $sh['effective_max'] }} pts
                                            </div>
                                        </th>
                                    @endforeach
                                    <th class="py-3 px-3 text-center font-semibold min-w-[80px] border-l border-gray-100 dark:border-gray-800">
                                        Total
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                                @foreach($students as $student)
                                    @php
                                        $studentTotal = collect($scoreHeads)->sum(
                                            fn($sh) => (float) ($scores[$student['id']][$sh['id']] ?? 0)
                                        );
                                        $isPerfect = $studentTotal > 0 && $studentTotal == $maxTotal;
                                        $hasAny    = $studentTotal > 0;
                                    @endphp
                                    <tr class="group bg-white dark:bg-gray-900 hover:bg-gray-50/70 dark:hover:bg-gray-800/30 transition-colors">

                                        {{-- Student name --}}
                                        <td class="py-2.5 px-4 font-medium text-gray-800 dark:text-gray-200 sticky left-0 bg-white dark:bg-gray-900 group-hover:bg-gray-50/70 dark:group-hover:bg-gray-800/30 border-r border-gray-100 dark:border-gray-800 transition-colors z-10">
                                            {{ $student['full_name'] }}
                                        </td>

                                        {{-- Score inputs --}}
                                        @foreach($scoreHeads as $sh)
                                            @php
                                                $val    = $scores[$student['id']][$sh['id']] ?? '';
                                                $isOver = $val !== '' && (float)$val > $sh['effective_max'];
                                            @endphp
                                            <td class="py-1.5 px-2 border-r border-gray-50 dark:border-gray-800/60">
                                                <input
                                                    type="number"
                                                    wire:model.lazy="scores.{{ $student['id'] }}.{{ $sh['id'] }}"
                                                    min="0"
                                                    max="{{ $sh['effective_max'] }}"
                                                    step="0.5"
                                                    placeholder="—"
                                                    class="w-full text-center rounded-md border px-2 py-1.5 text-sm transition-colors focus:outline-none focus:ring-2
                                                        {{ $isOver
                                                            ? 'border-red-400 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 focus:ring-red-300'
                                                            : 'border-gray-200 dark:border-gray-700 bg-transparent text-gray-800 dark:text-gray-200 hover:border-primary-300 dark:hover:border-primary-600 focus:ring-primary-400' }}"
                                                >
                                            </td>
                                        @endforeach

                                        {{-- Row total --}}
                                        <td class="py-2.5 px-3 text-center border-l border-gray-100 dark:border-gray-800">
                                            @if($hasAny)
                                                <span class="inline-block font-bold tabular-nums px-2 py-0.5 rounded-full text-xs
                                                    {{ $isPerfect
                                                        ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
                                                        : 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' }}">
                                                    {{ number_format($studentTotal, 1) }}
                                                </span>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600 tabular-nums">—</span>
                                            @endif
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>

                    {{-- Footer / Save action --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/20">
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            Leave a cell blank to clear that score. Changes are saved per student, per score head.
                        </p>

                        <x-filament::button
                            wire:click="saveScores"
                            wire:loading.attr="disabled"
                            color="primary"
                            icon="heroicon-o-check"
                        >
                            <span wire:loading.remove wire:target="saveScores">Save All Scores</span>
                            <span wire:loading wire:target="saveScores">Saving…</span>
                        </x-filament::button>
                    </div>

                </div>
            @endif

        @elseif($session_id && $term_id && $classroom_id && $subject_id)
            {{-- All filters set but not yet loaded --}}
            <div class="flex items-center justify-center py-10 text-sm text-gray-400 dark:text-gray-500 gap-2">
                <svg class="animate-spin h-5 w-5 text-primary-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Preparing score sheet…
            </div>

        @else
            {{-- Placeholder when filters incomplete --}}
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/20 py-16 text-center">
                <x-heroicon-o-pencil-square class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3"/>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Select a session, term, class, and subject above</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">The score sheet for that combination will appear here</p>
            </div>
        @endif

    </div>
</x-filament-panels::page>
