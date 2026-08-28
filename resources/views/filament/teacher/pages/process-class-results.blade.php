<x-filament-panels::page>
    <style>
        .ss-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .dark .ss-card { background: #131b2e; border-color: rgba(148,163,184,0.1); }
        .ss-label { display: block; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 6px; }
        .ss-select { width: 100%; border-radius: 0.75rem; border: 1px solid #e2e8f0; background: white; color: #1e293b; padding: 0.625rem 0.875rem; font-size: 0.875rem; transition: border-color 0.15s; }
        .ss-select:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .dark .ss-select { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .ss-grid { display: grid; grid-template-columns: repeat(1, 1fr); gap: 1rem; }
        @media (min-width: 768px) { .ss-grid { grid-template-columns: repeat(3, 1fr); } }
    </style>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
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
                            <p style="font-size: 0.75rem; color: #94a3b8;">Review subject teacher publishing progress and publish overall class results.</p>
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
            </div>
        </div>

        @if($session_id && $term_id && $classroom_id)
            @php
                $subjects = $this->subjectStatusList;
                $publishedCount = collect($subjects)->where('is_published', true)->count();
                $totalCount = count($subjects);
                $isFinalized = $this->isClassFinalized;
            @endphp

            {{-- Summary Stats Grid --}}
            <div class="ss-grid">
                <div class="ss-card">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Subject Publishing Progress</p>
                    <p style="font-size: 1.75rem; font-weight: 900; margin-top: 0.5rem; color: #4f46e5;">
                        {{ $publishedCount }} / {{ $totalCount }}
                    </p>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">Subjects Published by Subject Teachers</p>
                </div>

                <div class="ss-card">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase;">Overall Class Status</p>
                    <div style="margin-top: 0.5rem;">
                        @if($isFinalized)
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 800; background: #d1fae5; color: #065f46;">
                                ✓ Published to Students
                            </span>
                        @else
                            <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 800; background: #fef3c7; color: #92400e;">
                                ● Pending Finalization
                            </span>
                        @endif
                    </div>
                </div>

                <div class="ss-card" style="display: flex; flex-direction: column; justify-content: center; align-items: flex-start;">
                    <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 0.75rem;">Action</p>
                    <button type="button" wire:click="processClassResults" wire:loading.attr="disabled" style="width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.25rem; border-radius: 0.75rem; background: #4f46e5; color: white; font-size: 0.875rem; font-weight: 800; cursor: pointer; border: none; transition: background 0.15s;" onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                        <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Process &amp; Publish Class Results
                    </button>
                </div>
            </div>

            {{-- Subject Checklist Table --}}
            <div class="ss-card" style="padding: 0; overflow: hidden;">
                <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(148,163,184,0.1); background: rgba(248,250,252,0.5);" class="dark:bg-slate-900/40">
                    <h3 style="font-size: 0.875rem; font-weight: 900;" class="dark:text-white">Class Subjects Checklist</h3>
                </div>

                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid rgba(148,163,184,0.1); font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 800;">
                            <th style="padding: 0.875rem 1.5rem;">Subject Name</th>
                            <th style="padding: 0.875rem 1.5rem;">Code</th>
                            <th style="padding: 0.875rem 1.5rem; text-align: right;">Status</th>
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
                                <td style="padding: 1rem 1.5rem; text-align: right;">
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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="padding: 2rem; text-align: center; color: #94a3b8;">
                                    No subjects assigned to this classroom.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="ss-card" style="text-align: center; padding: 3rem 1.5rem;">
                <p style="color: #94a3b8; font-weight: 700;">Please select an Academic Session, Term, and Classroom to review subject status and process class results.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
