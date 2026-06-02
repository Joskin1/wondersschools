<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    slate: {
                        250: '#e2e8f0',
                        850: '#1b2330',
                    }
                }
            }
        }
    }
</script>

<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ── Sleek Parameter Filters Card (Top Deck) ──────────────────────── --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#131b2e] p-6 shadow-sm">
            <div class="relative flex flex-col gap-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">
                            {{-- Pencil icon --}}
                            <svg style="width: 20px; height: 20px; color: #4f46e5;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-900 dark:text-white">Active Parameters</h2>
                            <p class="text-xs text-slate-405 dark:text-gray-400">Configure parameters to load the student scorecard matrix.</p>
                        </div>
                    </div>

                    {{-- Loading status spinner --}}
                    <div wire:loading wire:target="session_id,term_id,classroom_id,subject_id" class="flex items-center gap-2 text-xs text-indigo-600 dark:text-indigo-400 font-bold animate-pulse">
                        <svg class="animate-spin h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Loading scorecard matrix...
                    </div>
                </div>

                {{-- Select Dropdown Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    {{-- Session --}}
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Academic Session
                        </label>
                        <select
                            wire:model.live="session_id"
                            class="w-full rounded-xl border border-slate-250 dark:border-slate-800 bg-white dark:bg-[#0b0f19] text-slate-800 dark:text-slate-100 px-3.5 py-2.5 text-sm transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
                        >
                            <option value="">— Select Session —</option>
                            @foreach($this->sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Term --}}
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Term
                        </label>
                        <select
                            wire:model.live="term_id"
                            @disabled(! $session_id)
                            class="w-full rounded-xl border border-slate-250 dark:border-slate-800 bg-white dark:bg-[#0b0f19] text-slate-800 dark:text-slate-100 px-3.5 py-2.5 text-sm transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-50 dark:disabled:bg-gray-800/50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <option value="">— Select Term —</option>
                            @foreach($this->terms as $term)
                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Class --}}
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Classroom
                        </label>
                        <select
                            wire:model.live="classroom_id"
                            @disabled(! $term_id)
                            class="w-full rounded-xl border border-slate-250 dark:border-slate-800 bg-white dark:bg-[#0b0f19] text-slate-800 dark:text-slate-100 px-3.5 py-2.5 text-sm transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-50 dark:disabled:bg-gray-800/50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <option value="">— Select Class —</option>
                            @foreach($this->authorizedClassrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Subject --}}
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            Subject
                        </label>
                        <select
                            wire:model.live="subject_id"
                            @disabled(! $classroom_id)
                            class="w-full rounded-xl border border-slate-250 dark:border-slate-800 bg-white dark:bg-[#0b0f19] text-slate-800 dark:text-slate-100 px-3.5 py-2.5 text-sm transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 disabled:bg-slate-50 dark:disabled:bg-gray-800/50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <option value="">— Select Subject —</option>
                            @foreach($this->authorizedSubjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Main Workspace ────────────────────────────────────────────────── --}}
        @if($loaded)

            {{-- Case 1: Structure Empty --}}
            @if(! $structureExists)
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-amber-200 dark:border-amber-900/50 bg-amber-50/10 dark:bg-amber-950/5 py-16 text-center shadow-sm">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 dark:bg-amber-900/50 text-amber-500 mb-4">
                        {{-- Warning icon --}}
                        <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                    <h3 class="text-sm font-extrabold text-amber-800 dark:text-amber-300">Score Structure Not Set</h3>
                    <p class="mt-1 max-w-sm text-xs text-amber-605 dark:text-amber-400">
                        The class score weights (e.g. Test &amp; Exams weight distributions) are not yet configured for this term.
                    </p>
                </div>

            {{-- Case 2: Enrolled Empty --}}
            @elseif(empty($students))
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-[#131b2e]/30 py-16 text-center shadow-sm">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-gray-800 text-slate-400 dark:text-gray-500 mb-4">
                        {{-- Users icon --}}
                        <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-white">No Enrolled Students</h3>
                    <p class="mt-1 max-w-sm text-xs text-slate-400 dark:text-gray-500">
                        There are currently no active students enrolled in this class for the current session.
                    </p>
                </div>

            {{-- Case 3: Interactive Mockup spreadsheet Matrix --}}
            @else
                @php
                    $stats = $this->stats;
                @endphp

                <div 
                    x-data="{
                        savingStates: {},
                        errorMessages: {},
                        moveFocus(current, rowOffset, colOffset) {
                            let row = parseInt(current.getAttribute('data-row')) + rowOffset;
                            let col = parseInt(current.getAttribute('data-col')) + colOffset;
                            let next = document.querySelector(`input[data-row='${row}'][data-col='${col}']`);
                            if (next) {
                                next.focus();
                                next.select();
                            }
                        },
                        triggerSave(studentId, scoreHeadId, value, maxVal, headName) {
                            let key = `${studentId}-${scoreHeadId}`;
                            let rowKey = `row-${studentId}`;
                            
                            // Visual Crimson validations and row error indicators
                            if (value !== '' && (parseFloat(value) < 0 || parseFloat(value) > maxVal)) {
                                this.errorMessages[key] = `Max ${maxVal}`;
                                this.savingStates[rowKey] = 'error';
                                document.getElementById(`sync-text-${studentId}`).innerText = `Max Error (${headName})`;
                                document.getElementById(`sync-text-${studentId}`).className = 'text-red-500 font-extrabold text-xs tracking-wide animate-pulse';
                                
                                // set Total & Grade to double-hyphen matching the mockup
                                document.getElementById(`total-${studentId}`).innerText = '--';
                                document.getElementById(`grade-${studentId}`).innerText = '--';
                                document.getElementById(`grade-${studentId}`).className = 'text-slate-500 font-bold text-xs select-none';
                                return;
                            }
                            
                            this.errorMessages[key] = null;
                            this.savingStates[rowKey] = 'saving';
                            document.getElementById(`sync-text-${studentId}`).innerText = 'Syncing...';
                            document.getElementById(`sync-text-${studentId}`).className = 'text-amber-500 font-black text-xs tracking-wide animate-pulse';
                            
                            $wire.saveScore(studentId, scoreHeadId, value).then(result => {
                                if (result.status === 'success') {
                                    this.savingStates[rowKey] = 'saved';
                                    document.getElementById(`sync-text-${studentId}`).innerText = 'Saved';
                                    document.getElementById(`sync-text-${studentId}`).className = 'text-emerald-500 font-bold text-xs tracking-wide';
                                    
                                    // Update student total & grade DOM
                                    document.getElementById(`total-${studentId}`).innerText = result.student_total;
                                    
                                    let gradeSpan = document.getElementById(`grade-${studentId}`);
                                    gradeSpan.innerText = result.student_grade;
                                    
                                    // Visual color grade updates matching mockup
                                    gradeSpan.className = '';
                                    if (result.student_grade === 'A' || result.student_grade === 'A+') {
                                        gradeSpan.className = 'text-white font-extrabold text-sm';
                                    } else if (result.student_grade === '—') {
                                        gradeSpan.className = 'text-slate-500 font-bold text-xs';
                                    } else {
                                        gradeSpan.className = 'text-slate-300 font-bold text-sm';
                                    }

                                    // Dynamic class stats updates
                                    if (result.stats) {
                                        document.getElementById('stat-average').innerText = result.stats.average + '%';
                                        document.getElementById('stat-highest').innerText = result.stats.highest + ' / ' + result.stats.max_total;
                                        document.getElementById('stat-lowest').innerText = result.stats.lowest + ' / ' + result.stats.max_total;
                                        document.getElementById('stat-pass-percent').innerText = result.stats.pass_percent + '%';
                                    }
                                } else {
                                    this.savingStates[rowKey] = 'error';
                                    document.getElementById(`sync-text-${studentId}`).innerText = result.message;
                                    document.getElementById(`sync-text-${studentId}`).className = 'text-red-500 font-black text-xs';
                                    document.getElementById(`total-${studentId}`).innerText = '--';
                                    document.getElementById(`grade-${studentId}`).innerText = '--';
                                }
                            }).catch(() => {
                                this.savingStates[rowKey] = 'error';
                                document.getElementById(`sync-text-${studentId}`).innerText = 'Error';
                                document.getElementById(`sync-text-${studentId}`).className = 'text-red-500 font-black text-xs';
                            });
                        }
                    }"
                    class="space-y-6"
                >

                    {{-- ── Real-Time Analytics Dashboard Row (Mockup Style) ────────── --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        {{-- Average --}}
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#131b2e] p-6 shadow-sm">
                            <p class="text-xs font-semibold text-slate-400 dark:text-slate-400 tracking-wide leading-none">Class Average:</p>
                            <p id="stat-average" class="text-2xl font-black text-slate-900 dark:text-white mt-2.5 tabular-nums">
                                {{ $stats['average'] }}%
                            </p>
                        </div>

                        {{-- Highest --}}
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#131b2e] p-6 shadow-sm">
                            <p class="text-xs font-semibold text-slate-400 dark:text-slate-400 tracking-wide leading-none">Highest Mark:</p>
                            <p id="stat-highest" class="text-2xl font-black text-slate-900 dark:text-white mt-2.5 tabular-nums">
                                {{ $stats['highest'] }} / {{ $stats['max_total'] }}
                            </p>
                        </div>

                        {{-- Lowest --}}
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#131b2e] p-6 shadow-sm">
                            <p class="text-xs font-semibold text-slate-400 dark:text-slate-400 tracking-wide leading-none">Lowest Mark:</p>
                            <p id="stat-lowest" class="text-2xl font-black text-slate-900 dark:text-white mt-2.5 tabular-nums">
                                {{ $stats['lowest'] }} / {{ $stats['max_total'] }}
                            </p>
                        </div>

                        {{-- Pass Rate --}}
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#131b2e] p-6 shadow-sm">
                            <p class="text-xs font-semibold text-slate-400 dark:text-slate-400 tracking-wide leading-none">Pass Rate:</p>
                            <p id="stat-pass-percent" class="text-2xl font-black text-slate-900 dark:text-white mt-2.5 tabular-nums">
                                {{ $stats['pass_percent'] }}%
                            </p>
                        </div>
                    </div>

                    {{-- ── spreadsheet Gradebook Matrix Grid View ─────────────────── --}}
                    <div class="relative rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0b0f19] shadow-sm overflow-hidden">
                        
                        <div class="overflow-x-auto max-h-[65vh] scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-800">
                            <table class="w-full text-sm border-collapse text-left">
                                <thead>
                                    <tr class="bg-slate-50/50 dark:bg-[#131b2e]/50 text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-400 border-b border-slate-250 dark:border-slate-800 sticky top-0 z-20">
                                        {{-- Locked Sticky Student Details Header --}}
                                        <th class="py-4.5 px-6 sticky left-0 bg-slate-50 dark:bg-[#131b2e] min-w-[260px] border-r border-slate-200 dark:border-slate-850 z-30 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)]">
                                            <div class="flex items-center justify-between">
                                                <span>Student Details (Sticky)</span>
                                                {{-- Padlock icon --}}
                                                <svg style="width: 14px; height: 14px; display: inline-block; color: #64748b;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                            </div>
                                        </th>
                                        @foreach($scoreHeads as $sh)
                                            <th class="py-4.5 px-4 text-center min-w-[135px] border-r border-slate-200 dark:border-slate-850">
                                                {{ $sh['name'] }} (Max {{ $sh['effective_max'] }})
                                            </th>
                                        @endforeach
                                        <th class="py-4.5 px-6 text-center min-w-[110px] border-r border-slate-200 dark:border-slate-850 bg-slate-100/10 dark:bg-[#131b2e]/20 font-black">
                                            Total ({{ $stats['max_total'] }})
                                        </th>
                                        <th class="py-4.5 px-6 text-center min-w-[95px] border-r border-slate-200 dark:border-slate-850 bg-slate-100/10 dark:bg-[#131b2e]/20 font-black">
                                            Grade
                                        </th>
                                        <th class="py-4.5 px-6 text-center min-w-[130px] bg-slate-100/10 dark:bg-[#131b2e]/20 font-black">
                                            Sync Status
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-200 dark:divide-slate-850">
                                    @foreach($students as $student)
                                        @php
                                            $studentTotal = collect($scoreHeads)->sum(
                                                fn($sh) => (float) ($scores[$student['id']][$sh['id']] ?? 0)
                                            );
                                            $hasAny = collect($scoreHeads)->contains(fn($sh) => ($scores[$student['id']][$sh['id']] ?? '') !== '');
                                            $gradeInfo = app(\App\Services\ResultCalculationService::class)->resolveGrade($studentTotal);
                                            $currentGrade = $hasAny ? $gradeInfo['grade'] : '—';
                                            
                                            // Avatar mapping hashing
                                            $colors = [
                                                'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300',
                                                'bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300',
                                                'bg-pink-50 text-pink-700 dark:bg-pink-950/50 dark:text-pink-300',
                                                'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300',
                                                'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
                                                'bg-teal-50 text-teal-700 dark:bg-teal-950/50 dark:text-teal-300'
                                            ];
                                            $colorIndex = crc32($student['full_name']) % count($colors);
                                            $avatarColor = $colors[$colorIndex];
                                            $initials = collect(explode(' ', $student['full_name']))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('');
                                        @endphp
                                        <tr class="group hover:bg-slate-50/20 dark:hover:bg-[#131b2e]/10 transition-colors">
                                            
                                            {{-- Sticky Profile Details Column --}}
                                            <td class="py-3.5 px-6 font-semibold text-slate-800 dark:text-slate-100 sticky left-0 bg-white dark:bg-[#0b0f19] group-hover:bg-slate-50 dark:group-hover:bg-[#131b2e]/20 border-r border-slate-200 dark:border-slate-800 transition-colors z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)]">
                                                <div class="flex items-center gap-3.5">
                                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full font-black text-xs uppercase {{ $avatarColor }}">
                                                        {{ $initials }}
                                                    </div>
                                                    <div class="truncate">
                                                        <p class="font-extrabold text-slate-900 dark:text-white leading-none truncate max-w-[155px]">{{ $student['full_name'] }}</p>
                                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1.5 font-bold tracking-wide">{{ $student['admission_number'] }}</p>
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- Dynamic bracketed spreadsheet cells --}}
                                            @foreach($scoreHeads as $sh)
                                                @php
                                                    $val = $scores[$student['id']][$sh['id']] ?? '';
                                                    $key = $student['id'] . '-' . $sh['id'];
                                                @endphp
                                                <td class="py-3 px-4 border-r border-slate-200 dark:border-slate-850">
                                                    <div class="flex items-center justify-center">
                                                        <div 
                                                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl border border-transparent transition-all"
                                                            :class="{
                                                                'bg-red-500/10 border-red-500/30 text-red-500': errorMessages['{{ $key }}'],
                                                                'bg-amber-500/5 border-amber-500/20': savingStates['row-{{ $student['id'] }}'] === 'saving' && $el.querySelector('input') === document.activeElement,
                                                                'bg-slate-800/40': !errorMessages['{{ $key }}'] && savingStates['row-{{ $student['id'] }}'] !== 'saving'
                                                            }"
                                                        >
                                                            <span class="text-slate-500 font-extrabold text-sm select-none" :class="errorMessages['{{ $key }}'] ? 'text-red-500' : 'text-slate-500'">[</span>
                                                            <input
                                                                type="number"
                                                                data-row="{{ $loop->parent->index }}"
                                                                data-col="{{ $loop->index }}"
                                                                min="0"
                                                                max="{{ $sh['effective_max'] }}"
                                                                step="0.5"
                                                                placeholder="—"
                                                                value="{{ $val }}"
                                                                
                                                                @keydown.up.prevent="moveFocus($el, -1, 0)"
                                                                @keydown.down.prevent="moveFocus($el, 1, 0)"
                                                                @keydown.left.prevent="moveFocus($el, 0, -1)"
                                                                @keydown.right.prevent="moveFocus($el, 0, 1)"
                                                                @keydown.enter.prevent="moveFocus($el, 1, 0)"
                                                                
                                                                @blur="triggerSave({{ $student['id'] }}, {{ $sh['id'] }}, $el.value, {{ $sh['effective_max'] }}, '{{ $sh['name'] }}')"
                                                                
                                                                class="w-10 text-center bg-transparent border-none focus:outline-none focus:ring-0 p-0 text-sm font-black text-slate-800 dark:text-slate-100 placeholder-slate-600 focus:text-indigo-600 focus:dark:text-indigo-400"
                                                            >
                                                            <span class="text-slate-500 font-extrabold text-sm select-none" :class="errorMessages['{{ $key }}'] ? 'text-red-500' : 'text-slate-500'">]</span>
                                                        </div>
                                                    </div>
                                                </td>
                                            @endforeach

                                            {{-- Total --}}
                                            <td class="py-3.5 px-6 text-center border-r border-slate-200 dark:border-slate-850 bg-slate-50/20 dark:bg-[#131b2e]/10">
                                                <span 
                                                    id="total-{{ $student['id'] }}" 
                                                    class="text-slate-900 dark:text-white font-extrabold text-sm"
                                                >
                                                    {{ $hasAny ? number_format($studentTotal, 0) : '—' }}
                                                </span>
                                            </td>

                                            {{-- Grade Badge --}}
                                            <td class="py-3.5 px-6 text-center border-r border-slate-200 dark:border-slate-850 bg-slate-50/20 dark:bg-[#131b2e]/10">
                                                <span 
                                                    id="grade-{{ $student['id'] }}"
                                                    class="{{ $currentGrade === 'A' || $currentGrade === 'A+'
                                                        ? 'text-white font-extrabold text-sm'
                                                        : ($currentGrade === '—'
                                                            ? 'text-slate-500 font-bold text-xs'
                                                            : 'text-slate-350 font-bold text-sm') }}"
                                                >
                                                    {{ $currentGrade }}
                                                </span>
                                            </td>

                                            {{-- Row sync indicator matching mockup --}}
                                            <td class="py-3.5 px-6 text-center bg-slate-50/20 dark:bg-[#131b2e]/10">
                                                <span 
                                                    id="sync-text-{{ $student['id'] }}"
                                                    class="text-emerald-500 font-bold text-xs tracking-wide"
                                                >
                                                    Saved
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            @endif

        @else
            {{-- ── Sleek illustration Empty State ─────────────────────────────── --}}
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-[#131b2e] py-24 px-6 text-center shadow-sm relative overflow-hidden">
                <div class="absolute -right-24 -bottom-24 h-48 w-48 rounded-full bg-indigo-500/5 blur-3xl"></div>
                <div class="absolute -left-24 -top-24 h-48 w-48 rounded-full bg-sky-500/5 blur-3xl"></div>

                <div class="relative flex flex-col items-center max-w-sm">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 mb-6 shadow-sm">
                        {{-- Elegant edit SVG --}}
                        <svg style="width: 32px; height: 32px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </div>
                    
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Begin Scorecard Entry</h3>
                    <p class="mt-2 text-xs text-slate-450 dark:text-gray-400 leading-relaxed">
                        To load the interactive spreadsheet matrix and dynamic metrics, please configure the session, term, class, and subject parameters from the filters above.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-4 text-[10px] font-bold text-slate-450 dark:text-gray-500">
                        <span class="flex items-center gap-1.5 bg-slate-50 dark:bg-[#0b0f19] px-2.5 py-1 rounded-lg">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Keyboard Navigation
                        </span>
                        <span class="flex items-center gap-1.5 bg-slate-50 dark:bg-[#0b0f19] px-2.5 py-1 rounded-lg">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                            Real-time Autosave
                        </span>
                        <span class="flex items-center gap-1.5 bg-slate-50 dark:bg-[#0b0f19] px-2.5 py-1 rounded-lg">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            Live Metrics
                        </span>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
