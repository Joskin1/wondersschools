<x-filament-panels::page>
    <style>
        .ms-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .dark .ms-card { background: #131b2e; border-color: rgba(148,163,184,0.1); }
        .ms-label { display: block; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 6px; }
        .ms-select { width: 100%; border-radius: 0.75rem; border: 1px solid #e2e8f0; background: white; color: #1e293b; padding: 0.625rem 0.875rem; font-size: 0.875rem; transition: border-color 0.15s; }
        .ms-select:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .ms-select:disabled { opacity: 0.5; cursor: not-allowed; background: #f8fafc; }
        .dark .ms-select { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .dark .ms-select:disabled { background: rgba(30,41,59,0.5); }

        .ms-table-card { position: relative; border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
        .dark .ms-table-card { background: #0b0f19; border-color: rgba(148,163,184,0.1); }
        .ms-scroll-container { overflow-x: auto; max-height: 65vh; }
        .ms-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
        .ms-table th { padding: 1rem 1.25rem; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
        .dark .ms-table th { border-bottom-color: #1e293b; }
        .ms-table td { padding: 0.875rem 1.25rem; border-bottom: 1px solid rgba(148,163,184,0.1); }
        .dark .ms-table td { border-bottom-color: rgba(148,163,184,0.05); }

        .ms-sticky-col { position: sticky; left: 0; background: white; border-right: 1px solid #e2e8f0; z-index: 10; box-shadow: 2px 0 5px -2px rgba(0,0,0,0.08); }
        .dark .ms-sticky-col { background: #0b0f19; border-right-color: #1e293b; }
        tr:hover .ms-sticky-col { background: #f8fafc; }
        .dark tr:hover .ms-sticky-col { background: #131b2e; }

        .ms-sticky-header { position: sticky; top: 0; background: #f8fafc; z-index: 20; border-bottom: 2px solid #e2e8f0; }
        .dark .ms-sticky-header { background: #131b2e; border-bottom-color: #1e293b; }
        .ms-sticky-header-first { position: sticky; left: 0; top: 0; background: #f8fafc; z-index: 30; border-right: 1px solid #e2e8f0; border-bottom: 2px solid #e2e8f0; }
        .dark .ms-sticky-header-first { background: #131b2e; border-right-color: #1e293b; border-bottom-color: #1e293b; }

        .ms-cell-wrap { display: inline-flex; align-items: center; justify-content: center; gap: 0.25rem; padding: 0.375rem 0.625rem; border-radius: 0.75rem; border: 1px solid transparent; transition: all 0.15s; background: rgba(30,41,59,0.04); }
        .dark .ms-cell-wrap { background: rgba(30,41,59,0.4); }
        .ms-cell-wrap:focus-within { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.15); }
        .ms-cell-input { width: 3rem; text-align: center; background: transparent; border: none; outline: none; padding: 0; font-size: 0.875rem; font-weight: 900; color: #1e293b; }
        .dark .ms-cell-input { color: #f1f5f9; }
        .ms-bracket { color: #64748b; font-weight: 900; font-size: 0.875rem; user-select: none; }
        .ms-btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.45rem 0.75rem; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 800; border: none; cursor: pointer; transition: all 0.15s; }
        .avatar-wrap { display: flex; height: 2.25rem; width: 2.25rem; shrink-0: 0; align-items: center; justify-content: center; border-radius: 9999px; font-weight: 900; font-size: 0.75rem; text-transform: uppercase; }

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

        {{-- ── 1. Full-Screen Backdrop Overlay for File Upload & Processing ──────── --}}
        <div x-show="isUploading" x-cloak style="position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 1.25rem; padding: 2rem 2.5rem; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);" class="dark:bg-slate-900">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #e0e7ff; color: #4338ca; margin-bottom: 1.25rem;" class="dark:bg-indigo-950 dark:text-indigo-400">
                    <svg class="animate-spin" style="width: 36px; height: 36px;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.125rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem;" class="dark:text-white">Uploading Excel Spreadsheet</h3>
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
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #e0e7ff; color: #4338ca; margin-bottom: 1.25rem;" class="dark:bg-indigo-950 dark:text-indigo-400">
                    <svg class="animate-spin" style="width: 36px; height: 36px;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.125rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem;" class="dark:text-white">Processing &amp; Validating Excel</h3>
                <p style="font-size: 0.875rem; color: #64748b; line-height: 1.5; margin: 0;" class="dark:text-slate-400">
                    Reading spreadsheet columns, matching students, and validating score values. Please wait a moment...
                </p>
            </div>
        </div>

        <div wire:loading.flex wire:target="confirmImport" style="display:none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 1.25rem; padding: 2rem 2.5rem; max-width: 420px; width: 90%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);" class="dark:bg-slate-900">
                <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 50%; background: #e0e7ff; color: #4338ca; margin-bottom: 1.25rem;" class="dark:bg-indigo-950 dark:text-indigo-400">
                    <svg class="animate-spin" style="width: 36px; height: 36px;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </div>
                <h3 style="font-size: 1.125rem; font-weight: 900; color: #0f172a; margin-bottom: 0.5rem;" class="dark:text-white">Saving &amp; Applying Scores</h3>
                <p style="font-size: 0.875rem; color: #64748b; line-height: 1.5; margin: 0;" class="dark:text-slate-400">
                    Writing scores into database and recalculating mid-term metrics. Please wait...
                </p>
            </div>
        </div>

        {{-- Parameters Card --}}
        <div class="ms-card">
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(148,163,184,0.1); flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="display: flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: 0.75rem; background: rgba(238,242,255,1); color: #4f46e5;">
                            <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </div>
                        <div>
                            <h2 style="font-size: 1rem; font-weight: 900; color: #0f172a;" class="dark:text-white">Active Parameters</h2>
                            <p style="font-size: 0.75rem; color: #94a3b8;">Select parameters to enter mid-term continuous assessment scores.</p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    @if ($classroom_id && $session_id && $term_id)
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            @if ($subject_id)
                                <button type="button" wire:click="exportCurrentSubject" wire:loading.attr="disabled" class="ms-btn" style="background: #059669; color: white;">
                                    <span wire:loading.remove wire:target="exportCurrentSubject">Export Subject (.xlsx)</span>
                                    <span wire:loading wire:target="exportCurrentSubject" style="display: inline-flex; align-items: center; gap: 4px;">
                                        <svg class="animate-spin" style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24"><circle opacity="0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path opacity="0.75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg> Exporting...
                                    </span>
                                </button>
                            @endif
                            <button type="button" wire:click="exportClassSubjects" wire:loading.attr="disabled" class="ms-btn" style="background: #0f766e; color: white;">
                                <span wire:loading.remove wire:target="exportClassSubjects">Export Class (.xlsx)</span>
                                <span wire:loading wire:target="exportClassSubjects" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg class="animate-spin" style="width: 13px; height: 13px;" fill="none" viewBox="0 0 24 24"><circle opacity="0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path opacity="0.75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg> Exporting...
                                </span>
                            </button>
                            <label for="excel-midterm-upload" class="ms-btn" style="background: #4338ca; color: white; cursor: pointer;">
                                <svg style="width: 14px; height: 14px; display: inline-block; vertical-align: middle; margin-right: 4px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span>Import Excel</span>
                                <input type="file" id="excel-midterm-upload" wire:model="uploadFile" accept=".xlsx,.xls,.csv" style="display: none;">
                            </label>

                            <div wire:loading wire:target="session_id,term_id,classroom_id,subject_id" style="display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.75rem; color: #4338ca; font-weight: 800;">
                                <svg class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24"><circle opacity="0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path opacity="0.75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                <span>Loading data...</span>
                            </div>

                            @if($isLocked)
                                <span style="display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 800; background: #fee2e2; color: #991b1b;">
                                    🔒 Mid-Term Locked
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Filter Selectors --}}
                <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 1.25rem;" class="sm:grid-cols-4">
                    <div>
                        <label class="ms-label">Session</label>
                        <select wire:model.live="session_id" class="ms-select">
                            <option value="">— Select Session —</option>
                            @foreach($this->sessions as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="ms-label">Term</label>
                        <select wire:model.live="term_id" @disabled(! $session_id) class="ms-select">
                            <option value="">— Select Term —</option>
                            @foreach($this->terms as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="ms-label">Classroom</label>
                        <select wire:model.live="classroom_id" @disabled(! $term_id) class="ms-select">
                            <option value="">— Select Classroom —</option>
                            @foreach($this->classrooms as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="ms-label">Subject</label>
                        <select wire:model.live="subject_id" @disabled(! $classroom_id) class="ms-select">
                            <option value="">— Select Subject —</option>
                            @foreach($this->subjects as $subj)
                                <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Configuration Error Alert --}}
        @if($configError)
            <div style="padding: 1.25rem; border-radius: 1rem; border: 1px solid rgba(239,68,68,0.25); background: rgba(239,68,68,0.06); color: #dc2626;">
                <p style="font-weight: 800; font-size: 0.875rem;">⚠️ {{ $configError }}</p>
            </div>
        @endif

        {{-- Score Matrix --}}
        @if ($loaded && $structureExists && !empty($students))
            <div
                x-data="{
                    savingStates: {},
                    save(studentId, scoreHeadId, value, isAbsent) {
                        let rowKey = `${studentId}_${scoreHeadId}`;
                        this.savingStates[rowKey] = 'saving';
                        document.getElementById(`sync-text-${studentId}`).innerText = 'Saving...';
                        document.getElementById(`sync-text-${studentId}`).className = 'text-amber-500 font-bold text-xs';

                        $wire.saveScore(studentId, scoreHeadId, value, isAbsent).then(result => {
                            if (result.status === 'success') {
                                this.savingStates[rowKey] = 'saved';
                                document.getElementById(`sync-text-${studentId}`).innerText = 'Saved ✓';
                                document.getElementById(`sync-text-${studentId}`).className = 'text-emerald-500 font-bold text-xs';

                                document.getElementById(`raw-total-${studentId}`).innerText = result.raw_total;
                                document.getElementById(`scaled-score-${studentId}`).innerText = result.scaled_score;
                                document.getElementById(`grade-${studentId}`).innerText = result.student_grade;
                            } else {
                                this.savingStates[rowKey] = 'error';
                                document.getElementById(`sync-text-${studentId}`).innerText = result.message;
                                document.getElementById(`sync-text-${studentId}`).className = 'text-red-500 font-black text-xs';
                            }
                        }).catch(() => {
                            this.savingStates[rowKey] = 'error';
                            document.getElementById(`sync-text-${studentId}`).innerText = 'Error';
                            document.getElementById(`sync-text-${studentId}`).className = 'text-red-500 font-black text-xs';
                        });
                    }
                }"
                class="ms-table-card"
            >
                <div class="ms-scroll-container">
                    <table class="ms-table">
                        <thead>
                            <tr class="ms-sticky-header">
                                <th class="ms-sticky-header-first" style="min-width: 240px;">
                                    <span>Student Details</span>
                                </th>
                                @foreach($scoreHeads as $sh)
                                    <th style="text-align: center; min-width: 140px; border-right: 1px solid rgba(148,163,184,0.1);">
                                        {{ $sh['name'] }} (Max {{ $sh['effective_max'] }})
                                    </th>
                                @endforeach
                                <th style="text-align: center; min-width: 100px; border-right: 1px solid rgba(148,163,184,0.1); background: rgba(248,250,252,0.5);" class="dark:bg-slate-900/30">
                                    Raw Total (/{{ $rawMax }})
                                </th>
                                <th style="text-align: center; min-width: 110px; border-right: 1px solid rgba(148,163,184,0.1); background: rgba(248,250,252,0.5);" class="dark:bg-slate-900/30">
                                    Scaled (/{{ $midtermWeight }})
                                </th>
                                <th style="text-align: center; min-width: 80px; border-right: 1px solid rgba(148,163,184,0.1); background: rgba(248,250,252,0.5);" class="dark:bg-slate-900/30">
                                    Grade
                                </th>
                                <th style="text-align: center; min-width: 120px; background: rgba(248,250,252,0.5);" class="dark:bg-slate-900/30">
                                    Status
                                </th>
                            </tr>
                        </thead>

                        <tbody style="background: white;" class="dark:bg-[#0b0f19]">
                            @foreach($students as $student)
                                @php
                                    $res = $studentResults[$student['id']] ?? null;
                                    $scaledDisplay = match ($res['status'] ?? 'missing') {
                                        'absent'  => 'ABS',
                                        'missing' => '—',
                                        default   => number_format((float) ($res['scaled_score'] ?? 0), 2),
                                    };
                                @endphp
                                <tr>
                                    <td class="ms-sticky-col">
                                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                                            <div>
                                                <div style="font-weight: 800; color: #0f172a;" class="dark:text-white">{{ $student['full_name'] }}</div>
                                                <div style="font-size: 0.75rem; color: #94a3b8;">{{ $student['admission_number'] }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    @foreach($scoreHeads as $sh)
                                        @php
                                            $val = $scores[$student['id']][$sh['id']] ?? '';
                                            $isAbs = $absents[$student['id']][$sh['id']] ?? false;
                                        @endphp
                                        <td style="text-align: center; border-right: 1px solid rgba(148,163,184,0.1);">
                                            <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                <div class="ms-cell-wrap">
                                                    <span class="ms-bracket">[</span>
                                                    <input
                                                        type="number"
                                                        step="any"
                                                        min="0"
                                                        max="{{ $sh['effective_max'] }}"
                                                        value="{{ $val }}"
                                                        @disabled($isLocked || $isAbs)
                                                        placeholder="—"
                                                        class="ms-cell-input"
                                                        id="input-{{ $student['id'] }}-{{ $sh['id'] }}"
                                                        @change="save({{ $student['id'] }}, {{ $sh['id'] }}, $event.target.value, false)"
                                                    >
                                                    <span class="ms-bracket">]</span>
                                                </div>

                                                <label style="display: inline-flex; align-items: center; gap: 0.2rem; font-size: 10px; font-weight: 800; color: #64748b; cursor: pointer;">
                                                    <input
                                                        type="checkbox"
                                                        style="accent-color: #ef4444;"
                                                        @checked($isAbs)
                                                        @disabled($isLocked)
                                                        @change="
                                                            let inp = document.getElementById('input-{{ $student['id'] }}-{{ $sh['id'] }}');
                                                            if ($event.target.checked) {
                                                                inp.value = '';
                                                                inp.disabled = true;
                                                                save({{ $student['id'] }}, {{ $sh['id'] }}, '', true);
                                                            } else {
                                                                inp.disabled = false;
                                                                save({{ $student['id'] }}, {{ $sh['id'] }}, inp.value, false);
                                                            }
                                                        "
                                                    >
                                                    ABS
                                                </label>
                                            </div>
                                        </td>
                                    @endforeach

                                    <td style="text-align: center; font-weight: 800; border-right: 1px solid rgba(148,163,184,0.1);" id="raw-total-{{ $student['id'] }}">
                                        {{ $res['raw_score'] ?? 0 }}
                                    </td>

                                    <td style="text-align: center; font-weight: 900; color: #4f46e5; border-right: 1px solid rgba(148,163,184,0.1);" id="scaled-score-{{ $student['id'] }}">
                                        {{ $scaledDisplay }}
                                    </td>

                                    <td style="text-align: center; font-weight: 900; border-right: 1px solid rgba(148,163,184,0.1);" id="grade-{{ $student['id'] }}">
                                        {{ $res['grade'] ?? '—' }}
                                    </td>

                                    <td style="text-align: center;">
                                        <span id="sync-text-{{ $student['id'] }}" class="text-slate-400 font-bold text-xs">
                                            {{ ($res['status'] ?? 'missing') === 'missing' ? 'Draft' : 'Computed' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($loaded && ! $structureExists)
            <div style="padding: 3rem 1rem; text-align: center; border-radius: 1rem; border: 2px dashed rgba(148,163,184,0.2);">
                <p style="font-weight: 800; color: #64748b;">No structure available for scoring.</p>
            </div>
        @endif

        {{-- ── Step 3: Interactive Staging & Correction Modal ── --}}
        @if ($showImportModal && !empty($stagingData))
            <div style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); padding: 1rem;">
                <div style="width: 100%; max-width: 95vw; height: 90vh; background: white; border-radius: 1.25rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; border: 1px solid rgba(148,163,184,0.2);" class="dark:bg-slate-900 dark:border-slate-800">
                    
                    {{-- Modal Header --}}
                    <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(148,163,184,0.2); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; background: #f8fafc;" class="dark:bg-slate-800/60">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="display: flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: 0.75rem; background: rgba(99,102,241,0.1); color: #4f46e5;">
                                <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <h3 style="font-size: 1.125rem; font-weight: 900; color: #0f172a;" class="dark:text-white">Review & Import Mid-Term Scores Preview</h3>
                                <p style="font-size: 0.75rem; color: #64748b;">Review parsed marks. You can correct any errors directly in the table before saving.</p>
                            </div>
                        </div>

                        {{-- Summary Badges --}}
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.625rem;">
                            <span style="font-size: 0.75rem; font-weight: 800; padding: 0.35rem 0.75rem; border-radius: 9999px; background: #f1f5f9; color: #334155;" class="dark:bg-slate-800 dark:text-slate-300">
                                {{ $stagingData['total_students'] ?? 0 }} Students
                            </span>

                            <span style="font-size: 0.75rem; font-weight: 800; padding: 0.35rem 0.75rem; border-radius: 9999px; background: #f1f5f9; color: #334155;" class="dark:bg-slate-800 dark:text-slate-300">
                                {{ count($stagingData['subjects'] ?? []) }} Subject(s)
                            </span>

                            <span style="font-size: 0.75rem; font-weight: 800; padding: 0.35rem 0.75rem; border-radius: 9999px; background: #e0e7ff; color: #3730a3;" class="dark:bg-indigo-950 dark:text-indigo-300">
                                {{ $stagingData['total_scores_count'] ?? 0 }} Scores Found
                            </span>

                            @if (($stagingData['total_errors'] ?? 0) > 0)
                                <span style="font-size: 0.75rem; font-weight: 800; padding: 0.35rem 0.75rem; border-radius: 9999px; background: #fee2e2; color: #991b1b; display: inline-flex; align-items: center; gap: 0.375rem;">
                                    <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    {{ $stagingData['total_errors'] }} error(s) to resolve
                                </span>
                            @else
                                <span style="font-size: 0.75rem; font-weight: 800; padding: 0.35rem 0.75rem; border-radius: 9999px; background: #d1fae5; color: #065f46; display: inline-flex; align-items: center; gap: 0.375rem;">
                                    <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    All Scores Valid
                                </span>
                            @endif

                            <button type="button" wire:click="closeImportModal" style="display: flex; align-items: center; justify-content: center; height: 2rem; width: 2rem; border-radius: 0.5rem; color: #64748b; background: transparent; border: none; cursor: pointer;" onmouseover="this.style.background='rgba(148,163,184,0.1)'" onmouseout="this.style.background='transparent'">
                                <svg style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body: Scrollable Spreadsheet Table --}}
                    <div style="flex: 1 1 0%; overflow: auto; padding: 1rem 1.5rem;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                            <thead>
                                {{-- Row 1: Group headers --}}
                                <tr>
                                    <th colspan="3" style="position: sticky; top: 0; left: 0; z-index: 30; background: #f1f5f9; padding: 0.625rem 0.75rem; font-weight: 900; font-size: 0.75rem; text-transform: uppercase; color: #334155; border: 1px solid #cbd5e1;" class="dark:bg-slate-800 dark:text-slate-200 dark:border-slate-700">
                                        STUDENT INFORMATION
                                    </th>
                                    @foreach ($stagingData['subjects'] as $subj)
                                        <th colspan="{{ count($subj['score_heads']) }}" style="position: sticky; top: 0; z-index: 20; background: #e0e7ff; padding: 0.625rem 0.75rem; font-weight: 900; font-size: 0.75rem; text-transform: uppercase; color: #1e1b4b; border: 1px solid #cbd5e1; text-align: center;" class="dark:bg-indigo-950 dark:text-indigo-200 dark:border-slate-700">
                                            {{ $subj['name'] }}
                                        </th>
                                    @endforeach
                                </tr>

                                {{-- Row 2: Sub-headers --}}
                                <tr>
                                    <th style="position: sticky; top: 32px; left: 0; z-index: 30; background: #e2e8f0; width: 3.5rem; padding: 0.5rem 0.625rem; font-weight: 800; font-size: 0.7rem; color: #475569; border: 1px solid #cbd5e1; text-align: center;" class="dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">S/N</th>
                                    <th style="position: sticky; top: 32px; left: 3.5rem; z-index: 30; background: #e2e8f0; width: 8rem; padding: 0.5rem 0.625rem; font-weight: 800; font-size: 0.7rem; color: #475569; border: 1px solid #cbd5e1;" class="dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">Adm No</th>
                                    <th style="position: sticky; top: 32px; left: 11.5rem; z-index: 30; background: #e2e8f0; width: 14rem; padding: 0.5rem 0.625rem; font-weight: 800; font-size: 0.7rem; color: #475569; border: 1px solid #cbd5e1;" class="dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">Full Name</th>

                                    @foreach ($stagingData['subjects'] as $subj)
                                        @foreach ($subj['score_heads'] as $sh)
                                            <th style="position: sticky; top: 32px; z-index: 20; background: #eef2ff; min-width: 7.5rem; padding: 0.5rem 0.625rem; font-weight: 800; font-size: 0.7rem; color: #312e81; border: 1px solid #cbd5e1; text-align: center;" class="dark:bg-indigo-900/40 dark:text-indigo-300 dark:border-slate-700">
                                                <div>{{ $sh['name'] }}</div>
                                                <div style="font-size: 0.65rem; color: #6366f1; font-weight: 700;">(Max: {{ $sh['effective_max'] }})</div>
                                            </th>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($stagingData['students'] as $sIndex => $student)
                                    <tr style="{{ !empty($student['has_error']) ? 'background: rgba(254, 242, 242, 0.4);' : '' }}">
                                        {{-- S/N --}}
                                        <td style="position: sticky; left: 0; background: white; border: 1px solid #e2e8f0; padding: 0.5rem 0.625rem; text-align: center; font-weight: 700; color: #64748b;" class="dark:bg-slate-900 dark:border-slate-800">
                                            {{ $loop->iteration }}
                                        </td>

                                        {{-- Admission No --}}
                                        <td style="position: sticky; left: 3.5rem; background: white; border: 1px solid #e2e8f0; padding: 0.5rem 0.625rem; font-weight: 700; font-family: monospace; color: #334155;" class="dark:bg-slate-900 dark:border-slate-800 dark:text-slate-300">
                                            {{ $student['admission_number'] }}
                                        </td>

                                        {{-- Full Name --}}
                                        <td style="position: sticky; left: 11.5rem; background: white; border: 1px solid #e2e8f0; padding: 0.5rem 0.625rem; font-weight: 800; color: #0f172a;" class="dark:bg-slate-900 dark:border-slate-800 dark:text-white">
                                            {{ $student['full_name'] }}
                                            @if (!empty($student['unmatched']))
                                                <span style="display: block; font-size: 0.65rem; color: #dc2626; font-weight: 700;">⚠️ Student not found in class</span>
                                            @endif
                                        </td>

                                        {{-- Score Cells --}}
                                        @foreach ($stagingData['subjects'] as $subj)
                                            @foreach ($subj['score_heads'] as $sh)
                                                @php
                                                    $scoreKey = $sh['key'];
                                                    $cellData = $student['scores'][$scoreKey] ?? ['value' => '', 'error' => null, 'effective_max' => $sh['effective_max']];
                                                    $hasCellError = !empty($cellData['error']);
                                                @endphp
                                                <td style="border: 1px solid #e2e8f0; padding: 0.375rem; text-align: center; {{ $hasCellError ? 'background: #fef2f2;' : '' }}" class="dark:border-slate-800">
                                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.125rem;">
                                                        <input
                                                            type="text"
                                                            value="{{ $cellData['value'] }}"
                                                            wire:change="updateStagingScore({{ $sIndex }}, '{{ $scoreKey }}', $event.target.value)"
                                                            style="width: 3.5rem; text-align: center; font-size: 0.8125rem; font-weight: 800; padding: 0.25rem 0.375rem; border-radius: 0.375rem; border: 1px solid {{ $hasCellError ? '#ef4444' : '#cbd5e1' }}; background: {{ $hasCellError ? '#fff' : 'transparent' }}; color: {{ $hasCellError ? '#b91c1c' : '#0f172a' }}; transition: all 0.15s;"
                                                            class="dark:text-white dark:border-slate-700"
                                                            placeholder="—"
                                                        />

                                                        @if ($hasCellError)
                                                            <span style="font-size: 0.625rem; color: #dc2626; font-weight: 800; line-height: 1.1; max-width: 6.5rem; text-align: center;">
                                                                {{ $cellData['error'] }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Modal Footer --}}
                    <div style="padding: 1rem 1.5rem; border-top: 1px solid rgba(148,163,184,0.2); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; background: #f8fafc;" class="dark:bg-slate-800/60">
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #64748b;">
                            <svg style="width: 16px; height: 16px; color: #6366f1;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Click on any cell to edit. Corrections take effect instantly in the preview. Type "ABS" for absent students.</span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <button type="button" wire:click="closeImportModal" style="padding: 0.5rem 1rem; border-radius: 0.75rem; border: 1px solid #cbd5e1; background: white; color: #475569; font-size: 0.8125rem; font-weight: 800; cursor: pointer;" class="dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700">
                                Cancel
                            </button>

                            @if (($stagingData['total_errors'] ?? 0) > 0)
                                <button type="button" disabled style="padding: 0.5rem 1.25rem; border-radius: 0.75rem; border: none; background: #94a3b8; color: white; font-size: 0.8125rem; font-weight: 800; cursor: not-allowed; opacity: 0.65;">
                                    Fix {{ $stagingData['total_errors'] }} Error(s) to Import
                                </button>
                            @else
                                <button type="button" wire:click="confirmImport" wire:loading.attr="disabled" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1.25rem; border-radius: 0.75rem; border: none; background: #4f46e5; color: white; font-size: 0.8125rem; font-weight: 800; cursor: pointer; transition: background 0.15s;" onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                                    <svg wire:loading.remove wire:target="confirmImport" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <svg wire:loading wire:target="confirmImport" class="animate-spin" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24"><circle opacity="0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path opacity="0.75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                    <span wire:loading.remove wire:target="confirmImport">Confirm &amp; Save All Scores</span>
                                    <span wire:loading wire:target="confirmImport">Saving Scores...</span>
                                </button>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
