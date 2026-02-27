<x-filament-panels::page>
    <style>
        /* ── Design System ───────────────────────────────────────────────── */
        .sr-page { max-width: 960px; margin: 0 auto; }

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
            background: #374151; border-color: #4b5563; color: #f3f4f6;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
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
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
            margin-bottom: 1.5rem;
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

        @media (max-width: 768px) {
            .sr-filters { grid-template-columns: 1fr; }
            .sr-hero { grid-template-columns: repeat(2, 1fr); }
            .sr-summary-footer { grid-template-columns: repeat(2, 1fr); }
        }
    </style>

    <div class="sr-page">
        {{-- ═══════════════════════════════════════════════════════════════
             FILTER BAR
        ═══════════════════════════════════════════════════════════════ --}}
        <div class="sr-filters">
            <div class="sr-filter-group">
                <label for="session_id">Academic Session</label>
                <select wire:model.live="session_id" id="session_id">
                    <option value="">— Select Session —</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session['id'] }}">{{ $session['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sr-filter-group">
                <label for="term_id">Term</label>
                <select wire:model.live="term_id" id="term_id" @if(empty($terms)) disabled @endif>
                    <option value="">— Select Term —</option>
                    @foreach ($terms as $term)
                        <option value="{{ $term['id'] }}">{{ $term['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             NO RESULTS STATE
        ═══════════════════════════════════════════════════════════════ --}}
        @if ($session_id && $term_id && !$loaded)
            <div class="sr-empty">
                <svg class="sr-empty-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Zm3.75 11.625a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                <h4>No results found</h4>
                <p>Results for this session and term haven't been published yet.</p>
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════════════
             RESULT CARD
        ═══════════════════════════════════════════════════════════════ --}}
        @if ($loaded && !empty($resultData))
            @php
                $tr = $resultData['term_result'];
            @endphp

            {{-- Hero Stats --}}
            <div class="sr-hero">
                <div class="sr-stat-card avg-card">
                    <div class="stat-value">{{ (float) $tr['average'] }}%</div>
                    <div class="stat-label">Average</div>
                </div>
                <div class="sr-stat-card grade-card">
                    <div class="stat-value">{{ $tr['grade'] }}</div>
                    <div class="stat-label">Overall Grade</div>
                </div>
                <div class="sr-stat-card pos-card">
                    <div class="stat-value">{{ $tr['overall_position'] }}<span style="font-size:0.9rem;font-weight:400;color:#9ca3af;"> / {{ $resultData['class_size'] }}</span></div>
                    <div class="stat-label">Class Position</div>
                </div>
                <div class="sr-stat-card total-card">
                    <div class="stat-value">{{ number_format($tr['grand_total'], 0) }}</div>
                    <div class="stat-label">Grand Total</div>
                </div>
            </div>

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
                            @foreach ($resultData['score_heads'] as $sh)
                                <th>{{ $sh['name'] }}</th>
                            @endforeach
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Pos</th>
                            <th style="text-align:left;">Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resultData['subjects'] as $row)
                            <tr>
                                <td>{{ $row['subject'] }}</td>
                                @foreach ($resultData['score_heads'] as $sh)
                                    <td>{{ isset($row['scores'][$sh['id']]) ? (float) $row['scores'][$sh['id']] : '–' }}</td>
                                @endforeach
                                <td class="total-cell">{{ (float) $row['total'] }}</td>
                                <td>
                                    <span class="grade-badge grade-{{ $row['grade'] }}">{{ $row['grade'] }}</span>
                                </td>
                                <td>{{ $row['position'] }}</td>
                                <td class="remark-cell" style="text-align:left;">{{ $row['remark'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Summary Footer --}}
                <div class="sr-summary-footer">
                    <div class="sr-summary-item">
                        <div class="summary-label">Total Obtainable</div>
                        <div class="summary-value">{{ $resultData['total_obtainable'] }}</div>
                    </div>
                    <div class="sr-summary-item">
                        <div class="summary-label">Grand Total</div>
                        <div class="summary-value">{{ number_format($tr['grand_total'], 0) }}</div>
                    </div>
                    <div class="sr-summary-item">
                        <div class="summary-label">Average</div>
                        <div class="summary-value">{{ (float) $tr['average'] }}%</div>
                    </div>
                    <div class="sr-summary-item">
                        <div class="summary-label">Overall Grade</div>
                        <div class="summary-value">{{ $tr['grade'] }}</div>
                    </div>
                    <div class="sr-summary-item">
                        <div class="summary-label">Position</div>
                        <div class="summary-value">{{ $tr['overall_position'] }} of {{ $resultData['class_size'] }}</div>
                    </div>
                    <div class="sr-summary-item">
                        <div class="summary-label">Subjects</div>
                        <div class="summary-value">{{ $tr['subjects_count'] }}</div>
                    </div>
                </div>
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
                <h4>No results available yet</h4>
                <p>Results will appear here once your teachers have published them.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
