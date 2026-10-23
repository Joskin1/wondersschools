<x-filament-panels::page>
    <style>
        .ca-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .ca-card svg { max-width: 24px; max-height: 24px; display: inline-block; vertical-align: middle; }
        .dark .ca-card { background: #131b2e; border-color: rgba(148,163,184,0.1); }
        .ca-label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin-bottom: 5px; }
        .dark .ca-label { color: #94a3b8; }
        .ca-select { width: 100%; border-radius: 0.75rem; border: 1px solid #cbd5e1; background: white; color: #1e293b; padding: 0.5rem 0.75rem; font-size: 0.8125rem; }
        .dark .ca-select { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .ca-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; border: none; cursor: pointer; transition: all 0.15s; }
        .ca-btn-primary { background: #4f46e5; color: white; }
        .ca-btn-primary:hover { background: #4338ca; }
        .ca-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
        .ca-btn-success { background: #059669; color: white; }
        .ca-btn-success:hover { background: #047857; }
        .ca-btn-lock { background: #1e293b; color: #f8fafc; border: 1px solid #334155; }
        .ca-btn-lock:hover { background: #0f172a; }
        .dark .ca-btn-lock { background: #1e293b; color: #cbd5e1; }
        
        /* Day Selector Tabs */
        .day-tab { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0.625rem 0.75rem; border-radius: 0.75rem; border: 1px solid rgba(148,163,184,0.25); background: #f8fafc; cursor: pointer; transition: all 0.15s; min-width: 70px; text-align: center; }
        .dark .day-tab { background: #0b0f19; border-color: rgba(148,163,184,0.15); }
        .day-tab:hover { border-color: #6366f1; }
        .day-tab.active { background: #4f46e5; color: white !important; border-color: #4f46e5; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.25); }
        .day-tab.active * { color: white !important; }
        .day-tab.holiday { background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.3); }
        .day-tab.future { opacity: 0.6; cursor: not-allowed; }

        /* Student Row / Card */
        .student-item { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.875rem 1rem; border-radius: 0.75rem; border: 1px solid rgba(148,163,184,0.15); background: white; transition: background 0.15s; }
        .dark .student-item { background: #0f172a; border-color: rgba(148,163,184,0.08); }
        .student-item:hover { background: #f8fafc; }
        .dark .student-item:hover { background: #1e293b; }

        /* Status Pills */
        .status-pill { display: inline-flex; align-items: center; justify-content: center; gap: 0.25rem; padding: 0.375rem 0.625rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 700; border: 1px solid; cursor: pointer; transition: all 0.1s; user-select: none; }
        .status-pill:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .pill-present { background: white; color: #059669; border-color: #a7f3d0; }
        .dark .pill-present { background: #064e3b; color: #a7f3d0; border-color: #047857; }
        .pill-present.selected { background: #059669; color: white; border-color: #059669; box-shadow: 0 2px 4px rgba(5, 150, 105, 0.3); }
        
        .pill-absent { background: white; color: #e11d48; border-color: #fecdd3; }
        .dark .pill-absent { background: #4c0519; color: #fecdd3; border-color: #9f1239; }
        .pill-absent.selected { background: #e11d48; color: white; border-color: #e11d48; box-shadow: 0 2px 4px rgba(225, 29, 72, 0.3); }
        
        .pill-late { background: white; color: #d97706; border-color: #fde68a; }
        .dark .pill-late { background: #451a03; color: #fde68a; border-color: #92400e; }
        .pill-late.selected { background: #d97706; color: white; border-color: #d97706; box-shadow: 0 2px 4px rgba(217, 119, 6, 0.3); }
        
        .pill-excused { background: white; color: #2563eb; border-color: #bfdbfe; }
        .dark .pill-excused { background: #172554; color: #bfdbfe; border-color: #1e40af; }
        .pill-excused.selected { background: #2563eb; color: white; border-color: #2563eb; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3); }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 0.85s linear infinite;
        }
    </style>

    <div class="space-y-5">
        <!-- ── Top Controls ──────────────────────────────────────────────── -->
        <div class="ca-card">
            <div wire:loading wire:target="classroom_id,class_arm_id,department_id,selected_week" style="margin-bottom: 0.75rem; font-size: 0.75rem; color: #6366f1; font-weight: 700; display: flex; align-items: center; gap: 0.375rem;">
                <svg class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Loading attendance register...
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                @if(auth()->user()?->isAdmin() || auth()->user()?->isSudo() || \App\Models\ClassTeacherAssignment::where('teacher_id', auth()->id())->count() > 1)
                    <div>
                        <label class="ca-label">Classroom</label>
                        <select wire:model.live="classroom_id" class="ca-select">
                            @foreach(\App\Models\Classroom::where('is_active', true)->orderBy('class_order')->get() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @php
                        $selectedClass = \App\Models\Classroom::find($classroom_id);
                    @endphp
                    @if($selectedClass && $selectedClass->arms->isNotEmpty())
                        <div>
                            <label class="ca-label">Class Arm</label>
                            <select wire:model.live="class_arm_id" class="ca-select">
                                <option value="">All Arms</option>
                                @foreach($selectedClass->arms as $arm)
                                    <option value="{{ $arm->id }}">{{ $arm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($selectedClass && $selectedClass->has_departments)
                        <div>
                            <label class="ca-label">Department</label>
                            <select wire:model.live="department_id" class="ca-select">
                                <option value="">All Departments</option>
                                @foreach(\App\Models\Department::where('is_active', true)->get() as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @else
                    <div class="sm:col-span-2">
                        <label class="ca-label">My Assigned Class</label>
                        <div class="font-bold text-base text-slate-800 dark:text-slate-100 flex items-center gap-2">
                            <x-heroicon-o-academic-cap class="w-5 h-5 text-indigo-500" />
                            {{ \App\Models\Classroom::find($classroom_id)?->name ?? 'Classroom' }}
                            @if($class_arm_id) — {{ \App\Models\ClassArm::find($class_arm_id)?->name }} @endif
                            @if($department_id) ({{ \App\Models\Department::find($department_id)?->name }}) @endif
                        </div>
                    </div>
                @endif

                <div class="sm:col-span-2 md:col-span-1">
                    <label class="ca-label">Academic Week</label>
                    <select wire:model.live="selected_week" class="ca-select font-semibold">
                        @foreach($this->weeks as $wNum => $wInfo)
                            <option value="{{ $wNum }}">
                                {{ $wInfo['label'] }} {{ $wInfo['is_current'] ? '★ (Current)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- ── Weekday Navigation Tabs ───────────────────────────────────── -->
        <div class="ca-card">
            <div class="flex items-center justify-between pb-3 border-b dark:border-slate-800 flex-wrap gap-2">
                <div>
                    <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200">
                        Daily Attendance Register
                    </h3>
                    <p class="text-xs text-slate-400">
                        Selected: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ \Carbon\Carbon::parse($selected_date)->format('l, F j, Y') }}</span>
                    </p>
                </div>

                @if($isCurrentWeekLocked)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                        <x-heroicon-o-lock-closed class="w-3.5 h-3.5" /> Week {{ $selected_week }} Locked
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-600 border border-amber-500/20">
                        <x-heroicon-o-pencil class="w-3.5 h-3.5" /> Week {{ $selected_week }} Open
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-5 gap-2 pt-3">
                @foreach($this->weekDays as $day)
                    <button type="button"
                            wire:click="selectDate('{{ $day['date'] }}')"
                            class="day-tab {{ $day['is_selected'] ? 'active' : '' }} {{ $day['is_holiday'] ? 'holiday' : '' }} {{ $day['is_future'] ? 'future' : '' }}">
                        <span class="text-xs font-bold">{{ $day['day_name'] }}</span>
                        <span class="text-sm font-extrabold">{{ $day['formatted_date'] }}</span>
                        
                        <div class="mt-1 text-[10px]">
                            @if($day['is_holiday'])
                                <span title="{{ $day['holiday_title'] }}">🏖️ Holiday</span>
                            @elseif($day['is_future'])
                                <span class="text-slate-400">⏳ Future</span>
                            @elseif($day['has_records'])
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">✓ Saved</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- ── Warning / Status Banner if restricted ─────────────────────── -->
        @if(!$canEditCurrentDate)
            <div class="p-4 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-950/40 dark:border-amber-800 flex items-center gap-3">
                <x-heroicon-o-information-circle class="w-6 h-6 text-amber-600 dark:text-amber-400 shrink-0" />
                <div class="text-xs md:text-sm text-amber-800 dark:text-amber-200">
                    <span class="font-bold">Attendance is Read-Only for this day:</span>
                    {{ $editRestrictionReason }}
                </div>
            </div>
        @endif

        <!-- ── Real-time Counts & Fast Actions ───────────────────────────── -->
        <div class="ca-card flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                    🟢 Present: {{ $this->counts['present'] }}
                </span>
                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300">
                    🔴 Absent: {{ $this->counts['absent'] }}
                </span>
                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                    🟡 Late: {{ $this->counts['late'] }}
                </span>
                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300">
                    🔵 Excused: {{ $this->counts['excused'] }}
                </span>
                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    👥 Total: {{ $this->counts['total'] }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                @if($canEditCurrentDate)
                    <button type="button" wire:click="markAllPresent" wire:loading.attr="disabled" wire:target="markAllPresent" class="ca-btn ca-btn-success text-xs">
                        <x-heroicon-o-check-circle wire:loading.remove wire:target="markAllPresent" class="w-4 h-4" />
                        <svg wire:loading wire:target="markAllPresent" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="markAllPresent">Mark All Present</span>
                        <span wire:loading wire:target="markAllPresent">Marking...</span>
                    </button>
                    <button type="button" wire:click="saveDailyAttendance" wire:loading.attr="disabled" wire:target="saveDailyAttendance" class="ca-btn ca-btn-primary text-xs">
                        <x-heroicon-o-bookmark wire:loading.remove wire:target="saveDailyAttendance" class="w-4 h-4" />
                        <svg wire:loading wire:target="saveDailyAttendance" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="saveDailyAttendance">Save Today</span>
                        <span wire:loading wire:target="saveDailyAttendance">Saving...</span>
                    </button>
                @endif

                @if(!$isCurrentWeekLocked)
                    <button type="button"
                            wire:click="submitWeeklyAttendance"
                            wire:confirm="Submit and lock Week {{ $selected_week }} attendance? Once locked, only an Admin can unlock it."
                            wire:loading.attr="disabled" wire:target="submitWeeklyAttendance"
                            class="ca-btn ca-btn-lock text-xs">
                        <x-heroicon-o-lock-closed wire:loading.remove wire:target="submitWeeklyAttendance" class="w-4 h-4" />
                        <svg wire:loading wire:target="submitWeeklyAttendance" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="submitWeeklyAttendance">Submit Week {{ $selected_week }}</span>
                        <span wire:loading wire:target="submitWeeklyAttendance">Submitting...</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- ── Student Roster with Exception Pills ───────────────────────── -->
        <div class="ca-card space-y-2">
            <div class="flex items-center justify-between pb-2 border-b dark:border-slate-800 text-xs font-bold text-slate-500 uppercase tracking-wider">
                <span>Student Roster ({{ $this->enrolledStudents->count() }})</span>
                <span>Attendance Status</span>
            </div>

            @if($this->enrolledStudents->isEmpty())
                <div class="p-8 text-center text-sm text-slate-400">
                    No active students enrolled in this classroom for the active session.
                </div>
            @else
                <div class="space-y-2 pt-1">
                    @foreach($this->enrolledStudents as $student)
                        @php
                            $currStatus = $records[$student->id]['status'] ?? 'present';
                        @endphp
                        <div class="student-item">
                            <!-- Student Info -->
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-extrabold text-xs flex items-center justify-center shrink-0">
                                    {{ substr($student->first_name ?? $student->full_name, 0, 1) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-slate-800 dark:text-slate-100">
                                        {{ $student->full_name }}
                                    </h4>
                                    <span class="text-xs text-slate-400">
                                        {{ $student->admission_number ?? 'No Adm No' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Status Pills -->
                            <div class="flex flex-wrap items-center gap-1.5">
                                <button type="button"
                                        @disabled(!$canEditCurrentDate)
                                        wire:click="setStudentStatus({{ $student->id }}, 'present')"
                                        class="status-pill pill-present {{ $currStatus === 'present' ? 'selected' : '' }}">
                                    ✓ Present
                                </button>
                                <button type="button"
                                        @disabled(!$canEditCurrentDate)
                                        wire:click="setStudentStatus({{ $student->id }}, 'absent')"
                                        class="status-pill pill-absent {{ $currStatus === 'absent' ? 'selected' : '' }}">
                                    ✕ Absent
                                </button>
                                <button type="button"
                                        @disabled(!$canEditCurrentDate)
                                        wire:click="setStudentStatus({{ $student->id }}, 'late')"
                                        class="status-pill pill-late {{ $currStatus === 'late' ? 'selected' : '' }}">
                                    ⏰ Late
                                </button>
                                <button type="button"
                                        @disabled(!$canEditCurrentDate)
                                        wire:click="setStudentStatus({{ $student->id }}, 'excused')"
                                        class="status-pill pill-excused {{ $currStatus === 'excused' ? 'selected' : '' }}">
                                    ★ Excused
                                </button>
                            </div>

                            <!-- Optional Remark input if absent or late -->
                            @if($currStatus !== 'present')
                                <div class="w-full pt-1">
                                    <input type="text"
                                           wire:model.defer="records.{{ $student->id }}.remark"
                                           @disabled(!$canEditCurrentDate)
                                           placeholder="Reason for absence / lateness (e.g. sick, traffic)..."
                                           class="w-full text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-3 py-1.5 text-slate-700 dark:text-slate-200" />
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
