<x-filament-panels::page>
    <style>
        /* ── Design System ───────────────────────────────────────────────── */
        .sr-page {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 1.25rem;
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }
        @media (max-width: 640px) {
            .sr-page {
                padding: 0 0.75rem;
                width: 100%;
                min-width: 0;
                box-sizing: border-box;
            }
        }

        /* Filter Card */
        .sr-filters {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
            border: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }
        .dark .sr-filters { background: #1f2937; border-color: #374151; }

        .sr-filter-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            margin-bottom: 0.375rem;
        }
        .dark .sr-filter-group label { color: #9ca3af; }

        .sr-filter-group select {
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 0.875rem;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            color: #111827;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .sr-filter-group select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        }
        .dark .sr-filter-group select {
            background-color: #374151;
            border-color: #4b5563;
            color: #f3f4f6;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1rem;
        }

        /* Hero Stats Bar */
        .sr-hero {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .sr-stat-card {
            padding: 1rem 1.25rem;
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            text-align: center;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .sr-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
        }
        .dark .sr-stat-card { background: #1f2937; border-color: #374151; }

        .sr-stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1.2;
        }
        .sr-stat-card .stat-label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        .dark .sr-stat-card .stat-label { color: #9ca3af; }

        .sr-stat-card.grade-card .stat-value { color: #059669; }
        .sr-stat-card.avg-card .stat-value { color: #6366f1; }
        .sr-stat-card.pos-card .stat-value { color: #f59e0b; }
        .sr-stat-card.total-card .stat-value { color: #0ea5e9; }

        /* Info Header */
        .sr-info-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        .sr-info-bar .info-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .sr-info-bar .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,.15);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
        }
        .sr-info-bar .student-name {
            font-weight: 700;
            font-size: 1rem;
        }
        .sr-info-bar .student-meta {
            font-size: 0.8rem;
            opacity: 0.75;
        }
        .sr-download-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 8px;
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
            text-decoration: none;
        }
        .sr-download-btn:hover { background: rgba(255,255,255,.25); }
        .sr-download-btn svg {
            width: 16px; height: 16px;
        }

        /* Result Table */
        .sr-table-wrap {
            background: white;
            border: 1px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 12px 12px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            margin-bottom: 1.5rem;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        .dark .sr-table-wrap { background: #1f2937; border-color: #374151; }

        .sr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        .sr-table thead th {
            padding: 0.75rem 1rem;
            text-align: center;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            background: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
        }
        .dark .sr-table thead th { background: #111827; color: #9ca3af; border-color: #374151; }
        .sr-table thead th:first-child { text-align: left; }

        .sr-table tbody td {
            padding: 0.75rem 1rem;
            text-align: center;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }
        .dark .sr-table tbody td { color: #e5e7eb; border-color: #1f2937; }
        .sr-table tbody td:first-child {
            text-align: left;
            font-weight: 600;
            color: #111827;
        }
        .dark .sr-table tbody td:first-child { color: #f9fafb; }

        .sr-table tbody tr { transition: background 0.1s; }
        .sr-table tbody tr:hover { background: #f0f4ff; }
        .dark .sr-table tbody tr:hover { background: #1e293b; }

        .sr-table .total-cell {
            font-weight: 800;
            color: #111827;
            font-size: 0.95rem;
        }
        .dark .sr-table .total-cell { color: #f9fafb; }

        /* Grade Badge */
        .grade-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            height: 26px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.02em;
        }
        .grade-A { background: #d1fae5; color: #065f46; }
        .grade-B { background: #dbeafe; color: #1e40af; }
        .grade-C { background: #fef3c7; color: #92400e; }
        .grade-D, .grade-E { background: #ffedd5; color: #9a3412; }
        .grade-F { background: #fee2e2; color: #991b1b; }
        .dark .grade-A { background: rgba(16,185,129,.15); color: #34d399; }
        .dark .grade-B { background: rgba(96,165,250,.15); color: #93c5fd; }
        .dark .grade-C { background: rgba(245,158,11,.15); color: #fbbf24; }
        .dark .grade-D, .dark .grade-E { background: rgba(251,146,60,.15); color: #fb923c; }
        .dark .grade-F { background: rgba(239,68,68,.15); color: #fca5a5; }

        .remark-cell {
            font-size: 0.78rem;
            color: #6b7280;
            font-style: italic;
        }
        .dark .remark-cell { color: #9ca3af; }

        /* Summary Footer */
        .sr-summary-footer {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            background: #f8fafc;
            border-top: 2px solid #e5e7eb;
        }
        .dark .sr-summary-footer { background: #111827; border-color: #374151; }
        .sr-summary-item {
            text-align: center;
        }
        .sr-summary-item .summary-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #9ca3af;
        }
        .sr-summary-item .summary-value {
            font-size: 1.1rem;
            font-weight: 800;
            color: #111827;
            margin-top: 0.125rem;
        }
        .dark .sr-summary-item .summary-value { color: #f3f4f6; }

        /* Empty States */
        .sr-empty {
            padding: 3rem 2rem;
            text-align: center;
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .dark .sr-empty { background: #1f2937; border-color: #374151; }
        .sr-empty-icon {
            width: 56px; height: 56px;
            margin: 0 auto 1rem;
            color: #d1d5db;
        }
        .dark .sr-empty-icon { color: #4b5563; }
        .sr-empty h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        .dark .sr-empty h4 { color: #e5e7eb; }
        .sr-empty p {
            font-size: 0.85rem;
            color: #9ca3af;
        }

        /* Webkit custom scrollbar for clean premium scroll experience on mobile */
        .sr-table-wrap::-webkit-scrollbar {
            height: 6px;
        }
        .sr-table-wrap::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .dark .sr-table-wrap::-webkit-scrollbar-track {
            background: #111827;
        }
        .sr-table-wrap::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .dark .sr-table-wrap::-webkit-scrollbar-thumb {
            background: #4b5563;
        }
        .sr-table-wrap::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .sr-table th, .sr-table td {
            white-space: nowrap;
        }
        .sr-table th:first-child, .sr-table td:first-child {
            white-space: normal;
            min-width: 140px;
        }

        @media (max-width: 768px) {
            .sr-filters { grid-template-columns: 1fr; gap: 0.75rem; }
            .sr-hero { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
            .sr-summary-footer { grid-template-columns: repeat(3, 1fr); }
        }

        @media (max-width: 640px) {
            .sr-info-bar {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                gap: 1rem;
                padding: 1.25rem 1rem;
            }
            .sr-info-bar .info-left {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 0.5rem;
            }
            .sr-download-btn {
                justify-content: center;
                width: 100%;
            }
            .sr-summary-footer {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
                padding: 1rem;
            }
        }

        @media (max-width: 576px) {
            .sr-hero {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            .sr-stat-card {
                padding: 1rem;
            }
            .sr-stat-card .stat-value {
                font-size: 1.5rem;
            }
            .sr-stat-card .stat-label {
                font-size: 0.65rem;
            }
            .sr-summary-footer {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            .sr-summary-item {
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #e5e7eb;
                padding-bottom: 0.5rem;
            }
            .dark .sr-summary-item {
                border-color: #374151;
            }
            .sr-summary-item:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
            .sr-summary-item .summary-value {
                margin-top: 0;
                font-size: 0.95rem;
            }
        }

        /* ── Impersonation Alert ────────────────────────────────────────── */
        .sr-impersonate-alert {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            color: #92400e;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.04);
        }
        .dark .sr-impersonate-alert {
            background: rgba(245, 158, 11, 0.1);
            border-color: rgba(245, 158, 11, 0.25);
            color: #fde68a;
        }
        .sr-impersonate-icon {
            width: 2rem;
            height: 2rem;
            flex-shrink: 0;
            color: #d97706;
        }
        .sr-impersonate-alert h4 {
            font-size: 0.95rem;
            font-weight: 700;
            margin: 0 0 0.25rem;
            color: #b45309;
        }
        .dark .sr-impersonate-alert h4 { color: #fbbf24; }
        .sr-impersonate-alert p {
            font-size: 0.85rem;
            margin: 0;
            opacity: 0.9;
        }

        /* ── PIN Gatekeeper ─────────────────────────────────────────────── */
        .sr-pin-gate {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,.05), 0 2px 4px -2px rgba(0,0,0,.05);
            margin-bottom: 1.5rem;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }
        .dark .sr-pin-gate {
            background: #1f2937;
            border-color: #374151;
        }
        .sr-pin-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
        }
        .dark .sr-pin-icon-wrap {
            background: rgba(37, 99, 235, 0.15);
            color: #60a5fa;
        }
        .sr-pin-icon {
            width: 32px;
            height: 32px;
        }
        .sr-pin-gate h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 0.5rem;
        }
        .dark .sr-pin-gate h3 { color: #f8fafc; }
        .sr-pin-gate .sr-pin-sub {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }
        .dark .sr-pin-gate .sr-pin-sub { color: #94a3b8; }
        .sr-pin-form {
            margin-bottom: 1.25rem;
        }
        .sr-pin-input-group {
            display: flex;
            gap: 0.5rem;
            max-width: 380px;
            margin: 0 auto;
        }
        @media (max-width: 480px) {
            .sr-pin-input-group {
                flex-direction: column;
            }
        }
        .sr-pin-input {
            flex: 1;
            padding: 0.75rem 1rem;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.25em;
            text-align: center;
            border-radius: 10px;
            border: 2px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
            outline: none;
            transition: all 0.15s ease;
        }
        .sr-pin-input:focus {
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }
        .dark .sr-pin-input {
            background: #111827;
            border-color: #4b5563;
            color: #f8fafc;
        }
        .dark .sr-pin-input:focus {
            border-color: #60a5fa;
            background: #1f2937;
        }
        .sr-pin-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            font-weight: 700;
            font-size: 0.875rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .sr-pin-btn:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }
        .sr-pin-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .sr-pin-error {
            margin-top: 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #dc2626;
        }
        .dark .sr-pin-error { color: #f87171; }
        .sr-pin-help {
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.4;
            max-width: 360px;
            margin: 0 auto;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 0.85s linear infinite;
        }
    </style>

    <div class="sr-page">
        {{-- ═══════════════════════════════════════════════════════════════
             IMPERSONATION RESTRICTION ALERT
        ═══════════════════════════════════════════════════════════════ --}}
        @if ($isImpersonating)
            <div class="sr-impersonate-alert">
                <svg class="sr-impersonate-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div>
                    <h4>Result Viewing Restricted During Impersonation</h4>
                    <p>Terminal results cannot be viewed or bypassed while impersonating a student account. Physical scratch card PINs are required for genuine student access.</p>
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════════
             FILTER BAR
        ═══════════════════════════════════════════════════════════════ --}}
        <div class="sr-filters" style="position: relative;">
            <div class="sr-filter-group">
                <label for="session_id">Academic Session</label>
                <select wire:model.live="session_id" id="session_id" @if($isImpersonating) disabled @endif>
                    <option value="">— Select Session —</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session['id'] }}">{{ $session['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sr-filter-group">
                <label for="term_id">Term</label>
                <select wire:model.live="term_id" id="term_id" @if(empty($terms) || $isImpersonating) disabled @endif>
                    <option value="">— Select Term —</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term['id'] }}">{{ $term['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div wire:loading wire:target="session_id,term_id" style="grid-column: span 2; font-size: 0.8rem; color: #4f46e5; font-weight: 700; display: flex; align-items: center; gap: 0.375rem; padding-top: 0.25rem;">
                <svg class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                    <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Loading academic session results...
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             PIN GATEKEEPER CARD (When term is selected but locked)
        ═══════════════════════════════════════════════════════════════ --}}
        @if ($session_id && $term_id && !$isUnlocked && !$isImpersonating)
            <div class="sr-pin-gate">
                <div class="sr-pin-icon-wrap">
                    <svg class="sr-pin-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <h3>Result Checker PIN Required</h3>
                <p class="sr-pin-sub">Enter your 6-digit scratch card PIN for this term to unlock and view your results.</p>

                <form wire:submit.prevent="verifyPin" class="sr-pin-form">
                    <div class="sr-pin-input-group">
                        <input
                            type="text"
                            wire:model="pin_input"
                            maxlength="6"
                            placeholder="Enter 6-digit PIN"
                            autocomplete="off"
                            class="sr-pin-input"
                        />
                        <button type="submit" class="sr-pin-btn" wire:loading.attr="disabled" wire:target="verifyPin" style="display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem;">
                            <svg wire:loading wire:target="verifyPin" class="animate-spin" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="verifyPin">Unlock Result</span>
                            <span wire:loading wire:target="verifyPin">Verifying...</span>
                        </button>
                    </div>
                    @if ($pin_error)
                        <div class="sr-pin-error">
                            {{ $pin_error }}
                        </div>
                    @endif
                </form>

                <div class="sr-pin-help">
                    Obtain a scratch card from the school administration. Gently scratch off the protective layer to reveal your 6-digit PIN. Each PIN is permanently linked to your profile for this term.
                </div>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════════
             NO RESULTS STATE (Unlocked but not published/finalized)
        ═══════════════════════════════════════════════════════════════ --}}
        @if ($session_id && $term_id && $isUnlocked && !$loaded && !$isImpersonating)
            <div class="sr-empty">
                <svg class="sr-empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Zm3.75 11.625a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <h4>No results found</h4>
                <p>Results for this session and term haven't been published yet.</p>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════════
             RESULT CARD (TERMINAL & MID-TERM TABS)
        ═══════════════════════════════════════════════════════════════ --}}
        @if ($loaded && ($hasMidterm || !empty($resultData)))
            @if ($hasMidterm && !empty($resultData))
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1.25rem;">
                    <button type="button" wire:click="$set('activeTab', 'terminal')" style="padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; border: none; cursor: pointer; background: {{ $activeTab === 'terminal' ? '#4f46e5' : '#f1f5f9' }}; color: {{ $activeTab === 'terminal' ? '#ffffff' : '#475569' }};">
                        Terminal Report Card
                    </button>
                    <button type="button" wire:click="$set('activeTab', 'midterm')" style="padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; border: none; cursor: pointer; background: {{ $activeTab === 'midterm' ? '#4f46e5' : '#f1f5f9' }}; color: {{ $activeTab === 'midterm' ? '#ffffff' : '#475569' }};">
                        Mid-Term Progress Report
                    </button>
                </div>
            @endif

            @if ($activeTab === 'terminal' && !empty($resultData))
                @php
                    $tr = $resultData['term_result'];
                    $rcSettings = $resultData['settings'] ?? [];
                    $isRc = fn(string $k) => \App\Services\ReportCardSettingsService::isEnabled($rcSettings, $k);

                    $showAvg   = $isRc('rc_show_term_percentage');
                    $showFinalGrd = $isRc('rc_show_final_grade');
                    $showPos   = $isRc('rc_show_overall_position') || $isRc('rc_show_arm_dept_position');
                    $showTotalGrand = $isRc('rc_show_total_obtained');

                    $showScoreHeads = $isRc('rc_show_score_heads');
                    $showCumTerms   = ($resultData['is_cumulative'] ?? false) && $isRc('rc_show_cumulative_terms');
                    $showSubjTotal  = $isRc('rc_show_subject_total');
                    $showSubjGrade  = $isRc('rc_show_subject_grade');
                    $showSubjPos    = $isRc('rc_show_subject_position');
                    $showSubjRemark = $isRc('rc_show_subject_teacher_remark');
                @endphp

            {{-- Hero Stats --}}
            @if ($showAvg || $showFinalGrd || $showPos || $showTotalGrand)
            <div class="sr-hero">
                @if ($showAvg)
                <div class="sr-stat-card avg-card">
                    <div class="stat-value">{{ (float) $tr['average'] }}%</div>
                    <div class="stat-label">Average</div>
                </div>
                @endif

                @if ($showFinalGrd)
                <div class="sr-stat-card grade-card">
                    <div class="stat-value">{{ $tr['grade'] }}</div>
                    <div class="stat-label">Overall Grade</div>
                </div>
                @endif

                @if ($showPos)
                <div class="sr-stat-card pos-card">
                    @if(!empty($tr['department_position']) && $isRc('rc_show_arm_dept_position'))
                        <div class="stat-value">{{ $tr['department_position'] }}<span style="font-size:0.85rem;font-weight:600;color:#9ca3af;"> / {{ $tr['department_population'] }}</span></div>
                        <div class="stat-label">Dept Position ({{ $resultData['student']['department'] ?? 'Dept' }})</div>
                        @if ($isRc('rc_show_overall_position'))
                            <div style="font-size:0.7rem;color:#9ca3af;margin-top:2px;">Set: {{ $tr['overall_position'] }} of {{ $tr['class_population'] ?? $resultData['class_size'] }}</div>
                        @endif
                    @elseif(!empty($tr['arm_position']) && $isRc('rc_show_arm_dept_position'))
                        <div class="stat-value">{{ $tr['arm_position'] }}<span style="font-size:0.85rem;font-weight:600;color:#9ca3af;"> / {{ $tr['arm_population'] }}</span></div>
                        <div class="stat-label">Arm Position ({{ $resultData['student']['arm'] ?? 'Arm' }})</div>
                        @if ($isRc('rc_show_overall_position'))
                            <div style="font-size:0.7rem;color:#9ca3af;margin-top:2px;">Set: {{ $tr['overall_position'] }} of {{ $tr['class_population'] ?? $resultData['class_size'] }}</div>
                        @endif
                    @elseif ($isRc('rc_show_overall_position'))
                        <div class="stat-value">{{ $tr['overall_position'] }}<span style="font-size:0.9rem;font-weight:400;color:#9ca3af;"> / {{ $tr['class_population'] ?? $resultData['class_size'] }}</span></div>
                        <div class="stat-label">Class Position</div>
                    @endif
                </div>
                @endif

                @if ($showTotalGrand)
                <div class="sr-stat-card total-card">
                    <div class="stat-value">{{ number_format($tr['grand_total'], 0) }}</div>
                    <div class="stat-label">Grand Total</div>
                </div>
                @endif
            </div>
            @endif

            {{-- Info Bar + Table --}}
            <div class="sr-info-bar">
                <div class="info-left">
                    <div class="student-avatar">
                        {{ strtoupper(substr($resultData['student']['name'], 0, 1)) }}
                    </div>
                    <div>
                        <div class="student-name">{{ $resultData['student']['name'] }}</div>
                        <div class="student-meta">{{ $resultData['classroom'] }} · {{ $resultData['term_name'] }} · {{ $resultData['session_name'] }}</div>
                    </div>
                </div>
                <a href="{{ route('student.result-pdf', ['session_id' => $session_id, 'term_id' => $term_id]) }}"
                   target="_blank"
                   class="sr-download-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download PDF
                </a>
            </div>

            <div class="sr-table-wrap">
                <table class="sr-table">
                    <thead>
                        <tr>
                            <th style="min-width:140px;">Subject</th>
                            @if ($showScoreHeads)
                                @foreach ($resultData['score_heads'] as $sh)
                                    <th>{{ $sh['name'] }}</th>
                                @endforeach
                            @endif
                            @if ($resultData['is_cumulative'] ?? false)
                                @if ($showCumTerms)
                                    <th>3rd Total</th>
                                    <th>1st Term</th>
                                    <th>2nd Term</th>
                                @endif
                                @if ($showSubjTotal)
                                    <th>Average</th>
                                @endif
                            @else
                                @if ($showSubjTotal)
                                    <th>Total</th>
                                @endif
                            @endif
                            @if ($showSubjGrade)
                                <th>Grade</th>
                            @endif
                            @if ($showSubjPos)
                                <th>Pos</th>
                            @endif
                            @if ($showSubjRemark)
                                <th style="text-align:left;">Remark</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resultData['subjects'] as $row)
                            <tr>
                                <td>{{ $row['subject'] }}</td>
                                @if ($showScoreHeads)
                                    @foreach ($resultData['score_heads'] as $sh)
                                        <td>{{ isset($row['scores'][$sh['id']]) ? (is_numeric($row['scores'][$sh['id']]) ? (int) ceil((float) $row['scores'][$sh['id']]) : $row['scores'][$sh['id']]) : '–' }}</td>
                                    @endforeach
                                @endif
                                @if ($resultData['is_cumulative'] ?? false)
                                    @if ($showCumTerms)
                                        <td class="total-cell">{{ is_numeric($row['term3_raw'] ?? null) ? (int) ceil((float) $row['term3_raw']) : ($row['term3_raw'] ?? '—') }}</td>
                                        <td>{{ is_numeric($row['term1'] ?? null) ? (int) ceil((float) $row['term1']) : ($row['term1'] ?? '—') }}</td>
                                        <td>{{ is_numeric($row['term2'] ?? null) ? (int) ceil((float) $row['term2']) : ($row['term2'] ?? '—') }}</td>
                                    @endif
                                    @if ($showSubjTotal)
                                        <td class="total-cell">{{ is_numeric($row['average'] ?? null) ? (int) ceil((float) $row['average']) : ($row['average'] ?? '—') }}</td>
                                    @endif
                                @else
                                    @if ($showSubjTotal)
                                        <td class="total-cell">{{ is_numeric($row['total'] ?? null) ? (int) ceil((float) $row['total']) : ($row['total'] ?? '—') }}</td>
                                    @endif
                                @endif
                                @if ($showSubjGrade)
                                    <td>
                                        <span class="grade-badge grade-{{ $row['grade'] }}">{{ $row['grade'] }}</span>
                                    </td>
                                @endif
                                @if ($showSubjPos)
                                    <td>{{ $row['position'] }}</td>
                                @endif
                                @if ($showSubjRemark)
                                    <td class="remark-cell" style="text-align:left;">{{ $row['remark'] }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Summary Footer --}}
                @if ($isRc('rc_show_performance_summary'))
                <div class="sr-summary-footer">
                    @if ($isRc('rc_show_total_obtained'))
                    <div class="sr-summary-item">
                        <div class="summary-label">Total Obtainable</div>
                        <div class="summary-value">{{ is_numeric($resultData['total_obtainable'] ?? null) ? (int) ceil((float) $resultData['total_obtainable']) : ($resultData['total_obtainable'] ?? '—') }}</div>
                    </div>
                    <div class="sr-summary-item">
                        <div class="summary-label">Grand Total</div>
                        <div class="summary-value">{{ is_numeric($tr['grand_total'] ?? null) ? (int) ceil((float) $tr['grand_total']) : ($tr['grand_total'] ?? '—') }}</div>
                    </div>
                    @endif
                    @if ($isRc('rc_show_term_percentage'))
                    <div class="sr-summary-item">
                        <div class="summary-label">Average</div>
                        <div class="summary-value">{{ is_numeric($tr['average'] ?? null) ? (int) ceil((float) $tr['average']) . '%' : ($tr['average'] ?? '—') }}</div>
                    </div>
                    @endif
                    @if ($isRc('rc_show_final_grade'))
                    <div class="sr-summary-item">
                        <div class="summary-label">Overall Grade</div>
                        <div class="summary-value">{{ $tr['grade'] }}</div>
                    </div>
                    @endif
                    @if ($isRc('rc_show_overall_position') || $isRc('rc_show_arm_dept_position'))
                    <div class="sr-summary-item">
                        <div class="summary-label">
                            @if(!empty($tr['department_position']) && $isRc('rc_show_arm_dept_position'))
                                Dept Position
                            @elseif(!empty($tr['arm_position']) && $isRc('rc_show_arm_dept_position'))
                                Arm Position
                            @elseif ($isRc('rc_show_overall_position'))
                                Position
                            @endif
                        </div>
                        <div class="summary-value">
                            @if(!empty($tr['department_position']) && $isRc('rc_show_arm_dept_position'))
                                {{ $tr['department_position'] }} of {{ $tr['department_population'] }}
                            @elseif(!empty($tr['arm_position']) && $isRc('rc_show_arm_dept_position'))
                                {{ $tr['arm_position'] }} of {{ $tr['arm_population'] }}
                            @elseif ($isRc('rc_show_overall_position'))
                                {{ $tr['overall_position'] }} of {{ $tr['class_population'] ?? $resultData['class_size'] }}
                            @endif
                        </div>
                    </div>
                    @endif
                    @if((!empty($tr['department_position']) || !empty($tr['arm_position'])) && $isRc('rc_show_overall_position') && $isRc('rc_show_arm_dept_position'))
                        <div class="sr-summary-item">
                            <div class="summary-label">Overall Set Position</div>
                            <div class="summary-value">{{ $tr['overall_position'] }} of {{ $tr['class_population'] ?? $resultData['class_size'] }}</div>
                        </div>
                    @endif
                    <div class="sr-summary-item">
                        <div class="summary-label">Subjects</div>
                        <div class="summary-value">{{ $tr['subjects_count'] }}</div>
                    </div>
                </div>
                @endif
            </div>
            @elseif ($activeTab === 'midterm' && !empty($midtermData))
                @php
                    $mtr = $midtermData['term_result'];
                @endphp

                {{-- Hero Stats --}}
                <div class="sr-hero">
                    <div class="sr-stat-card avg-card">
                        <div class="stat-value">{{ is_numeric($mtr['percentage'] ?? null) ? (int) ceil((float) $mtr['percentage']) . '%' : '—' }}</div>
                        <div class="stat-label">Mid-Term Average</div>
                    </div>
                    <div class="sr-stat-card grade-card">
                        <div class="stat-value">{{ $mtr['grade'] ?? '—' }}</div>
                        <div class="stat-label">Grade ({{ $mtr['remark'] ?? '—' }})</div>
                    </div>
                    <div class="sr-stat-card pos-card">
                        <div class="stat-value">{{ is_numeric($mtr['total_obtained'] ?? null) ? (int) ceil((float) $mtr['total_obtained']) : '—' }}</div>
                        <div class="stat-label">Total Obtained</div>
                    </div>
                    <div class="sr-stat-card total-card">
                        <div class="stat-value">{{ is_numeric($mtr['total_obtainable'] ?? null) ? (int) ceil((float) $mtr['total_obtainable']) : '—' }}</div>
                        <div class="stat-label">Total Obtainable</div>
                    </div>
                </div>

                {{-- Info Bar + Download --}}
                <div class="sr-info-bar">
                    <div class="info-left">
                        <div class="student-avatar">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="student-name">{{ Auth::user()->name }}</div>
                            <div class="student-meta">Mid-Term Evaluation · Weight: {{ is_numeric($midtermData['midterm_weight'] ?? null) ? (int) ceil((float) $midtermData['midterm_weight']) : ($midtermData['midterm_weight'] ?? '—') }}%</div>
                        </div>
                    </div>
                    <a href="{{ route('student.midterm-pdf', ['session_id' => $session_id, 'term_id' => $term_id]) }}"
                       target="_blank"
                       class="sr-download-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Download Mid-Term PDF
                    </a>
                </div>

                <div class="sr-table-wrap">
                    <table class="sr-table">
                        <thead>
                            <tr>
                                <th style="min-width:140px; text-align:left;">Subject</th>
                                @foreach ($midtermData['midterm_heads'] as $head)
                                    <th>{{ $head['name'] }} ({{ is_numeric($head['effective_max'] ?? null) ? (int) ceil((float) $head['effective_max']) : ($head['effective_max'] ?? '') }})</th>
                                @endforeach
                                <th>Raw Total ({{ is_numeric($midtermData['raw_max'] ?? null) ? (int) ceil((float) $midtermData['raw_max']) : ($midtermData['raw_max'] ?? '') }})</th>
                                <th style="background:#e0e7ff; color:#3730a3;">Mid-Term ({{ is_numeric($midtermData['midterm_weight'] ?? null) ? (int) ceil((float) $midtermData['midterm_weight']) : ($midtermData['midterm_weight'] ?? '') }}%)</th>
                                <th>% Score</th>
                                <th>Grade</th>
                                <th style="text-align:left;">Remark</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($midtermData['subjects'] as $row)
                                <tr>
                                    <td class="subject-name" style="text-align:left;">{{ $row['subject'] }}</td>
                                    @foreach ($midtermData['midterm_heads'] as $head)
                                        <td>
                                            @php $hVal = $row['head_scores'][$head['id']] ?? '—'; @endphp
                                            @if($hVal === 'ABS')
                                                <span style="font-weight:800; color:#e11d48;">ABS</span>
                                            @else
                                                {{ is_numeric($hVal) ? (int) ceil((float) $hVal) : $hVal }}
                                            @endif
                                        </td>
                                    @endforeach
                                    <td style="font-weight:700;">
                                        {{ is_numeric($row['raw_score']) ? (int) ceil((float) $row['raw_score']) : $row['raw_score'] }}
                                    </td>
                                    <td style="font-weight:800; background:#eef2ff; color:#4338ca;">
                                        @if($row['scaled_score'] === 'ABS')
                                            <span style="color:#e11d48;">ABS</span>
                                        @elseif(is_numeric($row['scaled_score']))
                                            {{ (int) ceil((float) $row['scaled_score']) }}
                                        @else
                                            {{ $row['scaled_score'] }}
                                        @endif
                                    </td>
                                    <td style="font-weight:700;">
                                        @if($row['percentage'] === 'ABS')
                                            <span style="color:#e11d48;">ABS</span>
                                        @elseif(is_numeric($row['percentage']))
                                            {{ (int) ceil((float) $row['percentage']) }}%
                                        @else
                                            {{ $row['percentage'] }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="sr-grade-pill">{{ $row['grade'] ?? '—' }}</span>
                                    </td>
                                    <td class="remark-cell" style="text-align:left;">
                                        {{ $row['remark'] ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Comments --}}
                @if(!empty($mtr['teacher_comment']))
                    <div style="background:white; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; margin-bottom:1rem;">
                        <h4 style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:#6b7280; margin-bottom:0.25rem;">Class Teacher's Observation</h4>
                        <p style="font-style:italic; color:#1e293b;">{{ $mtr['teacher_comment'] }}</p>
                    </div>
                @endif
                @if(!empty($mtr['head_teacher_comment']))
                    <div style="background:white; border:1px solid #e5e7eb; border-radius:12px; padding:1.25rem; margin-bottom:1.5rem;">
                        <h4 style="font-size:0.75rem; font-weight:800; text-transform:uppercase; color:#6b7280; margin-bottom:0.25rem;">Principal / Head Teacher's Remark</h4>
                        <p style="font-style:italic; color:#1e293b;">{{ $mtr['head_teacher_comment'] }}</p>
                    </div>
                @endif
            @endif
        @endif

        @if ($session_id && $term_id && ! $loaded)
            <div class="sr-empty" style="border: 2px dashed #f59e0b; background: rgba(245,158,11,0.03); padding: 3rem 1.5rem; border-radius: 1rem; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center; margin-top: 1rem;">
                <svg style="width: 48px; height: 48px; color: #f59e0b; margin-bottom: 1rem;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h4 style="font-size: 1.125rem; font-weight: 800; color: #b45309;" class="dark:text-amber-400">Results Processing in Progress</h4>
                <p style="font-size: 0.875rem; color: #92400e; margin-top: 0.5rem; max-width: 28rem; line-height: 1.6;" class="dark:text-amber-500">
                    Your class teacher is currently finalizing and processing the results for this term. Please check back later once results are officially published.
                </p>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════════
             INITIAL EMPTY STATE
        ═══════════════════════════════════════════════════════════════ --}}
        @if (empty($sessions))
            <div class="sr-empty">
                <svg class="sr-empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                </svg>
                <h4>No enrollments found</h4>
                <p>You have not been enrolled in any academic session yet.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
