<x-filament-panels::page>
    <style>
        .ss-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .dark .ss-card { background: #131b2e; border-color: rgba(148,163,184,0.1); }
        .ss-label { display: block; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 6px; }
        .ss-select { width: 100%; border-radius: 0.75rem; border: 1px solid #e2e8f0; background: white; color: #1e293b; padding: 0.625rem 0.875rem; font-size: 0.875rem; transition: border-color 0.15s; }
        .ss-select:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .ss-select:disabled { opacity: 0.5; cursor: not-allowed; background: #f8fafc; }
        .dark .ss-select { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .dark .ss-select:disabled { background: rgba(30,41,59,0.5); }
        .ss-structure-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
        .dark .ss-structure-card { background: #0b0f19; border-color: rgba(148,163,184,0.1); }
        .ss-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(148,163,184,0.1); }
        .dark .ss-header { border-bottom-color: #1b2330; }
        .ss-badge { display: inline-flex; align-items: center; gap: 0.375rem; border-radius: 9999px; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 900; border: 1px solid; }
        .ss-badge-locked { background: rgba(244,63,94,0.08); color: #be123c; border-color: rgba(244,63,94,0.15); }
        .dark .ss-badge-locked { background: rgba(244,63,94,0.1); color: #fb7185; border-color: rgba(244,63,94,0.2); }
        .ss-badge-editable { background: rgba(16,185,129,0.08); color: #047857; border-color: rgba(16,185,129,0.15); }
        .dark .ss-badge-editable { background: rgba(16,185,129,0.1); color: #34d399; border-color: rgba(16,185,129,0.2); }
        .ss-progress { height: 8px; width: 100%; background: #f1f5f9; }
        .dark .ss-progress { background: #1b2330; }
        .ss-progress-bar { height: 100%; transition: width 0.5s ease-out; }
        .ss-score-head-card { display: flex; cursor: pointer; align-items: center; justify-content: space-between; gap: 0.75rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; padding: 0.75rem 1rem; transition: border-color 0.15s; }
        .ss-score-head-card:hover { border-color: #a5b4fc; }
        .dark .ss-score-head-card { border-color: #1e293b; background: #0b0f19; }
        .dark .ss-score-head-card:hover { border-color: #4338ca; }
        .ss-score-head-card.selected { border-color: #818cf8; background: rgba(238,242,255,1); }
        .dark .ss-score-head-card.selected { border-color: #4338ca; background: rgba(49,46,129,0.3); }
        .ss-table { width: 100%; font-size: 0.875rem; border-collapse: collapse; text-align: left; }
        .ss-table th { padding: 0.75rem 1.25rem; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
        .dark .ss-table th { border-bottom-color: #1e293b; }
        .ss-table td { padding: 0.875rem 1.25rem; border-bottom: 1px solid rgba(148,163,184,0.1); }
        .dark .ss-table td { border-bottom-color: rgba(148,163,184,0.05); }
        .ss-override-input { width: 3rem; text-align: center; background: transparent; border: none; outline: none; padding: 0; font-size: 0.75rem; font-weight: 900; color: #1e293b; }
        .dark .ss-override-input { color: #f1f5f9; }
        .ss-override-input::placeholder { color: #64748b; }
        .ss-override-wrap { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; border-radius: 0.5rem; background: rgba(30,41,59,0.4); }
        .ss-override-wrap:hover { background: rgba(30,41,59,0.6); }
        .ss-bracket { color: #64748b; font-weight: 900; font-size: 0.875rem; user-select: none; }
        .ss-effective { display: inline-block; font-weight: 900; font-variant-numeric: tabular-nums; padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; }
        .ss-remove-btn { border-radius: 0.5rem; padding: 0.375rem; color: #94a3b8; background: none; border: none; cursor: pointer; transition: all 0.15s; }
        .ss-remove-btn:hover { color: #f43f5e; background: rgba(244,63,94,0.08); }
        .ss-alert { display: flex; align-items: flex-start; gap: 0.75rem; border-radius: 0.75rem; padding: 1rem 1.25rem; font-size: 0.8125rem; font-weight: 700; margin-top: 1rem; }
        .ss-alert-danger { border: 1px solid rgba(244,63,94,0.25); background: rgba(244,63,94,0.06); color: #e11d48; }
        .dark .ss-alert-danger { background: rgba(244,63,94,0.08); color: #fb7185; border-color: rgba(244,63,94,0.2); }
        .ss-alert-warning { border: 1px solid rgba(245,158,11,0.25); background: rgba(245,158,11,0.06); color: #b45309; }
        .dark .ss-alert-warning { background: rgba(245,158,11,0.08); color: #fbbf24; border-color: rgba(245,158,11,0.2); }
        .ss-alert-icon { width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px; }
        .ss-btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.625rem 1.25rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 900; border: none; cursor: pointer; transition: all 0.15s; }
        .ss-btn-save { background: #059669; color: white; box-shadow: 0 1px 3px rgba(5,150,105,0.3); }
        .ss-btn-save:hover { background: #10b981; }
        .ss-btn-save:disabled { opacity: 0.5; cursor: not-allowed; }
        .ss-btn-lock { background: #f1f5f9; color: #475569; }
        .ss-btn-lock:hover { background: #e2e8f0; }
        .dark .ss-btn-lock { background: #1e293b; color: #cbd5e1; }
        .dark .ss-btn-lock:hover { background: #334155; }
        .ss-action-row { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding-top: 1rem; margin-top: 1rem; border-top: 1px solid rgba(148,163,184,0.1); }
        .dark .ss-action-row { border-top-color: rgba(148,163,184,0.05); }
        .ss-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1.5rem; text-align: center; border-radius: 1rem; border: 2px dashed rgba(148,163,184,0.2); position: relative; overflow: hidden; }
        .dark .ss-empty-state { border-color: rgba(148,163,184,0.1); background: #131b2e; }
        .ss-score-total { font-variant-numeric: tabular-nums; }
        .ss-score-total.over { color: #f43f5e; }
        .ss-score-total.exact { color: #10b981; }
        .ss-score-total.under { color: #94a3b8; }
    </style>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- ── Step 1: Parameters Filters ──────────────── --}}
        <div class="ss-card">
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(148,163,184,0.1);">
                    <div style="display: flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: 0.75rem; background: rgba(238,242,255,1); color: #4f46e5;">
                        <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    </div>
                    <div>
                        <h2 style="font-size: 1rem; font-weight: 900; color: #0f172a;" class="dark:text-white">Select Class &amp; Term</h2>
                        <p style="font-size: 0.75rem; color: #94a3b8;">Configure parameters to load the active score head structure.</p>
                    </div>
                </div>

                <div wire:loading wire:target="session_id,term_id,classroom_id" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #6366f1; font-weight: 700;">
                    <svg style="width: 16px; height: 16px;" class="animate-spin" fill="none" viewBox="0 0 24 24"><circle opacity="0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path opacity="0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Loading structure...
                </div>

                <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 1.25rem;" class="sm:grid-cols-3">
                    <div>
                        <label class="ss-label">Academic Session</label>
                        <select wire:model.live="session_id" class="ss-select">
                            <option value="">— Select Session —</option>
                            @foreach($this->sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ss-label">Term</label>
                        <select wire:model.live="term_id" @disabled(! $session_id) class="ss-select">
                            <option value="">— Select Term —</option>
                            @foreach($this->terms as $term)
                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ss-label">Classroom</label>
                        <select wire:model.live="classroom_id" @disabled(! $term_id) class="ss-select">
                            <option value="">— Select Class —</option>
                            @foreach($this->classrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Step 2: Structure ──────────────── --}}
        @if($this->hasSelectedFilters)

            @php
                $barBg = $totalScore === 100 ? '#10b981' : ($totalScore > 100 ? '#f43f5e' : '#6366f1');
                $barWidth = min($totalScore, 100);
                $scoreClass = $totalScore > 100 ? 'over' : ($totalScore === 100 ? 'exact' : 'under');
            @endphp

            <div class="ss-structure-card">
                <div class="ss-header">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 0.875rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-white">Score Structure</span>
                        @if($locked)
                            <span class="ss-badge ss-badge-locked">🔒 Locked</span>
                        @else
                            <span class="ss-badge ss-badge-editable">🔓 Editable</span>
                        @endif
                    </div>
                    <div class="ss-score-total {{ $scoreClass }}" style="display: flex; align-items: baseline; gap: 0.25rem;">
                        <span style="font-size: 1.875rem; font-weight: 900; line-height: 1;">{{ $totalScore }}</span>
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">/ 100</span>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div class="ss-progress">
                    <div class="ss-progress-bar" style="width: {{ $barWidth }}%; background: {{ $barBg }};"></div>
                </div>

                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem;">
                    @php $selectedScoreHeadIds = $this->selectedScoreHeadIds; @endphp

                    {{-- Score head checkboxes --}}
                    <div style="border-radius: 0.75rem; border: 2px dashed rgba(148,163,184,0.2); padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.875rem;">
                            <span class="ss-label" style="margin-bottom: 0;">Assign Score Heads To This Class</span>
                            <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">{{ count($selectedScoreHeadIds) }} selected</span>
                        </div>

                        @if($this->scoreHeads->isEmpty())
                            <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8;">No active score heads. Create them under Results → Score Heads.</p>
                        @else
                            <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 0.75rem;" class="md:grid-cols-2 xl:grid-cols-3">
                                @foreach($this->scoreHeads as $scoreHead)
                                    @php $isSelected = in_array((int) $scoreHead->id, $selectedScoreHeadIds, true); @endphp
                                    <label class="ss-score-head-card {{ $isSelected ? 'selected' : '' }}" @if($locked && ! auth()->user()?->isSudo()) style="cursor: not-allowed; opacity: 0.6;" @endif>
                                        <span>
                                            <span style="display: block; font-size: 0.875rem; font-weight: 900;" class="dark:text-white">{{ $scoreHead->name }}</span>
                                            <span style="display: block; font-size: 10px; font-weight: 700; color: #94a3b8;">Default: {{ $scoreHead->max_score }} pts</span>
                                        </span>
                                        <input type="checkbox" style="width: 1.25rem; height: 1.25rem; accent-color: #6366f1;"
                                            wire:click="toggleScoreHead({{ $scoreHead->id }})"
                                            wire:loading.attr="disabled" wire:target="toggleScoreHead"
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
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; text-align: center; border-radius: 0.75rem; border: 2px dashed rgba(148,163,184,0.15);">
                            <p style="font-size: 0.875rem; font-weight: 900;" class="dark:text-white">No score heads added yet</p>
                            <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Select score heads above to build grading weights.</p>
                        </div>
                    @else
                        <div style="overflow-x: auto; border-radius: 0.75rem; border: 1px solid rgba(148,163,184,0.15);">
                            <table class="ss-table">
                                <thead>
                                    <tr style="background: rgba(248,250,252,0.5);">
                                        <th style="width: 3rem; text-align: left;">#</th>
                                        <th style="text-align: left;">Score Head</th>
                                        <th style="text-align: center;">Default Limit</th>
                                        <th style="text-align: center;">Override (Brackets)</th>
                                        <th style="text-align: center;">Effective Limit</th>
                                        <th style="width: 3rem;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $index => $item)
                                        @php $effective = (int)($item['max_score_override'] ?: $item['max_score']); @endphp
                                        <tr>
                                            <td style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">{{ $index + 1 }}</td>
                                            <td style="font-weight: 900;" class="dark:text-white">{{ $item['name'] }}</td>
                                            <td style="text-align: center; color: #64748b; font-weight: 700; font-variant-numeric: tabular-nums;">{{ $item['max_score'] }} pts</td>
                                            <td style="text-align: center;">
                                                @if(! $locked || auth()->user()?->isSudo())
                                                    <div style="display: flex; align-items: center; justify-content: center;">
                                                        <div class="ss-override-wrap">
                                                            <span class="ss-bracket">[</span>
                                                            <input type="number" wire:model.live="items.{{ $index }}.max_score_override"
                                                                min="1" max="{{ $item['max_score'] }}" placeholder="{{ $item['max_score'] }}"
                                                                class="ss-override-input">
                                                            <span class="ss-bracket">]</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">{{ $item['max_score_override'] ?? '—' }}</span>
                                                @endif
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="ss-effective" style="{{ $item['max_score_override'] ? 'background: rgba(245,158,11,0.1); color: #b45309;' : 'background: rgba(148,163,184,0.1); color: #475569;' }}">
                                                    {{ $effective }} pts
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                @if(! $locked || auth()->user()?->isSudo())
                                                    <button type="button" wire:click="removeItem({{ $index }})" class="ss-remove-btn" title="Remove {{ $item['name'] }}">
                                                        <svg style="width: 14px; height: 14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
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
                        <div class="ss-alert ss-alert-danger">
                            <svg class="ss-alert-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>⚠️ <strong>Total score is {{ $totalScore }}/100 — exceeds the limit!</strong> Please adjust bracket overrides or remove a score head. The total must be exactly 100 to save.</span>
                        </div>
                    @elseif($totalScore > 0 && $totalScore < 100)
                        <div class="ss-alert ss-alert-warning">
                            <svg class="ss-alert-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <span>Total score is currently <strong>{{ $totalScore }}/100</strong>. Add more score heads or adjust bracket overrides to reach exactly 100.</span>
                        </div>
                    @endif

                    {{-- Action row --}}
                    <div class="ss-action-row">
                        @if($structureId)
                            <button type="button" wire:click="toggleLock" wire:loading.attr="disabled" class="ss-btn ss-btn-lock">
                                {{ $locked ? '🔒 Unlock Structure' : '🔓 Lock Structure' }}
                            </button>
                        @else
                            <div></div>
                        @endif

                        @if(! $locked || auth()->user()?->isSudo())
                            <button type="button" wire:click="saveStructure" wire:loading.attr="disabled"
                                @disabled($totalScore !== 100 || empty($items))
                                class="ss-btn ss-btn-save">
                                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <span wire:loading.remove wire:target="saveStructure">Save Structure</span>
                                <span wire:loading wire:target="saveStructure">Saving…</span>
                            </button>
                        @endif
                    </div>

                </div>
            </div>

        @else
            {{-- Empty State --}}
            <div class="ss-empty-state">
                <div style="width: 4rem; height: 4rem; display: flex; align-items: center; justify-content: center; border-radius: 1rem; background: rgba(238,242,255,1); color: #6366f1; margin-bottom: 1.5rem;">
                    <svg style="width: 32px; height: 32px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
                </div>
                <h3 style="font-size: 1rem; font-weight: 900;" class="dark:text-white">Begin Structure Setup</h3>
                <p style="margin-top: 0.5rem; font-size: 0.75rem; color: #94a3b8; max-width: 24rem; line-height: 1.6;">
                    To build or configure the active grading weights and score head structure for a class, please choose the session, term, and classroom parameters from the filters above.
                </p>
            </div>
        @endif

    </div>
</x-filament-panels::page>
