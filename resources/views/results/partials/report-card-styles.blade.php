@php
    $brandColor = $brandColor ?? ($settings['primary_color'] ?? config('app.tenant_primary_color', '#1e3a8a'));
    $marginCss  = $marginCss ?? '12mm 14mm 12mm 14mm';
@endphp
<style>
    /* ── Reset & Document Base ─────────────────────────────────────────── */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    @page {
        size: a4 portrait;
        margin: {{ $marginCss }};
    }
    body {
        font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
        font-size: 9.5px;
        color: #1e293b;
        line-height: 1.3;
        background: #ffffff;
    }

    .student-page-wrapper {
        page-break-after: always;
        clear: both;
    }
    .student-page-wrapper:last-child {
        page-break-after: avoid;
    }

    /* ── Header Section ────────────────────────────────────────────────── */
    .school-header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5px;
    }
    .header-logo-cell {
        width: 15%;
        text-align: center;
        vertical-align: middle;
    }
    .header-center-cell {
        text-align: center;
        vertical-align: middle;
        padding: 0 6px;
    }
    .header-photo-cell {
        width: 15%;
        text-align: center;
        vertical-align: middle;
    }
    .school-logo {
        max-width: 65px;
        max-height: 65px;
    }
    .student-photo {
        width: 62px;
        height: 74px;
        object-fit: cover;
        border: 2px solid {{ $brandColor }};
        border-radius: 4px;
    }
    .student-photo-placeholder {
        width: 62px;
        height: 74px;
        border: 1px dashed #94a3b8;
        border-radius: 4px;
        display: inline-block;
        line-height: 74px;
        font-size: 8.5px;
        color: #94a3b8;
        text-align: center;
        background: #f8fafc;
    }
    .school-name {
        font-size: 16.5px;
        font-weight: 900;
        color: {{ $brandColor }};
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 2px;
    }
    .school-address {
        font-size: 8.5px;
        color: #475569;
        font-weight: 600;
        margin-bottom: 1.5px;
    }
    .school-contacts {
        font-size: 8px;
        color: #64748b;
        margin-bottom: 2.5px;
    }
    .school-motto {
        font-size: 8.5px;
        font-style: italic;
        font-weight: 700;
        color: {{ $brandColor }};
        margin-bottom: 3.5px;
    }
    .report-title-badge {
        display: inline-block;
        background: {{ $brandColor }};
        color: #ffffff;
        font-size: 9.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 2.5px 16px;
        border-radius: 20px;
    }

    /* ── Term Ribbon ───────────────────────────────────────────────────── */
    .term-ribbon {
        background: #f1f5f9;
        border-top: 1.5px solid {{ $brandColor }};
        border-bottom: 1.5px solid {{ $brandColor }};
        text-align: center;
        padding: 3px 0;
        font-size: 9.5px;
        font-weight: 800;
        color: {{ $brandColor }};
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        text-transform: uppercase;
    }

    /* ── Student Bio-Data & Attendance Grid ────────────────────────────── */
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }
    .info-table td {
        padding: 3px 5px;
        border: 1px solid #cbd5e1;
        font-size: 8.5px;
        vertical-align: middle;
    }
    .info-table .lbl {
        font-weight: 800;
        color: #475569;
        background: #f8fafc;
        width: 18%;
        text-transform: uppercase;
        font-size: 8px;
        letter-spacing: 0.3px;
    }
    .info-table .val {
        font-weight: 700;
        color: #0f172a;
        width: 32%;
    }
    .badge-dept {
        display: inline-block;
        background: rgba(79, 70, 229, 0.12);
        color: #4338ca;
        padding: 1px 5px;
        border-radius: 3px;
        font-weight: 800;
        font-size: 8.5px;
    }
    .badge-adm {
        display: inline-block;
        background: #e2e8f0;
        color: #1e293b;
        padding: 1px 5px;
        border-radius: 3px;
        font-weight: 800;
        font-size: 8.5px;
    }

    /* ── Section Header ────────────────────────────────────────────────── */
    .section-header {
        font-size: 8.5px;
        font-weight: 800;
        color: {{ $brandColor }};
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2.5px;
        border-bottom: 1px solid #cbd5e1;
        padding-bottom: 1.5px;
    }

    /* ── Academic Performance Table ────────────────────────────────────── */
    .results-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5px;
    }
    .results-table th {
        background: {{ $brandColor }};
        color: #ffffff;
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 3.5px 3px;
        text-align: center;
        border: 1px solid {{ $brandColor }};
    }
    .results-table th.th-left { text-align: left; padding-left: 5px; }
    .results-table td {
        padding: 2.8px 3px;
        border: 1px solid #cbd5e1;
        text-align: center;
        font-size: 8.5px;
    }
    .results-table td.td-left {
        text-align: left;
        padding-left: 5px;
        font-weight: 700;
        color: #0f172a;
    }
    .results-table tr:nth-child(even) td {
        background: #f8fafc;
    }
    .score-total {
        font-weight: 800;
        color: #0f172a;
    }
    .grade-a { color: #047857; font-weight: 900; }
    .grade-b { color: #1d4ed8; font-weight: 900; }
    .grade-c { color: #b45309; font-weight: 900; }
    .grade-d, .grade-e { color: #c2410c; font-weight: 900; }
    .grade-f { color: #b91c1c; font-weight: 900; }
    .remark-cell {
        text-align: left !important;
        padding-left: 5px !important;
        font-size: 8px !important;
        color: #475569;
    }

    /* ── Academic Summary Statistics ───────────────────────────────────── */
    .summary-grid-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5px;
    }
    .summary-grid-table td {
        padding: 3px 5px;
        border: 1px solid #cbd5e1;
        font-size: 8.5px;
    }
    .summary-grid-table .s-lbl {
        font-weight: 800;
        color: #475569;
        background: #f8fafc;
        width: 25%;
        font-size: 8px;
        text-transform: uppercase;
    }
    .summary-grid-table .s-val {
        font-weight: 800;
        color: {{ $brandColor }};
        width: 25%;
        font-size: 9px;
    }

    /* ── Affective & Psychomotor Traits ────────────────────────────────── */
    .traits-container {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 4px;
    }
    .traits-col {
        vertical-align: top;
    }
    .trait-table {
        width: 100%;
        border-collapse: collapse;
    }
    .trait-table th {
        background: #e2e8f0;
        color: #334155;
        font-size: 7.5px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 2.5px 5px;
        border: 1px solid #cbd5e1;
        text-align: left;
    }
    .trait-table th.th-ctr { text-align: center; width: 35%; }
    .trait-table td {
        padding: 2.2px 5px;
        border: 1px solid #cbd5e1;
        font-size: 8px;
    }
    .trait-table td.td-ctr { text-align: center; font-weight: 800; color: #0f172a; }
    .trait-table tr:nth-child(even) td { background: #f8fafc; }

    /* ── Remarks & Resumption ──────────────────────────────────────────── */
    .remark-card {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 3.5px 6px;
        margin-bottom: 3.5px;
        background: #ffffff;
        page-break-inside: avoid;
    }
    .remark-title {
        font-size: 7.5px;
        font-weight: 800;
        color: {{ $brandColor }};
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 1.5px;
    }
    .remark-text {
        font-size: 8.5px;
        font-style: italic;
        color: #1e293b;
        min-height: 12px;
    }

    /* ── Next Term Resumption Ribbon ───────────────────────────────────── */
    .resumption-banner {
        background: #f8fafc;
        border: 1.2px dashed {{ $brandColor }};
        border-radius: 4px;
        padding: 3px;
        text-align: center;
        font-size: 8px;
        font-weight: 800;
        color: {{ $brandColor }};
        margin-bottom: 4px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    /* ── Signatures Section ────────────────────────────────────────────── */
    .sig-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
        page-break-inside: avoid;
    }
    .sig-col {
        text-align: center;
        vertical-align: bottom;
        padding: 0 8px;
    }
    .sig-line {
        border-top: 1px solid #64748b;
        padding-top: 2px;
        font-size: 7.5px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
    }
    .stamp-box {
        display: inline-block;
        border: 1px dashed #94a3b8;
        border-radius: 4px;
        padding: 2.5px 8px;
        font-size: 7px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ── Footer ────────────────────────────────────────────────────────── */
    .footer-note {
        margin-top: 3px;
        border-top: 1px solid #e2e8f0;
        padding-top: 1.5px;
        text-align: center;
        font-size: 6.5px;
        color: #94a3b8;
    }
</style>
