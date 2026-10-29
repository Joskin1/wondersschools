<x-filament-panels::page>
    <style>
        .ss-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .dark .ss-card { background: #131b2e; border-color: rgba(148,163,184,0.1); }
        .ss-label { display: block; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 6px; }
        .ss-select { width: 100%; border-radius: 0.75rem; border: 1px solid #e2e8f0; background: white; color: #1e293b; padding: 0.625rem 0.875rem; font-size: 0.875rem; transition: border-color 0.15s; }
        .ss-select:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .dark .ss-select { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .ss-input { width: 100%; border-radius: 0.625rem; border: 1px solid #e2e8f0; background: white; color: #1e293b; padding: 0.5rem 0.75rem; font-size: 0.875rem; }
        .ss-input:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 2px rgba(99,102,241,0.15); }
        .dark .ss-input { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .ss-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem; }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 0.85s linear infinite;
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 1.5rem; position: relative;">
        {{-- Full-Screen Backdrop Overlay for Calculation --}}
        <div wire:loading.flex wire:target="calculateMidtermResults" style="display:none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 1.25rem; padding: 2rem 2.5rem; max-width: 440px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);" class="dark:bg-slate-900">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #e0e7ff; color: #4338ca; margin-bottom: 1.25rem;" class="dark:bg-indigo-950 dark:text-indigo-400">
                    <svg class="animate-spin" style="width: 36px; height: 36px;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.125rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem;" class="dark:text-white">Computing Mid-Term Results</h3>
                <p style="font-size: 0.875rem; color: #64748b; line-height: 1.5; margin: 0;" class="dark:text-slate-400">
                    Scaling assessment heads, computing student totals, percentages, and performance grades. Please wait...
                </p>
            </div>
        </div>

        {{-- Parameters Header Card --}}
        <div class="ss-card">
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(148,163,184,0.1);">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="display: flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: 0.75rem; background: rgba(238,242,255,1); color: #4f46e5;">
                            <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 style="font-size: 1rem; font-weight: 900; color: #0f172a;" class="dark:text-white">Process Mid-Term Results</h2>
                            <p style="font-size: 0.75rem; color: #94a3b8;">Calculate mid-term scaled marks, review completion status, record teacher remarks, and lock/publish reports.</p>
                        </div>
                    </div>
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

                <div wire:loading wire:target="session_id,term_id,classroom_id" style="font-size: 0.8rem; color: #6366f1; font-weight: 700; display: inline-flex; align-items: center; gap: 0.375rem;">
                    <svg class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Loading class records...
                </div>
            </div>
        </div>

        @if($session_id && $term_id && $classroom_id)
            @php
                $students = $this->studentsList;
                $totalEnrolled = count($students);
                $missingCount = collect($students)->where('has_missing', true)->count();
                $snapshot = $this->snapshot;
                $hasCalculated = collect($students)->whereNotNull('total_obtained')->count() > 0;
            @endphp

            {{-- Completeness Warning Banner & Detailed Breakdown --}}
            @if($missingCount > 0)
                @php
                    $breakdown = $this->missingScoresBreakdown;
                @endphp
                <div x-data="{ showBreakdown: true }" style="border-radius: 0.75rem; background: #fff1f2; border: 1px solid #fecdd3; padding: 1.25rem; margin-bottom: 1.5rem;">
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <div style="color: #e11d48; margin-top: 2px;">
                            <svg style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                                <div>
                                    <h4 style="font-size: 0.95rem; font-weight: 800; color: #9f1239;">Incomplete Mid-Term Scores Detected</h4>
                                    <p style="font-size: 0.8rem; color: #be123c; margin-top: 0.25rem;">
                                        <strong>{{ $missingCount }} student(s)</strong> have unrecorded or missing assessment heads. To lock or release results, the teacher must enter a score or mark <strong>ABS</strong> for each missing head below.
                                    </p>
                                </div>
                                @if(!empty($breakdown))
                                    <button type="button" @click="showBreakdown = !showBreakdown" style="font-size: 0.75rem; font-weight: 700; color: #be123c; background: #fee2e2; border: 1px solid #fca5a5; padding: 0.35rem 0.75rem; border-radius: 0.375rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.35rem;">
                                        <span x-show="showBreakdown">Hide Breakdown ▲</span>
                                        <span x-show="!showBreakdown">View Breakdown ({{ count($breakdown) }} Items) ▼</span>
                                    </button>
                                @endif
                            </div>

                            @if(!empty($breakdown))
                                <div x-show="showBreakdown" style="margin-top: 1rem; border-top: 1px solid #fecdd3; padding-top: 0.75rem;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #9f1239;">
                                            Missing Scores Breakdown ({{ count($breakdown) }} Item{{ count($breakdown) > 1 ? 's' : '' }})
                                        </span>
                                    </div>

                                    <div style="overflow-x: auto; background: white; border-radius: 0.5rem; border: 1px solid #fecdd3; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                        <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem; text-align: left;">
                                            <thead>
                                                <tr style="background: #fff5f5; border-bottom: 1px solid #fecdd3;">
                                                    <th style="padding: 0.6rem 0.85rem; font-weight: 800; color: #9f1239; font-size: 11px; text-transform: uppercase;">Student</th>
                                                    <th style="padding: 0.6rem 0.85rem; font-weight: 800; color: #9f1239; font-size: 11px; text-transform: uppercase;">Adm No.</th>
                                                    <th style="padding: 0.6rem 0.85rem; font-weight: 800; color: #9f1239; font-size: 11px; text-transform: uppercase;">Subject</th>
                                                    <th style="padding: 0.6rem 0.85rem; font-weight: 800; color: #9f1239; font-size: 11px; text-transform: uppercase;">Missing Assessment Head(s)</th>
                                                    <th style="padding: 0.6rem 0.85rem; font-weight: 800; color: #9f1239; font-size: 11px; text-transform: uppercase; text-align: right;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($breakdown as $item)
                                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                                        <td style="padding: 0.6rem 0.85rem; font-weight: 700; color: #1e293b;">
                                                            {{ $item['student_name'] }}
                                                        </td>
                                                        <td style="padding: 0.6rem 0.85rem; color: #64748b; font-family: monospace;">
                                                            {{ $item['admission_number'] }}
                                                        </td>
                                                        <td style="padding: 0.6rem 0.85rem; font-weight: 600; color: #334155;">
                                                            {{ $item['subject_name'] }}
                                                        </td>
                                                        <td style="padding: 0.6rem 0.85rem;">
                                                            <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                                                @forelse($item['missing_heads'] as $headName)
                                                                    <span style="display: inline-flex; align-items: center; padding: 0.15rem 0.5rem; border-radius: 0.25rem; font-size: 0.72rem; font-weight: 700; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;">
                                                                        ⚠️ {{ $headName }}
                                                                    </span>
                                                                @empty
                                                                    <span style="color: #64748b; font-size: 0.75rem;">All heads missing</span>
                                                                @endforelse
                                                            </div>
                                                        </td>
                                                        <td style="padding: 0.6rem 0.85rem; text-align: right;">
                                                            <a href="{{ $item['url'] }}" target="_blank" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.35rem 0.75rem; border-radius: 0.375rem; background: #e11d48; color: white; text-decoration: none; font-size: 0.75rem; font-weight: 700; transition: background 0.15s;" onmouseover="this.style.background='#be123c'" onmouseout="this.style.background='#e11d48'">
                                                                <span>Enter Score</span>
                                                                <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Summary Stats Grid --}}
            <div class="ss-grid">
                <div class="ss-card">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Class Enrollment</p>
                    <p style="font-size: 1.75rem; font-weight: 900; margin-top: 0.5rem; color: #4f46e5;">
                        {{ $totalEnrolled }}
                    </p>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Enrolled Students</p>
                </div>

                <div class="ss-card">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Calculation Status</p>
                    <div style="margin-top: 0.5rem;">
                        @if($hasCalculated)
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 800; background: #d1fae5; color: #065f46;">
                                ✓ Calculated
                            </span>
                        @else
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 800; background: #fef3c7; color: #92400e;">
                                ● Pending Calculation
                            </span>
                        @endif
                    </div>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Weight: {{ $snapshot?->midterm_weight ?? '—' }}%</p>
                </div>

                <div class="ss-card">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Lock Status</p>
                    <div style="margin-top: 0.5rem;">
                        @if($isLocked)
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 800; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                                🔒 Locked
                            </span>
                        @else
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 800; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">
                                🔓 Unlocked (Open)
                            </span>
                        @endif
                    </div>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                        {{ $isLocked ? 'By ' . ($lockedByName ?? 'Admin') : 'Scores Editable' }}
                    </p>
                </div>

                <div class="ss-card" style="display: flex; flex-direction: column; justify-content: center; align-items: flex-start;">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.75rem;">Action</p>
                    <button type="button" wire:click="calculateMidtermResults" wire:loading.attr="disabled" wire:target="calculateMidtermResults" @disabled($isLocked) style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.25rem; border-radius: 0.75rem; background: {{ $isLocked ? '#94a3b8' : '#4f46e5' }}; color: white; font-size: 0.875rem; font-weight: 800; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }}; border: none;">
                        <svg wire:loading.remove wire:target="calculateMidtermResults" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg wire:loading wire:target="calculateMidtermResults" class="animate-spin" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="calculateMidtermResults">Compute Mid-Term</span>
                        <span wire:loading wire:target="calculateMidtermResults">Calculating...</span>
                    </button>
                </div>
            </div>

            {{-- Unscored Class Subjects Confirmation Banner --}}
            @if($showUnscoredLockWarning)
                <div style="border-radius: 0.75rem; background: #fffbeb; border: 1px solid #fde68a; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                        <div style="color: #d97706; margin-top: 2px;">
                            <svg style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <h4 style="font-size: 0.875rem; font-weight: 800; color: #92400e;">Unscored Subject(s) Detected</h4>
                            <p style="font-size: 0.75rem; color: #b45309; margin-top: 0.25rem;">
                                The following assigned subject(s) have no score entries recorded for any student in this class: <strong>{{ $unscoredClassSubjectsText }}</strong>.
                                If this class does not take these subjects, or if you intend to proceed without them, please confirm to lock mid-term results.
                            </p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" wire:click="cancelLockWithUnscored" wire:loading.attr="disabled" wire:target="cancelLockWithUnscored" style="padding: 0.5rem 1rem; border-radius: 0.5rem; background: #f3f4f6; color: #374151; font-size: 0.8125rem; font-weight: 700; border: 1px solid #d1d5db; cursor: pointer;">
                            Cancel
                        </button>
                        <button type="button" wire:click="confirmLockWithUnscored" wire:loading.attr="disabled" wire:target="confirmLockWithUnscored" style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 0.5rem; background: #b45309; color: white; font-size: 0.8125rem; font-weight: 700; border: none; cursor: pointer;">
                            <svg wire:loading wire:target="confirmLockWithUnscored" class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="confirmLockWithUnscored">Confirm &amp; Lock Results</span>
                            <span wire:loading wire:target="confirmLockWithUnscored">Locking...</span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Admin Control Card --}}
            @if($this->canApprove)
                <div class="ss-card" style="border: 2px solid {{ $isPublished ? '#10b981' : ($isLocked ? '#3b82f6' : '#f59e0b') }};">
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                            <div>
                                <h3 style="font-size: 1rem; font-weight: 900; color: #0f172a;" class="dark:text-white">
                                    Admin Mid-Term Control &amp; Release
                                </h3>
                                <p style="font-size: 0.75rem; color: #64748b;">
                                    Lock scores to freeze editing, or publish mid-term reports to make them visible on the student portal.
                                </p>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                                @if(! $isLocked)
                                    <button type="button" wire:click="lockMidtermResults" wire:loading.attr="disabled" wire:target="lockMidtermResults" @disabled($missingCount > 0) style="padding: 0.625rem 1.25rem; border-radius: 0.625rem; background: #0f172a; color: white; font-size: 0.875rem; font-weight: 700; border: none; cursor: {{ $missingCount > 0 ? 'not-allowed' : 'pointer' }}; opacity: {{ $missingCount > 0 ? '0.5' : '1' }}; display: inline-flex; align-items: center; gap: 0.375rem;">
                                        <svg wire:loading wire:target="lockMidtermResults" class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="lockMidtermResults">🔒 Lock Results</span>
                                        <span wire:loading wire:target="lockMidtermResults">Locking...</span>
                                    </button>
                                @else
                                    <button type="button" wire:click="unlockMidtermResults" wire:loading.attr="disabled" wire:target="unlockMidtermResults" style="padding: 0.625rem 1.25rem; border-radius: 0.625rem; background: #e2e8f0; color: #0f172a; font-size: 0.875rem; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.375rem;">
                                        <svg wire:loading wire:target="unlockMidtermResults" class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="unlockMidtermResults">🔓 Unlock Results</span>
                                        <span wire:loading wire:target="unlockMidtermResults">Unlocking...</span>
                                    </button>
                                @endif

                                @if(! $isPublished)
                                    <button type="button" wire:click="publishMidtermResults" wire:loading.attr="disabled" wire:target="publishMidtermResults" @disabled($missingCount > 0) style="padding: 0.625rem 1.25rem; border-radius: 0.625rem; background: #16a34a; color: white; font-size: 0.875rem; font-weight: 700; border: none; cursor: {{ $missingCount > 0 ? 'not-allowed' : 'pointer' }}; opacity: {{ $missingCount > 0 ? '0.5' : '1' }}; display: inline-flex; align-items: center; gap: 0.375rem;">
                                        <svg wire:loading wire:target="publishMidtermResults" class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="publishMidtermResults">📢 Release / Publish to Portal</span>
                                        <span wire:loading wire:target="publishMidtermResults">Publishing...</span>
                                    </button>
                                @else
                                    <button type="button" wire:click="unpublishMidtermResults" wire:loading.attr="disabled" wire:target="unpublishMidtermResults" style="padding: 0.625rem 1.25rem; border-radius: 0.625rem; background: #dc2626; color: white; font-size: 0.875rem; font-weight: 700; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 0.375rem;">
                                        <svg wire:loading wire:target="unpublishMidtermResults" class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="unpublishMidtermResults">🚫 Hide / Unpublish</span>
                                        <span wire:loading wire:target="unpublishMidtermResults">Unpublishing...</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if($isPublished)
                            <div style="padding: 0.75rem 1rem; border-radius: 0.5rem; background: #ecfdf5; border: 1px solid #a7f3d0; font-size: 0.75rem; color: #065f46; font-weight: 600;">
                                ✓ Published to student portal by {{ $publishedByName ?? 'Admin' }} on {{ $publishedAt ?? '—' }}.
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Students List & Comments Card --}}
            <div class="ss-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(148,163,184,0.1); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 900; color: #0f172a;" class="dark:text-white">
                            Students &amp; Comments
                        </h3>
                        <p style="font-size: 0.75rem; color: #64748b;">
                            Review student totals and enter class teacher &amp; head teacher observations.
                        </p>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        @if($this->canApprove)
                            @php
                                $midtermClassPdfUrl = route('admin.results.midterm-class-pdf', [
                                    'session_id'   => $session_id,
                                    'term_id'      => $term_id,
                                    'classroom_id' => $classroom_id,
                                ]);
                            @endphp
                            <a href="{{ $midtermClassPdfUrl }}" target="_blank" style="padding: 0.625rem 1.25rem; border-radius: 0.625rem; background: #dc2626; color: white; font-size: 0.875rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 0.375rem; transition: background 0.15s;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                                <svg style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>Print Class Slips (PDF)</span>
                            </a>
                        @endif

                        <button type="button" wire:click="saveComments" wire:loading.attr="disabled" wire:target="saveComments" @disabled($isLocked) style="padding: 0.625rem 1.25rem; border-radius: 0.625rem; background: {{ $isLocked ? '#94a3b8' : '#4f46e5' }}; color: white; font-size: 0.875rem; font-weight: 700; border: none; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }}; display: inline-flex; align-items: center; gap: 0.375rem;">
                            <svg wire:loading wire:target="saveComments" class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="saveComments">💾 Save Remarks</span>
                            <span wire:loading wire:target="saveComments">Saving...</span>
                        </button>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;" class="dark:bg-[#0b0f19] dark:border-[#1e293b]">
                                <th style="padding: 0.75rem 1rem; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase;">#</th>
                                <th style="padding: 0.75rem 1rem; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase;">Student</th>
                                <th style="padding: 0.75rem 1rem; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; text-align: center;">Subjects</th>
                                <th style="padding: 0.75rem 1rem; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; text-align: right;">Obtained / Total</th>
                                <th style="padding: 0.75rem 1rem; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; text-align: center;">%</th>
                                <th style="padding: 0.75rem 1rem; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; text-align: center;">Grade</th>
                                <th style="padding: 0.75rem 1rem; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; width: 220px;">Teacher Comment</th>
                                <th style="padding: 0.75rem 1rem; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; width: 220px;">Head Teacher Comment</th>
                                @if($this->canApprove)
                                    <th style="padding: 0.75rem 1rem; font-size: 11px; font-weight: 900; color: #64748b; text-transform: uppercase; text-align: center; width: 60px;">Print</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $student)
                                <tr style="border-bottom: 1px solid rgba(148,163,184,0.1);" class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                    <td style="padding: 0.875rem 1rem; font-weight: 700; color: #94a3b8;">{{ $index + 1 }}</td>
                                    <td style="padding: 0.875rem 1rem;">
                                        <div style="font-weight: 800; color: #0f172a;" class="dark:text-white">{{ $student['full_name'] }}</div>
                                        <div style="font-size: 11px; color: #64748b; font-family: monospace;">{{ $student['admission_number'] }}</div>
                                        @if($student['has_missing'])
                                            <div style="margin-top: 3px;">
                                                <span style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 10px; font-weight: 800; color: #be123c; background: #ffe4e6; padding: 0.15rem 0.45rem; border-radius: 0.25rem; border: 1px solid #fecdd3;" title="{{ implode(', ', $student['missing_subjects'] ?? []) }}">
                                                    ⚠️ Incomplete: {{ !empty($student['missing_subjects']) ? implode(', ', $student['missing_subjects']) : $student['missing_count'] . ' subject(s)' }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="padding: 0.875rem 1rem; text-align: center; font-weight: 700;">
                                        {{ $student['subjects_count'] }}
                                    </td>
                                    <td style="padding: 0.875rem 1rem; text-align: right; font-weight: 800;">
                                        @if($student['total_obtained'] !== null)
                                            <span style="color: #4f46e5;">{{ is_numeric($student['total_obtained']) ? (int) ceil((float) $student['total_obtained']) : $student['total_obtained'] }}</span>
                                            <span style="font-size: 11px; color: #94a3b8;">/ {{ is_numeric($student['total_obtainable']) ? (int) ceil((float) $student['total_obtainable']) : $student['total_obtainable'] }}</span>
                                        @else
                                            <span style="color: #94a3b8;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.875rem 1rem; text-align: center; font-weight: 800;">
                                        @if($student['percentage'] !== null)
                                            {{ is_numeric($student['percentage']) ? (int) ceil((float) $student['percentage']) . '%' : $student['percentage'] . '%' }}
                                        @else
                                            <span style="color: #94a3b8;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.875rem 1rem; text-align: center;">
                                        @if($student['grade'] && $student['grade'] !== '—')
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 0.5rem; font-weight: 900; font-size: 12px; background: #e0e7ff; color: #4338ca;">
                                                {{ $student['grade'] }}
                                            </span>
                                        @else
                                            <span style="color: #94a3b8;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding: 0.875rem 1rem;">
                                        <input type="text"
                                               wire:model.defer="comments.{{ $student['student_id'] }}.teacher_comment"
                                               placeholder="Remark..."
                                               @disabled($isLocked)
                                               class="ss-input" />
                                    </td>
                                    <td style="padding: 0.875rem 1rem;">
                                        <input type="text"
                                               wire:model.defer="comments.{{ $student['student_id'] }}.head_teacher_comment"
                                               placeholder="{{ $this->canApprove ? 'Admin observation...' : 'Locked' }}"
                                               @disabled(! $this->canApprove || $isLocked)
                                               class="ss-input" />
                                    </td>
                                    @if($this->canApprove)
                                        <td style="padding: 0.75rem 1rem; text-align: center;">
                                            <a href="{{ route('student.midterm-pdf', ['session_id' => $session_id, 'term_id' => $term_id, 'student_id' => $student['student_id']]) }}" target="_blank" title="Print Student Midterm Slip" style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 0.375rem; background: #fee2e2; color: #dc2626; text-decoration: none; transition: background 0.15s;" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">
                                                <svg style="width: 15px; height: 15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            </a>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $this->canApprove ? 9 : 8 }}" style="padding: 2.5rem; text-align: center; color: #94a3b8;">
                                        No students enrolled in this class.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(! empty($students))
                    <div style="padding: 1.25rem 1.5rem; border-top: 1px solid rgba(148,163,184,0.1); display: flex; justify-content: flex-end;">
                        <button type="button" wire:click="saveComments" wire:loading.attr="disabled" wire:target="saveComments" @disabled($isLocked) style="padding: 0.625rem 1.5rem; border-radius: 0.625rem; background: {{ $isLocked ? '#94a3b8' : '#4f46e5' }}; color: white; font-size: 0.875rem; font-weight: 700; border: none; cursor: {{ $isLocked ? 'not-allowed' : 'pointer' }}; display: inline-flex; align-items: center; gap: 0.375rem;">
                            <svg wire:loading wire:target="saveComments" class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="saveComments">💾 Save Remarks</span>
                            <span wire:loading wire:target="saveComments">Saving...</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
