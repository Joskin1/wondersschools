<x-filament-panels::page>
    <style>
        .ms-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .dark .ms-card { background: #131b2e; border-color: rgba(148,163,184,0.1); }
        .ms-label { display: block; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 6px; }
        .ms-select, .ms-input { width: 100%; border-radius: 0.75rem; border: 1px solid #e2e8f0; background: white; color: #1e293b; padding: 0.625rem 0.875rem; font-size: 0.875rem; transition: border-color 0.15s; }
        .ms-select:focus, .ms-input:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .ms-select:disabled, .ms-input:disabled { opacity: 0.5; cursor: not-allowed; background: #f8fafc; }
        .dark .ms-select, .dark .ms-input { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .dark .ms-select:disabled, .dark .ms-input:disabled { background: rgba(30,41,59,0.5); }
        .ms-structure-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
        .dark .ms-structure-card { background: #0b0f19; border-color: rgba(148,163,184,0.1); }
        .ms-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(148,163,184,0.1); }
        .dark .ms-header { border-bottom-color: #1b2330; }
        .ms-badge { display: inline-flex; align-items: center; gap: 0.375rem; border-radius: 9999px; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 900; border: 1px solid; }
        .ms-badge-locked { background: rgba(244,63,94,0.08); color: #be123c; border-color: rgba(244,63,94,0.15); }
        .dark .ms-badge-locked { background: rgba(244,63,94,0.1); color: #fb7185; border-color: rgba(244,63,94,0.2); }
        .ms-badge-editable { background: rgba(16,185,129,0.08); color: #047857; border-color: rgba(16,185,129,0.15); }
        .dark .ms-badge-editable { background: rgba(16,185,129,0.1); color: #34d399; border-color: rgba(16,185,129,0.2); }
        .ms-score-head-card { display: flex; cursor: pointer; align-items: center; justify-content: space-between; gap: 0.75rem; border-radius: 0.75rem; border: 1px solid #e2e8f0; padding: 0.75rem 1rem; transition: border-color 0.15s; }
        .ms-score-head-card:hover { border-color: #a5b4fc; }
        .dark .ms-score-head-card { border-color: #1e293b; background: #0b0f19; }
        .dark .ms-score-head-card:hover { border-color: #4338ca; }
        .ms-score-head-card.selected { border-color: #818cf8; background: rgba(238,242,255,1); }
        .dark .ms-score-head-card.selected { border-color: #4338ca; background: rgba(49,46,129,0.3); }
        .ms-table { width: 100%; font-size: 0.875rem; border-collapse: collapse; text-align: left; }
        .ms-table th { padding: 0.75rem 1.25rem; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
        .dark .ms-table th { border-bottom-color: #1e293b; }
        .ms-table td { padding: 0.875rem 1.25rem; border-bottom: 1px solid rgba(148,163,184,0.1); }
        .dark .ms-table td { border-bottom-color: rgba(148,163,184,0.05); }
        .ms-override-input { width: 3rem; text-align: center; background: transparent; border: none; outline: none; padding: 0; font-size: 0.75rem; font-weight: 900; color: #1e293b; }
        .dark .ms-override-input { color: #f1f5f9; }
        .ms-override-wrap { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.75rem; border-radius: 0.5rem; background: rgba(30,41,59,0.4); }
        .ms-effective { display: inline-block; font-weight: 900; font-variant-numeric: tabular-nums; padding: 0.25rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; }
        .ms-btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.625rem 1.25rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 900; border: none; cursor: pointer; transition: all 0.15s; }
        .ms-btn-save { background: #059669; color: white; box-shadow: 0 1px 3px rgba(5,150,105,0.3); }
        .ms-btn-save:hover { background: #10b981; }
        .ms-btn-save:disabled { opacity: 0.5; cursor: not-allowed; }
        .ms-btn-secondary { background: #f1f5f9; color: #475569; }
        .dark .ms-btn-secondary { background: #1e293b; color: #cbd5e1; }
        .ms-stat-pill { display: flex; flex-direction: column; padding: 0.5rem 1rem; border-radius: 0.75rem; border: 1px solid rgba(148,163,184,0.2); background: rgba(248,250,252,0.6); }
        .dark .ms-stat-pill { background: rgba(30,41,59,0.3); border-color: rgba(148,163,184,0.1); }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 0.85s linear infinite;
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- Classroom Selection --}}
        <div class="ms-card">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2 style="font-size: 1.125rem; font-weight: 900; color: #0f172a;" class="dark:text-white">Class Mid-Term Configuration</h2>
                        <p style="font-size: 0.75rem; color: #94a3b8;">Assign continuous assessment heads and weight for each class.</p>
                    </div>

                    <div style="min-width: 280px;">
                        <label class="ms-label">Select Classroom</label>
                        <select wire:model.live="classroom_id" class="ms-select">
                            <option value="">— Select Classroom —</option>
                            @foreach($this->classrooms as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div wire:loading wire:target="classroom_id" style="font-size: 0.8rem; color: #6366f1; font-weight: 700; display: inline-flex; align-items: center; gap: 0.375rem;">
                    <svg class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Loading midterm configuration...
                </div>
            </div>
        </div>

        @if($classroom_id)
            <div class="ms-structure-card">
                {{-- Header with Stats --}}
                <div class="ms-header">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 0.875rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-white">Assessment Weights</span>
                        @if($locked)
                            <span class="ms-badge ms-badge-locked">🔒 Locked (Scores Recorded)</span>
                        @else
                            <span class="ms-badge ms-badge-editable">🔓 Configurable</span>
                        @endif
                    </div>

                    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <div class="ms-stat-pill">
                            <span style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase;">Mid-Term Weight (W)</span>
                            <span style="font-size: 1.25rem; font-weight: 900; color: #6366f1;">{{ $midterm_weight }}%</span>
                        </div>
                        <div class="ms-stat-pill">
                            <span style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase;">Raw Max Sum</span>
                            <span style="font-size: 1.25rem; font-weight: 900; color: #059669;">{{ $rawMax }} pts</span>
                        </div>
                        <div class="ms-stat-pill">
                            <span style="font-size: 10px; font-weight: 900; color: #94a3b8; text-transform: uppercase;">Derived Exam Weight</span>
                            <span style="font-size: 1.25rem; font-weight: 900; color: #d97706;">{{ $this->examWeight }}%</span>
                        </div>
                    </div>
                </div>

                <div style="padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem;">

                    {{-- Weight Configuration Input --}}
                    <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem;" class="sm:grid-cols-2">
                        <div>
                            <label class="ms-label">Mid-Term Assessment Weight (1 - 99%)</label>
                            <input type="number" wire:model.live.debounce.300ms="midterm_weight" min="1" max="99" class="ms-input" @disabled($locked && ! auth()->user()?->isSudo())>
                            <span style="font-size: 11px; color: #94a3b8; margin-top: 4px; display: block;">
                                The percentage of the 100-mark terminal grade contributed by continuous assessment.
                            </span>
                        </div>

                        <div>
                            <label class="ms-label">Exam Weight (Derived: 100 - W)</label>
                            <input type="text" value="{{ $this->examWeight }}%" class="ms-input" disabled style="background: rgba(148,163,184,0.1); cursor: not-allowed;">
                            <span style="font-size: 11px; color: #94a3b8; margin-top: 4px; display: block;">
                                Remaining percentage allocated to the terminal exam.
                            </span>
                        </div>
                    </div>

                    {{-- Score Heads Checkboxes --}}
                    <div style="border-radius: 0.75rem; border: 2px dashed rgba(148,163,184,0.2); padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.875rem;">
                            <span class="ms-label" style="margin-bottom: 0;">Select Mid-Term Score Heads</span>
                            <span style="font-size: 10px; font-weight: 700; color: #94a3b8;">{{ count($this->selectedScoreHeadIds) }} selected</span>
                        </div>

                        @if($this->midtermScoreHeads->isEmpty())
                            <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8;">
                                No active midterm score heads found. Create score heads with type "Mid-Term Assessment" under Results → Score Heads.
                            </p>
                        @else
                            <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 0.75rem;" class="md:grid-cols-2 xl:grid-cols-3">
                                @foreach($this->midtermScoreHeads as $scoreHead)
                                    @php $isSelected = in_array((int) $scoreHead->id, $this->selectedScoreHeadIds, true); @endphp
                                    <label class="ms-score-head-card {{ $isSelected ? 'selected' : '' }}" @if($locked && ! auth()->user()?->isSudo()) style="cursor: not-allowed; opacity: 0.6;" @endif>
                                        <span>
                                            <span style="display: block; font-size: 0.875rem; font-weight: 900;" class="dark:text-white">{{ $scoreHead->name }}</span>
                                            <span style="display: block; font-size: 10px; font-weight: 700; color: #94a3b8;">Max: {{ $scoreHead->max_score }} pts</span>
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

                    {{-- Items Table --}}
                    @if(empty($items))
                        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; text-align: center; border-radius: 0.75rem; border: 2px dashed rgba(148,163,184,0.15);">
                            <p style="font-size: 0.875rem; font-weight: 900;" class="dark:text-white">No midterm score heads assigned</p>
                            <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem;">Select score heads above to configure this class assessment breakdown.</p>
                        </div>
                    @else
                        <div style="overflow-x: auto; border-radius: 0.75rem; border: 1px solid rgba(148,163,184,0.15);">
                            <table class="ms-table">
                                <thead>
                                    <tr style="background: rgba(248,250,252,0.5);">
                                        <th style="width: 3rem;">#</th>
                                        <th>Score Head</th>
                                        <th style="text-align: center;">Default Max</th>
                                        <th style="text-align: center;">Override (Brackets)</th>
                                        <th style="text-align: center;">Effective Max</th>
                                        <th style="width: 3rem;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $index => $item)
                                        @php $effective = (int)($item['max_score_override'] ?: $item['max_score']); @endphp
                                        <tr>
                                            <td style="font-size: 0.75rem; color: #94a3b8; font-weight: 700;">{{ $index + 1 }}</td>
                                            <td style="font-weight: 900;" class="dark:text-white">{{ $item['name'] }}</td>
                                            <td style="text-align: center; color: #64748b; font-weight: 700;">{{ $item['max_score'] }} pts</td>
                                            <td style="text-align: center;">
                                                @if(! $locked || auth()->user()?->isSudo())
                                                    <div style="display: flex; align-items: center; justify-content: center;">
                                                        <div class="ms-override-wrap">
                                                            <span style="color: #64748b; font-weight: 900;">[</span>
                                                            <input type="number" wire:model.live.debounce.300ms="items.{{ $index }}.max_score_override"
                                                                min="1" max="{{ $item['max_score'] }}" placeholder="{{ $item['max_score'] }}"
                                                                class="ms-override-input">
                                                            <span style="color: #64748b; font-weight: 900;">]</span>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 700;">{{ $item['max_score_override'] ?? '—' }}</span>
                                                @endif
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="ms-effective" style="{{ $item['max_score_override'] ? 'background: rgba(245,158,11,0.1); color: #b45309;' : 'background: rgba(148,163,184,0.1); color: #475569;' }}">
                                                    {{ $effective }} pts
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                @if(! $locked || auth()->user()?->isSudo())
                                                    <button type="button" wire:click="removeItem({{ $index }})" style="color: #94a3b8; background: none; border: none; cursor: pointer;">
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

                    {{-- Action Row & Tools --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; padding-top: 1rem; border-top: 1px solid rgba(148,163,184,0.1);">
                        <div>
                            @if(! $locked || auth()->user()?->isSudo())
                                <button type="button" wire:click="saveConfig" wire:loading.attr="disabled" wire:target="saveConfig"
                                    @disabled(empty($items) || $midterm_weight < 1 || $midterm_weight > 99)
                                    class="ms-btn ms-btn-save">
                                    <svg wire:loading.remove wire:target="saveConfig" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <svg wire:loading wire:target="saveConfig" class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="saveConfig">Save Mid-Term Structure</span>
                                    <span wire:loading wire:target="saveConfig">Saving…</span>
                                </button>
                            @endif
                        </div>

                        {{-- Copy & Apply Tools --}}
                        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <select wire:model="copy_source_classroom_id" class="ms-select" style="width: auto; padding: 0.4rem 0.75rem; font-size: 0.75rem;">
                                    <option value="">— Copy from class —</option>
                                    @foreach($this->classrooms as $c)
                                        @if($c->id !== $classroom_id)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button type="button" wire:click="copyFromClassroom" wire:loading.attr="disabled" wire:target="copyFromClassroom" class="ms-btn ms-btn-secondary" style="padding: 0.4rem 0.75rem; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                    <svg wire:loading wire:target="copyFromClassroom" class="animate-spin" style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24">
                                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="copyFromClassroom">Copy</span>
                                    <span wire:loading wire:target="copyFromClassroom">Copying...</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Apply to Multiple Classes Section --}}
                    @if(! empty($items))
                        <div style="margin-top: 1rem; padding: 1rem; border-radius: 0.75rem; background: rgba(248,250,252,0.5); border: 1px solid rgba(148,163,184,0.15);">
                            <span class="ms-label" style="margin-bottom: 0.5rem;">Apply This Setup To Multiple Classes</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                                @foreach($this->classrooms as $c)
                                    @if($c->id !== $classroom_id)
                                        <label style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.75rem; font-weight: 700; cursor: pointer; background: white;" class="dark:bg-slate-900 dark:border-slate-700">
                                            <input type="checkbox" value="{{ $c->id }}" wire:model="apply_target_classroom_ids" style="accent-color: #6366f1;">
                                            {{ $c->name }}
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                            <button type="button" wire:click="applyToMultipleClasses" wire:loading.attr="disabled" wire:target="applyToMultipleClasses" class="ms-btn ms-btn-secondary" style="font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.375rem;">
                                <svg wire:loading wire:target="applyToMultipleClasses" class="animate-spin" style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24">
                                    <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="applyToMultipleClasses">Apply Setup to Selected Classes</span>
                                <span wire:loading wire:target="applyToMultipleClasses">Applying Setup...</span>
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
