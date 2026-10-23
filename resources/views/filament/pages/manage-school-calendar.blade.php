<x-filament-panels::page>
    <style>
        .att-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .att-card svg { max-width: 24px; max-height: 24px; display: inline-block; vertical-align: middle; }
        .dark .att-card { background: #131b2e; border-color: rgba(148,163,184,0.1); }
        .att-label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 6px; }
        .dark .att-label { color: #94a3b8; }
        .att-input, .att-select { width: 100%; border-radius: 0.75rem; border: 1px solid #cbd5e1; background: white; color: #1e293b; padding: 0.625rem 0.875rem; font-size: 0.875rem; }
        .att-input:focus, .att-select:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .dark .att-input, .dark .att-select { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .att-table { width: 100%; font-size: 0.875rem; border-collapse: collapse; text-align: left; }
        .att-table th { padding: 0.75rem 1rem; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
        .dark .att-table th { border-bottom-color: #1e293b; color: #94a3b8; }
        .att-table td { padding: 0.875rem 1rem; border-bottom: 1px solid rgba(148,163,184,0.1); }
        .dark .att-table td { border-bottom-color: rgba(148,163,184,0.05); }
        .att-badge { display: inline-flex; align-items: center; gap: 0.375rem; border-radius: 9999px; padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 800; }
        .att-badge-locked { background: rgba(16,185,129,0.1); color: #059669; border: 1px solid rgba(16,185,129,0.2); }
        .dark .att-badge-locked { background: rgba(16,185,129,0.15); color: #34d399; }
        .att-badge-progress { background: rgba(245,158,11,0.1); color: #d97706; border: 1px solid rgba(245,158,11,0.2); }
        .dark .att-badge-progress { background: rgba(245,158,11,0.15); color: #fbbf24; }
        .att-badge-missing { background: rgba(244,63,94,0.1); color: #e11d48; border: 1px solid rgba(244,63,94,0.2); }
        .dark .att-badge-missing { background: rgba(244,63,94,0.15); color: #fb7185; }
        .att-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.15s; }
        .att-btn-primary { background: #4f46e5; color: white; }
        .att-btn-primary:hover { background: #4338ca; }
        .att-btn-danger { background: rgba(244,63,94,0.1); color: #e11d48; }
        .att-btn-danger:hover { background: #e11d48; color: white; }
        .att-btn-warning { background: rgba(245,158,11,0.15); color: #b45309; }
        .att-btn-warning:hover { background: #f59e0b; color: white; }
        .dark .att-btn-warning { color: #fde68a; background: rgba(245,158,11,0.2); }
        .dark .att-btn-warning:hover { background: #d97706; color: white; }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 0.85s linear infinite;
        }
    </style>

    <div class="space-y-6">
        <!-- ── Top Filter Bar ────────────────────────────────────────────── -->
        <div class="att-card">
            <div wire:loading wire:target="session_id,term_id,selected_week" style="margin-bottom: 0.75rem; font-size: 0.75rem; color: #6366f1; font-weight: 700; display: flex; align-items: center; gap: 0.375rem;">
                <svg class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Loading calendar and compliance records...
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="att-label">Academic Session</label>
                    <select wire:model.live="session_id" class="att-select">
                        @foreach(\App\Models\Session::orderByDesc('start_year')->get() as $sess)
                            <option value="{{ $sess->id }}">{{ $sess->name }} {{ $sess->is_active ? '★ (Active)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="att-label">Academic Term</label>
                    <select wire:model.live="term_id" class="att-select">
                        @foreach(\App\Models\Term::where('session_id', $session_id)->orderBy('order')->get() as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} {{ $t->is_active ? '★ (Active)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- ── Column 1: Term Dates & Setting ──────────────────────────── -->
            <div class="att-card space-y-4">
                <div class="flex items-center gap-2 border-b pb-3 dark:border-slate-800">
                    <x-heroicon-o-calendar class="w-5 h-5 text-indigo-500" />
                    <h3 class="font-bold text-base dark:text-white">Term Calendar Dates</h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Specify the official start and end dates for this term. Week numbers and school days are automatically calculated from these dates.
                </p>

                <div class="space-y-3">
                    <div>
                        <label class="att-label">Term Resumption (Start Date)</label>
                        <input type="date" wire:model="term_start_date" class="att-input" />
                        @error('term_start_date') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="att-label">Term Vacation (End Date)</label>
                        <input type="date" wire:model="term_end_date" class="att-input" />
                        @error('term_end_date') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <button wire:click="saveTermDates" wire:loading.attr="disabled" wire:target="saveTermDates" type="button" class="att-btn att-btn-primary w-full">
                        <x-heroicon-o-check wire:loading.remove wire:target="saveTermDates" class="w-4 h-4" />
                        <svg wire:loading wire:target="saveTermDates" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="saveTermDates">Save Term Dates</span>
                        <span wire:loading wire:target="saveTermDates">Saving...</span>
                    </button>
                </div>
            </div>

            <!-- ── Column 2: Declare Holiday Form ───────────────────────────── -->
            <div class="att-card space-y-4 lg:col-span-2">
                <div class="flex items-center gap-2 border-b pb-3 dark:border-slate-800">
                    <x-heroicon-o-sun class="w-5 h-5 text-amber-500" />
                    <h3 class="font-bold text-base dark:text-white">Declare School Holiday / Closure</h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Select a single date or date range (e.g. Mid-Term Break, Public Holiday). Teachers will be prevented from taking attendance on these days.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="md:col-span-3">
                        <label class="att-label">Holiday Title / Reason</label>
                        <input type="text" wire:model="holiday_title" placeholder="e.g. Mid-Term Break, Independence Day" class="att-input" />
                        @error('holiday_title') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="att-label">Start Date</label>
                        <input type="date" wire:model="holiday_start_date" class="att-input" />
                        @error('holiday_start_date') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="att-label">End Date</label>
                        <input type="date" wire:model="holiday_end_date" class="att-input" />
                        @error('holiday_end_date') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="att-label">Note / Description (Optional)</label>
                        <input type="text" wire:model="holiday_description" placeholder="Optional details..." class="att-input" />
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button wire:click="addHoliday" wire:loading.attr="disabled" wire:target="addHoliday" type="button" class="att-btn att-btn-primary">
                        <x-heroicon-o-plus wire:loading.remove wire:target="addHoliday" class="w-4 h-4" />
                        <svg wire:loading wire:target="addHoliday" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="addHoliday">Add Holiday</span>
                        <span wire:loading wire:target="addHoliday">Adding...</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Declared Holidays Table ────────────────────────────────────── -->
        <div class="att-card space-y-4">
            <div class="flex items-center justify-between border-b pb-3 dark:border-slate-800">
                <h3 class="font-bold text-base dark:text-white">Declared Holidays for Current Term</h3>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                    {{ $this->holidays->count() }} Non-School {{ Str::plural('Day', $this->holidays->count()) }}
                </span>
            </div>

            @if($this->holidays->isEmpty())
                <div class="p-8 text-center text-sm text-slate-400">
                    No holidays declared for this term yet. Regular Monday–Friday school days apply.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="att-table">
                        <thead>
                            <tr>
                                <th>Holiday Title</th>
                                <th>Duration / Dates</th>
                                <th>Description</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->holidays as $holiday)
                                <tr>
                                    <td class="font-bold text-slate-800 dark:text-slate-200">
                                        🏖️ {{ $holiday->title }}
                                    </td>
                                    <td class="text-slate-600 dark:text-slate-400">
                                        {{ $holiday->start_date->format('M d, Y') }}
                                        @if(!$holiday->start_date->isSameDay($holiday->end_date))
                                            — {{ $holiday->end_date->format('M d, Y') }}
                                            <span class="text-xs text-indigo-500 font-semibold">({{ $holiday->start_date->diffInDays($holiday->end_date) + 1 }} days)</span>
                                        @endif
                                    </td>
                                    <td class="text-slate-500 dark:text-slate-400 text-xs">
                                        {{ $holiday->description ?: '—' }}
                                    </td>
                                    <td class="text-right">
                                        <button wire:click="deleteHoliday({{ $holiday->id }})" wire:confirm="Remove this holiday?" wire:loading.attr="disabled" wire:target="deleteHoliday({{ $holiday->id }})" type="button" class="att-btn att-btn-danger text-xs px-2.5 py-1">
                                            <x-heroicon-o-trash wire:loading.remove wire:target="deleteHoliday({{ $holiday->id }})" class="w-3.5 h-3.5" />
                                            <svg wire:loading wire:target="deleteHoliday({{ $holiday->id }})" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            <span wire:loading.remove wire:target="deleteHoliday({{ $holiday->id }})">Remove</span>
                                            <span wire:loading wire:target="deleteHoliday({{ $holiday->id }})">Removing...</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- ── Weekly Attendance Compliance & Unlock ─────────────────────── -->
        <div class="att-card space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b pb-3 dark:border-slate-800">
                <div>
                    <h3 class="font-bold text-base dark:text-white">Weekly Attendance Status & Admin Unlock</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Monitor teacher submissions and unlock locked registers when adjustments are needed.</p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="att-label mb-0">Select Week:</label>
                    <select wire:model.live="selected_week" class="att-select py-1.5 text-xs w-auto">
                        @foreach($this->weeks as $wNum => $wInfo)
                            <option value="{{ $wNum }}">
                                {{ $wInfo['label'] }} {{ $wInfo['is_current'] ? '★ (Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th>Classroom</th>
                            <th>Status</th>
                            <th>Submission Details</th>
                            <th class="text-right">Admin Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->classCompliance as $item)
                            <tr>
                                <td class="font-bold text-slate-800 dark:text-slate-200">
                                    {{ $item['class_name'] }}
                                </td>
                                <td>
                                    @if($item['status'] === 'locked' || $item['status'] === 'submitted')
                                        <span class="att-badge att-badge-locked">
                                            <x-heroicon-o-lock-closed class="w-3.5 h-3.5" /> Submitted &amp; Locked
                                        </span>
                                    @elseif($item['status'] === 'in_progress')
                                        <span class="att-badge att-badge-progress">
                                            <x-heroicon-o-clock class="w-3.5 h-3.5" /> In Progress (Open)
                                        </span>
                                    @else
                                        <span class="att-badge att-badge-missing">
                                            <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" /> Not Taken
                                        </span>
                                    @endif
                                </td>
                                <td class="text-xs text-slate-500 dark:text-slate-400">
                                    @if($item['submitted_by'])
                                        Submitted by <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $item['submitted_by'] }}</span><br>
                                        <span class="text-slate-400">{{ $item['submitted_at'] }}</span>
                                    @elseif($item['unlocked_by'])
                                        <span class="text-amber-600 dark:text-amber-400 font-semibold">Unlocked by {{ $item['unlocked_by'] }}</span><br>
                                        <span class="text-slate-400">{{ $item['unlocked_at'] }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($item['status'] === 'locked' || $item['status'] === 'submitted')
                                        <button wire:click="unlockClassWeek({{ $item['weekly_id'] }})"
                                                wire:confirm="Unlock Week {{ $selected_week }} attendance for {{ $item['class_name'] }}? The teacher will be able to edit."
                                                wire:loading.attr="disabled" wire:target="unlockClassWeek({{ $item['weekly_id'] }})"
                                                type="button"
                                                class="att-btn att-btn-warning text-xs px-3 py-1.5">
                                            <x-heroicon-o-lock-open wire:loading.remove wire:target="unlockClassWeek({{ $item['weekly_id'] }})" class="w-3.5 h-3.5" />
                                            <svg wire:loading wire:target="unlockClassWeek({{ $item['weekly_id'] }})" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                            <span wire:loading.remove wire:target="unlockClassWeek({{ $item['weekly_id'] }})">Unlock Week</span>
                                            <span wire:loading wire:target="unlockClassWeek({{ $item['weekly_id'] }})">Unlocking...</span>
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400">Open for editing</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
