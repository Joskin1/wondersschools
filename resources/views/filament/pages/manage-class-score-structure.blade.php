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

        {{-- ── Step 1: Sleek Parameters Filters (Top Deck) ──────────────── --}}
        <div class="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#131b2e] p-6 shadow-sm">
            <div class="relative flex flex-col gap-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">
                            {{-- Funnel / config icon --}}
                            <svg style="width: 20px; height: 20px; color: #4f46e5;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-900 dark:text-white">Select Class &amp; Term</h2>
                            <p class="text-xs text-slate-400 dark:text-gray-500">Configure parameters to load the active score head structure.</p>
                        </div>
                    </div>

                    {{-- Loading status spinner --}}
                    <div wire:loading wire:target="session_id,term_id,classroom_id" class="flex items-center gap-2 text-xs text-indigo-600 dark:text-indigo-400 font-bold animate-pulse">
                        <svg class="animate-spin h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Loading structure...
                    </div>
                </div>

                {{-- Select Dropdown Row --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
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
                            @foreach($this->classrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Step 2: Structure (only after class is chosen) ──────────────── --}}
        @if($this->hasSelectedFilters)

            @php
                $barColor = $totalScore === 100 ? 'bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.3)]' : ($totalScore > 100 ? 'bg-rose-500 shadow-[0_0_12px_rgba(244,63,94,0.3)]' : 'bg-indigo-500 shadow-[0_0_12px_rgba(99,102,241,0.3)]');
                $barWidth  = min($totalScore, 100);
            @endphp

            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0b0f19] shadow-sm overflow-hidden transition-all">

                {{-- Card header --}}
                <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-5 border-b border-slate-100 dark:border-slate-850 bg-slate-50/20 dark:bg-[#131b2e]/30">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-extrabold text-slate-900 dark:text-white uppercase tracking-wider">Score Structure</span>

                        @if($locked)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 dark:bg-rose-950/50 px-3 py-1 text-xs font-black text-rose-700 dark:text-rose-450 border border-rose-100 dark:border-rose-900/30">
                                <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Locked
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/50 px-3 py-1 text-xs font-black text-emerald-700 dark:text-emerald-400 border border-emerald-105 dark:border-emerald-900/30">
                                <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Editable
                            </span>
                        @endif
                    </div>

                    <div class="flex items-baseline gap-1 {{ $totalScore > 100 ? 'text-rose-500' : ($totalScore === 100 ? 'text-emerald-500' : 'text-slate-400 dark:text-slate-500') }}">
                        <span class="text-3xl font-black tabular-nums leading-none">{{ $totalScore }}</span>
                        <span class="text-xs font-bold uppercase tracking-wider">/ 100</span>
                    </div>
                </div>

                {{-- Premium Progress bar --}}
                <div class="h-2 w-full bg-slate-100 dark:bg-slate-850">
                    <div class="h-full transition-all duration-500 ease-out {{ $barColor }}" style="width: {{ $barWidth }}%"></div>
                </div>

                <div class="p-6 space-y-6">
                    @php $selectedScoreHeadIds = $this->selectedScoreHeadIds; @endphp

                    {{-- Score head assignment list --}}
                    <div class="rounded-xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/30 dark:bg-[#131b2e]/10 p-5 space-y-3.5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-550">Assign Score Heads To This Class</p>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500">{{ count($selectedScoreHeadIds) }} selected</span>
                        </div>

                        @if($this->scoreHeads->isEmpty())
                            <p class="text-xs font-bold text-slate-400 dark:text-slate-500">
                                No active score heads have been created yet. Create score heads first under Results → Score Heads.
                            </p>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                                @foreach($this->scoreHeads as $scoreHead)
                                    @php $isSelected = in_array((int) $scoreHead->id, $selectedScoreHeadIds, true); @endphp
                                    <label
                                        class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border px-4 py-3 transition
                                            {{ $isSelected
                                                ? 'border-indigo-400 bg-indigo-50 text-slate-900 dark:border-indigo-700 dark:bg-indigo-950/30 dark:text-white'
                                                : 'border-slate-200 bg-white text-slate-700 hover:border-indigo-300 dark:border-slate-800 dark:bg-[#0b0f19] dark:text-slate-200 dark:hover:border-indigo-800' }}
                                            {{ $locked && ! auth()->user()?->isSudo() ? 'cursor-not-allowed opacity-60' : '' }}"
                                    >
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-extrabold">{{ $scoreHead->name }}</span>
                                            <span class="block text-[10px] font-bold text-slate-400 dark:text-slate-500">Default: {{ $scoreHead->max_score }} pts</span>
                                        </span>
                                        <input
                                            type="checkbox"
                                            class="h-5 w-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                            wire:click="toggleScoreHead({{ $scoreHead->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="toggleScoreHead"
                                            @checked($isSelected)
                                            @disabled($locked && ! auth()->user()?->isSudo())
                                        >
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Items table --}}
                    @if(empty($items))
                        <div class="flex flex-col items-center justify-center py-12 text-center rounded-xl border border-dashed border-slate-200 dark:border-slate-800 bg-slate-50/10 dark:bg-[#131b2e]/10">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-105 dark:bg-[#131b2e] text-slate-400 mb-3.5">
                                <svg style="width: 22px; height: 22px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                            </div>
                            <p class="text-sm font-extrabold text-slate-805 dark:text-white">No score heads added yet</p>
                            <p class="text-xs text-slate-400 dark:text-gray-500 mt-1 max-w-sm">Select one or more score heads above to build this class's grading weights.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0b0f19]">
                            <table class="w-full text-sm border-collapse text-left">
                                <thead>
                                    <tr class="bg-slate-50/50 dark:bg-[#131b2e]/50 text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-400 border-b border-slate-250 dark:border-slate-800 sticky top-0">
                                        <th class="py-3 px-5 text-left font-black w-12">#</th>
                                        <th class="py-3 px-5 text-left font-black">Score Head</th>
                                        <th class="py-3 px-5 text-center font-black">Default Limit</th>
                                        <th class="py-3 px-5 text-center font-black">Override (Brackets)</th>
                                        <th class="py-3 px-5 text-center font-black">Effective Limit</th>
                                        <th class="py-3 px-4 w-12"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 dark:divide-slate-850">
                                    @foreach($items as $index => $item)
                                        @php $effective = (int)($item['max_score_override'] ?: $item['max_score']); @endphp
                                        <tr class="group hover:bg-slate-50/20 dark:hover:bg-[#131b2e]/10 transition-colors">

                                            <td class="py-3.5 px-5 text-xs text-slate-400 dark:text-slate-650 font-bold">{{ $index + 1 }}</td>

                                            <td class="py-3.5 px-5 font-extrabold text-slate-900 dark:text-white">
                                                {{ $item['name'] }}
                                            </td>

                                            <td class="py-3.5 px-5 text-center text-slate-500 dark:text-slate-400 font-bold tabular-nums">
                                                {{ $item['max_score'] }} pts
                                            </td>

                                            {{-- Bracketed Override input matching spreadsheet --}}
                                            <td class="py-3.5 px-5 text-center">
                                                @if(! $locked || auth()->user()?->isSudo())
                                                    <div class="flex items-center justify-center">
                                                        <div class="inline-flex items-center justify-center gap-1 px-3 py-1 rounded-lg border border-transparent bg-slate-800/40 hover:bg-slate-800/60 focus-within:ring-2 focus-within:ring-indigo-500/20 transition-all">
                                                            <span class="text-slate-500 font-extrabold text-sm select-none">[</span>
                                                            <input
                                                                type="number"
                                                                wire:model.live="items.{{ $index }}.max_score_override"
                                                                min="1"
                                                                max="{{ $item['max_score'] }}"
                                                                placeholder="{{ $item['max_score'] }}"
                                                                class="w-12 text-center bg-transparent border-none focus:outline-none focus:ring-0 p-0 text-xs font-black text-slate-800 dark:text-slate-100 placeholder-slate-600 focus:text-indigo-600 focus:dark:text-indigo-400"
                                                            >
                                                            <span class="text-slate-500 font-extrabold text-sm select-none">]</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-xs text-slate-500 dark:text-slate-600 font-bold">{{ $item['max_score_override'] ?? '—' }}</span>
                                                @endif
                                            </td>

                                            <td class="py-3.5 px-5 text-center">
                                                <span class="inline-block font-extrabold tabular-nums px-3 py-1 rounded-lg text-xs
                                                    {{ $item['max_score_override'] ? 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 border border-amber-100/30' : 'bg-slate-105 dark:bg-[#131b2e] text-slate-700 dark:text-slate-305' }}">
                                                    {{ $effective }} pts
                                                </span>
                                            </td>

                                            <td class="py-3.5 px-4 text-center">
                                                @if(! $locked || auth()->user()?->isSudo())
                                                    <button
                                                        type="button"
                                                        wire:click="removeItem({{ $index }})"
                                                        class="opacity-0 group-hover:opacity-100 rounded-lg p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20 transition-all"
                                                        title="Remove {{ $item['name'] }}"
                                                    >
                                                        <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
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
                        <div class="flex items-start gap-3 rounded-xl border border-rose-200 dark:border-rose-900/30 bg-rose-50/50 dark:bg-rose-950/20 px-4.5 py-3.5 text-xs text-rose-700 dark:text-rose-400 font-bold">
                            <svg style="width: 16px; height: 16px; margin-top: 2px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                            <span>Total structure score exceeds 100 limits. Please adjust brackets overrides or remove a score head before saving.</span>
                        </div>
                    @elseif($totalScore > 0 && $totalScore < 100)
                        <div class="flex items-start gap-3 rounded-xl border border-amber-200 dark:border-amber-900/30 bg-amber-50/50 dark:bg-amber-950/20 px-4.5 py-3.5 text-xs text-amber-700 dark:text-amber-400 font-bold">
                            <svg style="width: 16px; height: 16px; margin-top: 2px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="12" x2="12" y2="16"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                            <span>Total score is currently {{ $totalScore }}/100. Add more score heads or adjust your brackets overrides to reach exactly 100.</span>
                        </div>
                    @endif

                    {{-- Action row --}}
                    <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-100 dark:border-slate-850">

                        @if($structureId)
                            <button
                                type="button"
                                wire:click="toggleLock"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-205 rounded-xl text-xs font-black transition"
                            >
                                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                {{ $locked ? 'Unlock structure' : 'Lock Structure' }}
                            </button>
                        @else
                            <div></div>
                        @endif

                        @if(! $locked || auth()->user()?->isSudo())
                            <button
                                type="button"
                                wire:click="saveStructure"
                                wire:loading.attr="disabled"
                                @disabled($totalScore !== 100 || empty($items))
                                class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-black shadow-sm transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span wire:loading.remove wire:target="saveStructure">Save Structure</span>
                                <span wire:loading wire:target="saveStructure">Saving…</span>
                            </button>
                        @endif

                    </div>

                </div>
            </div>

        @else
            {{-- Sleek illustration Empty State --}}
            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 dark:border-slate-800 bg-white dark:bg-[#131b2e] py-24 px-6 text-center shadow-sm relative overflow-hidden">
                <div class="absolute -right-24 -bottom-24 h-48 w-48 rounded-full bg-indigo-500/5 blur-3xl"></div>
                <div class="absolute -left-24 -top-24 h-48 w-48 rounded-full bg-sky-500/5 blur-3xl"></div>

                <div class="relative flex flex-col items-center max-w-sm">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 mb-6 shadow-sm">
                        {{-- Academic cap icon --}}
                        <svg style="width: 32px; height: 32px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>
                    </div>
                    
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Begin Structure Setup</h3>
                    <p class="mt-2 text-xs text-slate-450 dark:text-gray-400 leading-relaxed">
                        To build or configure the active grading weights and score head structure for a class, please choose the session, term, and classroom parameters from the filters above.
                    </p>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
