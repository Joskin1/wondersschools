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
        @media (min-width: 768px) { .ss-grid { grid-template-columns: repeat(4, 1fr); } }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 0.85s linear infinite;
        }
    </style>

    <div x-data="{ isUploading: false, uploadProgress: 0 }"
         x-on:livewire-upload-start="isUploading = true; uploadProgress = 0"
         x-on:livewire-upload-finish="isUploading = false"
         x-on:livewire-upload-error="isUploading = false; alert('Spreadsheet upload failed. Please verify the file and try again.')"
         x-on:livewire-upload-progress="uploadProgress = $event.detail.progress"
         style="display: flex; flex-direction: column; gap: 1.5rem; position: relative;">

        {{-- ── Full-Screen Backdrop Overlays ────────────────────────────────── --}}
        <div wire:loading.flex wire:target="processClassResults" style="display:none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 1.25rem; padding: 2rem 2.5rem; max-width: 440px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);" class="dark:bg-slate-900">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #e0e7ff; color: #4338ca; margin-bottom: 1.25rem;" class="dark:bg-indigo-950 dark:text-indigo-400">
                    <svg class="animate-spin" style="width: 36px; height: 36px;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.125rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem;" class="dark:text-white">Calculating Class Results</h3>
                <p style="font-size: 0.875rem; color: #64748b; line-height: 1.5; margin: 0;" class="dark:text-slate-400">
                    Computing student scores, competition rankings, averages, and overall class positions. Please wait...
                </p>
            </div>
        </div>

        <div wire:loading.flex wire:target="approveClassResults" style="display:none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 1.25rem; padding: 2rem 2.5rem; max-width: 440px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);" class="dark:bg-slate-900">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #dcfce7; color: #059669; margin-bottom: 1.25rem;" class="dark:bg-emerald-950 dark:text-emerald-400">
                    <svg class="animate-spin" style="width: 36px; height: 36px;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.125rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem;" class="dark:text-white">Releasing Results to Students</h3>
                <p style="font-size: 0.875rem; color: #64748b; line-height: 1.5; margin: 0;" class="dark:text-slate-400">
                    Finalizing official administrative release and enabling student portal access. Please wait...
                </p>
            </div>
        </div>

        <div x-show="isUploading" x-cloak style="position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 1.25rem; padding: 2rem 2.5rem; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);" class="dark:bg-slate-900">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #e0e7ff; color: #4338ca; margin-bottom: 1.25rem;" class="dark:bg-indigo-950 dark:text-indigo-400">
                    <svg class="animate-spin" style="width: 36px; height: 36px;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.125rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem;" class="dark:text-white">Uploading Remarks Spreadsheet</h3>
                <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 1rem;" class="dark:text-slate-400">
                    Transferring file to server... <span x-text="uploadProgress + '%'" style="font-weight: 800; color: #4338ca;"></span>
                </p>
                <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 9999px; overflow: hidden;" class="dark:bg-slate-800">
                    <div style="height: 100%; background: #4f46e5; transition: width 0.2s;" :style="'width: ' + uploadProgress + '%'"></div>
                </div>
            </div>
        </div>

        <div wire:loading.flex wire:target="uploadFile" style="display:none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 1.25rem; padding: 2rem 2.5rem; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);" class="dark:bg-slate-900">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #e0f2fe; color: #0284c7; margin-bottom: 1.25rem;" class="dark:bg-sky-950 dark:text-sky-400">
                    <svg class="animate-spin" style="width: 36px; height: 36px;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.125rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem;" class="dark:text-white">Parsing Remarks &amp; Attendance</h3>
                <p style="font-size: 0.875rem; color: #64748b; line-height: 1.5; margin: 0;" class="dark:text-slate-400">
                    Reading spreadsheet records and updating student comments. Please wait...
                </p>
            </div>
        </div>
        {{-- Parameters Header Card --}}
        <div class="ss-card">
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(148,163,184,0.1);">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="display: flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: 0.75rem; background: rgba(238,242,255,1); color: #4f46e5;">
                            <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h2 style="font-size: 1rem; font-weight: 900; color: #0f172a;" class="dark:text-white">Process Class Results</h2>
                            <p style="font-size: 0.75rem; color: #94a3b8;">Review subject status, calculate overall class results, manage teacher remarks &amp; attendance, and finalize admin approval.</p>
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
                $subjects = $this->subjectStatusList;
                $publishedCount = collect($subjects)->where('is_published', true)->count();
                $totalCount = count($subjects);
                $isFinalized = $this->isClassFinalized;
                $students = $this->studentsList;
                $isApproved = $this->approvalStatus === 'approved';
            @endphp

            {{-- Summary Stats Grid --}}
            <div class="ss-grid">
                <div class="ss-card">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Subject Publishing</p>
                    <p style="font-size: 1.75rem; font-weight: 900; margin-top: 0.5rem; color: #4f46e5;">
                        {{ $publishedCount }} / {{ $totalCount }}
                    </p>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Subjects Published</p>
                </div>

                <div class="ss-card">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Calculation Status</p>
                    <div style="margin-top: 0.5rem;">
                        @if($isFinalized)
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 800; background: #d1fae5; color: #065f46;">
                                ✓ Scores Calculated
                            </span>
                        @else
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 800; background: #fef3c7; color: #92400e;">
                                ● Pending Calculation
                            </span>
                        @endif
                    </div>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Overall Totals &amp; Positions</p>
                </div>

                <div class="ss-card">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Admin Release Status</p>
                    <div style="margin-top: 0.5rem;">
                        @if($isApproved)
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 800; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                ✓ Released to Students
                            </span>
                        @else
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 800; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
                                🔒 Pending Admin Approval
                            </span>
                        @endif
                    </div>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Student Portal Visibility</p>
                </div>

                <div class="ss-card" style="display: flex; flex-direction: column; justify-content: center; align-items: flex-start;">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.75rem;">Action</p>
                    <button type="button"
                            wire:click="processClassResults"
                            wire:loading.attr="disabled"
                            wire:target="processClassResults"
                            @disabled($isApproved)
                            style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.25rem; border-radius: 0.75rem; background: {{ $isApproved ? '#94a3b8' : '#4f46e5' }}; color: white; font-size: 0.875rem; font-weight: 800; cursor: {{ $isApproved ? 'not-allowed' : 'pointer' }}; opacity: {{ $isApproved ? '0.65' : '1' }}; border: none; transition: background 0.15s;"
                            @if(!$isApproved) onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'" @endif>
                        @if($isApproved)
                            <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Scores Locked (Released)</span>
                        @else
                            <svg wire:loading.remove wire:target="processClassResults" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <svg wire:loading wire:target="processClassResults" class="animate-spin" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="processClassResults">Calculate Class Scores</span>
                            <span wire:loading wire:target="processClassResults">Calculating Scores...</span>
                        @endif
                    </button>
                    @if($isApproved)
                        <span style="font-size: 10px; color: #94a3b8; margin-top: 5px; font-weight: 600;">Revert approval to recalculate.</span>
                    @endif
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════════════════
                 ADMIN FINAL APPROVAL & RESULT RELEASE SECTION
            ══════════════════════════════════════════════════════════════════ --}}
            @if($this->canApprove)
                <div class="ss-card" style="border: 2px solid {{ $isApproved ? '#10b981' : '#f59e0b' }}; background: {{ $isApproved ? 'rgba(240, 253, 244, 0.5)' : 'rgba(255, 251, 235, 0.5)' }};">
                    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid rgba(148,163,184,0.2); padding-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="display: flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: 0.75rem; background: {{ $isApproved ? '#10b981' : '#f59e0b' }}; color: white;">
                                    <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div>
                                    <h3 style="font-size: 1rem; font-weight: 900; color: #0f172a;" class="dark:text-white">
                                        Admin Final Approval &amp; Result Release
                                    </h3>
                                    <p style="font-size: 0.75rem; color: #64748b;">
                                        Input total school days opened and resumption date to release results to students.
                                    </p>
                                </div>
                            </div>

                            @if($isApproved)
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="font-size: 0.75rem; color: #065f46; font-weight: 700;">
                                        Approved by {{ $approvedByName ?? 'Admin' }} on {{ $approvedAt ?? '—' }}
                                    </span>
                                    <button type="button" wire:click="unapproveClassResults" wire:confirm="Are you sure you want to revert approval? Results will be hidden from students." wire:loading.attr="disabled" wire:target="unapproveClassResults" style="padding: 0.4rem 0.85rem; border-radius: 0.5rem; background: #ef4444; color: white; font-size: 0.75rem; font-weight: 800; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 0.375rem;">
                                        <svg wire:loading wire:target="unapproveClassResults" class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="unapproveClassResults">Revert Approval</span>
                                        <span wire:loading wire:target="unapproveClassResults">Reverting...</span>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 1.25rem;" class="sm:grid-cols-3">
                            <div>
                                <label class="ss-label">Times School Opened (Days)</label>
                                <input type="number" wire:model="times_school_opened" placeholder="e.g. 114" min="1" class="ss-input" @disabled($isApproved) style="{{ $isApproved ? 'background: #f1f5f9; cursor: not-allowed; opacity: 0.75;' : '' }}" />
                                @error('times_school_opened') <span style="color:#ef4444;font-size:11px;">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="ss-label">Next Term Begins</label>
                                <input type="text" wire:model="next_term_begins" placeholder="e.g. 12th January, 2026" class="ss-input" @disabled($isApproved) style="{{ $isApproved ? 'background: #f1f5f9; cursor: not-allowed; opacity: 0.75;' : '' }}" />
                                @error('next_term_begins') <span style="color:#ef4444;font-size:11px;">{{ $message }}</span> @enderror
                            </div>

                            <div style="display: flex; flex-direction: column; justify-content: flex-end;">
                                <button type="button"
                                        wire:click="approveClassResults"
                                        wire:loading.attr="disabled"
                                        wire:target="approveClassResults"
                                        @disabled($isApproved)
                                        style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.65rem 1.25rem; border-radius: 0.625rem; background: {{ $isApproved ? '#94a3b8' : '#10b981' }}; color: white; font-size: 0.875rem; font-weight: 800; cursor: {{ $isApproved ? 'not-allowed' : 'pointer' }}; opacity: {{ $isApproved ? '0.65' : '1' }}; border: none; transition: background 0.15s;"
                                        @if(!$isApproved) onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#10b981'" @endif>
                                    @if($isApproved)
                                        <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <span>Results Released to Students</span>
                                    @else
                                        <svg wire:loading.remove wire:target="approveClassResults" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <svg wire:loading wire:target="approveClassResults" class="animate-spin" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24">
                                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        <span wire:loading.remove wire:target="approveClassResults">Approve &amp; Release Results</span>
                                        <span wire:loading wire:target="approveClassResults">Releasing Results...</span>
                                    @endif
                                </button>
                                @if($isApproved)
                                    <span style="font-size: 10px; color: #047857; margin-top: 5px; font-weight: 600; text-align: center;">Revert approval above to update dates or recalculate.</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ══════════════════════════════════════════════════════════════════
                 STUDENT REMARKS & ATTENDANCE SECTION
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="ss-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(148,163,184,0.1); background: rgba(248,250,252,0.5); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;" class="dark:bg-slate-900/40">
                    <div>
                        <h3 style="font-size: 0.95rem; font-weight: 900;" class="dark:text-white">Student Remarks &amp; Attendance</h3>
                        <p style="font-size: 0.75rem; color: #64748b;">Class Teacher: enter days present and remarks for each student, or export/import via Excel spreadsheet.</p>
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        {{-- Sync Daily Attendance --}}
                        <button type="button" wire:click="syncFromAttendance" wire:loading.attr="disabled" wire:target="syncFromAttendance" style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 0.875rem; border-radius: 0.625rem; background: #6366f1; color: white; font-size: 0.8rem; font-weight: 800; cursor: pointer; border: none; transition: background 0.15s;" onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'">
                            <svg wire:loading.remove wire:target="syncFromAttendance" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <svg wire:loading wire:target="syncFromAttendance" class="animate-spin" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="syncFromAttendance">Sync Daily Attendance</span>
                            <span wire:loading wire:target="syncFromAttendance">Syncing...</span>
                        </button>

                        {{-- Export Excel Template --}}
                        <button type="button" wire:click="exportRemarksSpreadsheet" wire:loading.attr="disabled" wire:target="exportRemarksSpreadsheet" style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 0.875rem; border-radius: 0.625rem; background: #059669; color: white; font-size: 0.8rem; font-weight: 800; cursor: pointer; border: none; transition: background 0.15s;" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                            <svg wire:loading.remove wire:target="exportRemarksSpreadsheet" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <svg wire:loading wire:target="exportRemarksSpreadsheet" class="animate-spin" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="exportRemarksSpreadsheet">Export Excel (.xlsx)</span>
                            <span wire:loading wire:target="exportRemarksSpreadsheet">Exporting...</span>
                        </button>

                        {{-- Import Excel File --}}
                        <label style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 0.875rem; border-radius: 0.625rem; background: #0284c7; color: white; font-size: 0.8rem; font-weight: 800; cursor: pointer; border: none; transition: background 0.15s; margin: 0;" onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">
                            <svg wire:loading.remove wire:target="uploadFile" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            <svg wire:loading wire:target="uploadFile" class="animate-spin" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="uploadFile">Import Filled Excel</span>
                            <span wire:loading wire:target="uploadFile">Uploading...</span>
                            <input type="file" wire:model.live="uploadFile" accept=".xlsx,.xls" style="display: none;" />
                        </label>

                        {{-- Save Inline Remarks --}}
                        <button type="button" wire:click="saveRemarks" wire:loading.attr="disabled" wire:target="saveRemarks" style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 0.625rem; background: #4f46e5; color: white; font-size: 0.8rem; font-weight: 800; cursor: pointer; border: none; transition: background 0.15s;" onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                            <svg wire:loading.remove wire:target="saveRemarks" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <svg wire:loading wire:target="saveRemarks" class="animate-spin" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="saveRemarks">Save Remarks &amp; Attendance</span>
                            <span wire:loading wire:target="saveRemarks">Saving...</span>
                        </button>
                    </div>
                </div>

                <div wire:loading wire:target="uploadFile" style="padding: 1rem; background: #e0f2fe; color: #0369a1; text-align: center; font-size: 0.875rem; font-weight: 700;">
                    Uploading &amp; parsing spreadsheet... Please wait.
                </div>

                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid rgba(148,163,184,0.1); font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800;">
                            <th style="padding: 0.875rem 1rem; width: 60px;">S/N</th>
                            <th style="padding: 0.875rem 1rem; width: 130px;">Adm No</th>
                            <th style="padding: 0.875rem 1rem; width: 200px;">Student Name</th>
                            <th style="padding: 0.875rem 1rem; width: 140px;">Division</th>
                            <th style="padding: 0.875rem 1rem; text-align: center; width: 90px;">Average</th>
                            <th style="padding: 0.875rem 1rem; text-align: center; width: 90px;">Position</th>
                            <th style="padding: 0.875rem 1rem; width: 140px;">Times Present</th>
                            <th style="padding: 0.875rem 1rem;">Class Teacher's Comment</th>
                            <th style="padding: 0.875rem 1rem;">Principal's Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $idx => $st)
                            <tr style="border-bottom: 1px solid rgba(148,163,184,0.05);">
                                <td style="padding: 0.875rem 1rem; color: #64748b;">{{ $idx + 1 }}</td>
                                <td style="padding: 0.875rem 1rem; font-weight: 700; color: #475569;">{{ $st['admission_number'] }}</td>
                                <td style="padding: 0.875rem 1rem; font-weight: 800;" class="dark:text-white">{{ $st['full_name'] }}</td>
                                <td style="padding: 0.875rem 1rem;">
                                    @if($st['department'])
                                        <span style="display: inline-flex; padding: 0.2rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 700; background: #e0e7ff; color: #3730a3;">
                                            Dept: {{ $st['department'] }}
                                        </span>
                                    @elseif($st['arm'])
                                        <span style="display: inline-flex; padding: 0.2rem 0.5rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 700; background: #fef3c7; color: #92400e;">
                                            Arm: {{ $st['arm'] }}
                                        </span>
                                    @else
                                        <span style="color: #94a3b8;">—</span>
                                    @endif
                                </td>
                                <td style="padding: 0.875rem 1rem; text-align: center; font-weight: 800; color: #4f46e5;">
                                    {{ $st['average'] !== null ? (is_numeric($st['average']) ? (int) ceil((float) $st['average']) . '%' : $st['average'] . '%') : '—' }}
                                </td>
                                <td style="padding: 0.875rem 1rem; text-align: center; font-weight: 800;">
                                    @if(!empty($st['department_position']))
                                        <div style="font-size:0.85rem;font-weight:800;color:#1e293b;" class="dark:text-white">
                                            {{ $st['department_position'] }}<span style="font-size:0.7rem;color:#64748b;font-weight:normal;">/{{ $st['department_population'] }}</span>
                                        </div>
                                        <div style="font-size:0.65rem;color:#94a3b8;font-weight:600;">Set: {{ $st['position'] }}/{{ $st['class_population'] }}</div>
                                    @elseif(!empty($st['arm_position']))
                                        <div style="font-size:0.85rem;font-weight:800;color:#1e293b;" class="dark:text-white">
                                            {{ $st['arm_position'] }}<span style="font-size:0.7rem;color:#64748b;font-weight:normal;">/{{ $st['arm_population'] }}</span>
                                        </div>
                                        <div style="font-size:0.65rem;color:#94a3b8;font-weight:600;">Set: {{ $st['position'] }}/{{ $st['class_population'] }}</div>
                                    @else
                                        <div style="font-size:0.85rem;font-weight:800;">{{ $st['position'] ?? '—' }}</div>
                                    @endif
                                </td>
                                <td style="padding: 0.5rem 1rem;">
                                    <input type="number" wire:model.defer="remarks.{{ $st['student_id'] }}.times_present" placeholder="Days" min="0" class="ss-input" style="padding: 0.375rem 0.5rem; font-size: 0.8rem; text-align: center;" />
                                </td>
                                <td style="padding: 0.5rem 1rem;">
                                    <input type="text" wire:model.defer="remarks.{{ $st['student_id'] }}.class_teacher_comment" placeholder="Enter remark (e.g. An attentive and hardworking student)" class="ss-input" style="padding: 0.375rem 0.5rem; font-size: 0.8rem;" />
                                </td>
                                <td style="padding: 0.5rem 1rem;">
                                    @if($this->canApprove)
                                        <input type="text" wire:model.defer="remarks.{{ $st['student_id'] }}.principal_comment" placeholder="Principal remark (auto-assigned by grade, editable)" class="ss-input" style="padding: 0.375rem 0.5rem; font-size: 0.8rem;" />
                                    @else
                                        <span style="font-size: 0.8rem; color: #475569; font-style: italic;">
                                            {{ $st['principal_comment'] ?? 'Auto-assigned by grade' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="padding: 2rem; text-align: center; color: #94a3b8;">
                                    No enrolled students found in this classroom for the selected session.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ══════════════════════════════════════════════════════════════════
                 SUBJECT CHECKLIST TABLE
            ══════════════════════════════════════════════════════════════════ --}}
            <div class="ss-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(148,163,184,0.1); background: rgba(248,250,252,0.5);" class="dark:bg-slate-900/40">
                    <h3 style="font-size: 0.875rem; font-weight: 900;" class="dark:text-white">Class Subjects Checklist</h3>
                </div>

                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid rgba(148,163,184,0.1); font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800;">
                            <th style="padding: 0.875rem 1.5rem;">Subject Name</th>
                            <th style="padding: 0.875rem 1.5rem;">Code</th>
                            <th style="padding: 0.875rem 1.5rem; text-align: center;">Status</th>
                            <th style="padding: 0.875rem 1.5rem; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $subj)
                            <tr style="border-bottom: 1px solid rgba(148,163,184,0.05);">
                                <td style="padding: 1rem 1.5rem; font-weight: 700;" class="dark:text-white">
                                    {{ $subj['name'] }}
                                </td>
                                <td style="padding: 1rem 1.5rem; color: #64748b;">
                                    {{ $subj['code'] ?: '—' }}
                                </td>
                                <td style="padding: 1rem 1.5rem; text-align: center;">
                                    @if($subj['is_published'])
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 800; background: #d1fae5; color: #065f46;">
                                            ✓ Published
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 800; background: #fef3c7; color: #92400e;">
                                            ● Pending Subject Teacher
                                        </span>
                                    @endif
                                </td>
                                <td style="padding: 1rem 1.5rem; text-align: right;">
                                    @php
                                        $panelId = filament()->getCurrentPanel()?->getId() ?? 'teacher';
                                        $enterScoresRoute = "filament.{$panelId}.pages.enter-scores";
                                        $scoresUrl = \Illuminate\Support\Facades\Route::has($enterScoresRoute)
                                            ? route($enterScoresRoute, [
                                                'session_id' => $session_id,
                                                'term_id' => $term_id,
                                                'classroom_id' => $classroom_id,
                                                'subject_id' => $subj['id'],
                                            ])
                                            : null;
                                    @endphp
                                    @if($scoresUrl)
                                        <a href="{{ $scoresUrl }}" style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.35rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 700; background: rgba(99,102,241,0.1); color: #4f46e5; text-decoration: none;" onmouseover="this.style.background='rgba(99,102,241,0.2)'" onmouseout="this.style.background='rgba(99,102,241,0.1)'">
                                            <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <span>View Scores</span>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="padding: 2rem; text-align: center; color: #94a3b8;">
                                    No subjects assigned to this classroom.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="ss-card" style="text-align: center; padding: 3rem 1.5rem;">
                <p style="color: #94a3b8; font-weight: 700;">Please select an Academic Session, Term, and Classroom to review subject status, manage remarks, and approve results.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
