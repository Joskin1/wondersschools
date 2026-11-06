@php
    $brandColor = $brandColor ?? ($settings['primary_color'] ?? config('app.tenant_primary_color', '#1e3a8a'));
    $marginCss  = $marginCss ?? '12mm 14mm 12mm 14mm';
@endphp
<style>
    /* ── Reset & Document Base ─────────────────────────────────────────── */
    table, th, td, p, div, span, img, input, h1, h2, h3, h4, h5, h6 {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }
    @page {
        size: a4 portrait;
        margin: {{ $marginCss }};
    }
    body {
        font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
        font-size: 10px;
        color: #1e293b;
        line-height: 1.2;
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
        margin-bottom: 2.5px;
    }
    .header-logo-cell {
        width: 14%;
        text-align: center;
        vertical-align: middle;
    }
    .header-center-cell {
        text-align: center;
        vertical-align: middle;
        padding: 0 4px;
    }
    .header-photo-cell {
        width: 14%;
        text-align: center;
        vertical-align: middle;
    }
    .school-logo {
        max-width: 56px;
        max-height: 56px;
    }
    .student-photo {
        width: 54px;
        height: 64px;
        object-fit: cover;
        border: 2px solid {{ $brandColor }};
        border-radius: 4px;
    }
    .student-photo-placeholder {
        width: 54px;
        height: 64px;
        border: 1px dashed #94a3b8;
        border-radius: 4px;
        display: inline-block;
        line-height: 64px;
        font-size: 8.5px;
        color: #94a3b8;
        text-align: center;
        background: #f8fafc;
    }
    .school-name {
        font-size: 18px;
        font-weight: 900;
        color: {{ $brandColor }};
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1.5px;
    }
    .school-address {
        font-size: 9.5px;
        color: #475569;
        font-weight: 600;
        margin-bottom: 1px;
    }
    .school-contacts {
        font-size: 9px;
        color: #64748b;
        margin-bottom: 1.5px;
    }
    .school-motto {
        font-size: 9.5px;
        font-style: italic;
        font-weight: 700;
        color: {{ $brandColor }};
        margin-bottom: 2px;
    }
    .report-title-badge {
        display: inline-block;
        background: {{ $brandColor }};
        color: #ffffff;
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 2px 14px;
        border-radius: 20px;
    }

    /* ── Term Ribbon ───────────────────────────────────────────────────── */
    .term-ribbon {
        background: #f1f5f9;
        border-top: 1.5px solid {{ $brandColor }};
        border-bottom: 1.5px solid {{ $brandColor }};
        text-align: center;
        padding: 2px 0;
        font-size: 10px;
        font-weight: 800;
        color: {{ $brandColor }};
        letter-spacing: 0.5px;
        margin-bottom: 3.5px;
        text-transform: uppercase;
    }

    /* ── Student Bio-Data & Attendance Grid ────────────────────────────── */
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 3.5px;
    }
    .info-table td {
        padding: 2px 4.5px;
        border: 1px solid #cbd5e1;
        font-size: 9.5px;
        vertical-align: middle;
    }
    .info-table .lbl {
        font-weight: 800;
        color: #475569;
        background: #f8fafc;
        width: 18%;
        text-transform: uppercase;
        font-size: 9px;
        letter-spacing: 0.3px;
    }
    .info-table .val {
        font-weight: 700;
        color: #0f172a;
        width: 32%;
        font-size: 10px;
    }
    .badge-dept {
        display: inline-block;
        background: rgba(79, 70, 229, 0.12);
        color: #4338ca;
        padding: 1px 5px;
        border-radius: 3px;
        font-weight: 800;
        font-size: 9.5px;
    }
    .badge-adm {
        display: inline-block;
        background: #e2e8f0;
        color: #1e293b;
        padding: 1px 5px;
        border-radius: 3px;
        font-weight: 800;
        font-size: 9.5px;
    }

    /* ── Section Header ────────────────────────────────────────────────── */
    .section-header {
        font-size: 9.5px;
        font-weight: 800;
        color: {{ $brandColor }};
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1.5px;
        border-bottom: 1px solid #cbd5e1;
        padding-bottom: 0.5px;
    }

    /* ── Academic Performance Table ────────────────────────────────────── */
    .results-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 3px;
    }
    .results-table th {
        background: {{ $brandColor }};
        color: #ffffff;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 2.5px 2px;
        text-align: center;
        border: 1px solid {{ $brandColor }};
        vertical-align: middle;
    }
    .results-table th.th-left { text-align: left; padding-left: 5px; }
    .results-table td {
        padding: 1.6px 2px;
        border: 1px solid #cbd5e1;
        text-align: center;
        font-size: 9.5px;
    }
    .results-table td.td-left {
        text-align: left;
        padding-left: 5px;
        font-weight: 700;
        color: #0f172a;
        font-size: 9.5px;
    }
    .results-table tr:nth-child(even) td {
        background: #f8fafc;
    }
    .score-total {
        font-weight: 800;
        color: #0f172a;
        font-size: 9.5px;
    }
    .grade-a { color: #047857; font-weight: 900; font-size: 9.5px; }
    .grade-b { color: #1d4ed8; font-weight: 900; font-size: 9.5px; }
    .grade-c { color: #b45309; font-weight: 900; font-size: 9.5px; }
    .grade-d, .grade-e { color: #c2410c; font-weight: 900; font-size: 9.5px; }
    .grade-f { color: #b91c1c; font-weight: 900; font-size: 9.5px; }
    .remark-cell {
        text-align: left !important;
        padding-left: 5px !important;
        font-size: 9px !important;
        color: #475569;
    }

    /* ── Academic Summary Statistics ───────────────────────────────────── */
    .summary-grid-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 3px;
    }
    .summary-grid-table td {
        padding: 2px 4.5px;
        border: 1px solid #cbd5e1;
        font-size: 9.5px;
    }
    .summary-grid-table .s-lbl {
        font-weight: 800;
        color: #475569;
        background: #f8fafc;
        width: 25%;
        font-size: 9px;
        text-transform: uppercase;
    }
    .summary-grid-table .s-val {
        font-weight: 800;
        color: {{ $brandColor }};
        width: 25%;
        font-size: 10px;
    }

    /* ── Affective & Psychomotor Traits ────────────────────────────────── */
    .traits-container {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2.5px;
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
        font-size: 8.5px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 1.5px 3px;
        border: 1px solid #cbd5e1;
        text-align: left;
    }
    .trait-table th.th-ctr { text-align: center; width: 35%; }
    .trait-table td {
        padding: 1.2px 3px;
        border: 1px solid #cbd5e1;
        font-size: 9px;
    }
    .trait-table td.td-ctr { text-align: center; font-weight: 800; color: #0f172a; font-size: 9px; }
    .trait-table tr:nth-child(even) td { background: #f8fafc; }

    /* ── Remarks & Resumption ──────────────────────────────────────────── */
    .remark-card {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 2.5px 5px;
        margin-bottom: 2.5px;
        background: #ffffff;
        page-break-inside: avoid;
    }
    .remark-title {
        font-size: 8.5px;
        font-weight: 800;
        color: {{ $brandColor }};
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 1px;
    }
    .remark-text {
        font-size: 9.5px;
        font-style: italic;
        color: #1e293b;
        min-height: 9px;
    }

    /* ── Next Term Resumption Ribbon ───────────────────────────────────── */
    .resumption-banner {
        background: #f8fafc;
        border: 1.2px dashed {{ $brandColor }};
        border-radius: 4px;
        padding: 2px;
        text-align: center;
        font-size: 9px;
        font-weight: 800;
        color: {{ $brandColor }};
        margin-bottom: 2.5px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    /* ── Signatures Section ────────────────────────────────────────────── */
    .sig-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 2px;
        page-break-inside: avoid;
    }
    .sig-col {
        text-align: center;
        vertical-align: bottom;
        padding: 0 6px;
    }
    .sig-line {
        border-top: 1px solid #64748b;
        padding-top: 1.5px;
        font-size: 8.5px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
    }
    .sig-img-box {
        height: 32px;
        line-height: 32px;
        text-align: center;
        margin-bottom: 1.5px;
    }
    .sig-img {
        max-height: 30px;
        max-width: 130px;
        display: inline-block;
        vertical-align: bottom;
    }
    .sig-placeholder {
        height: 32px;
    }
    .sig-subname {
        margin-top: 1.5px;
        font-size: 8.5px;
        font-weight: 700;
        color: #1e293b;
        text-transform: none;
    }
    .stamp-img-box {
        text-align: center;
        margin-bottom: 1.5px;
    }
    .stamp-img {
        max-height: 40px;
        max-width: 85px;
        display: inline-block;
        vertical-align: middle;
    }
    .stamp-box {
        display: inline-block;
        border: 1px dashed #94a3b8;
        border-radius: 4px;
        padding: 2px 7px;
        font-size: 7.5px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ── Footer ────────────────────────────────────────────────────────── */
    .footer-note {
        margin-top: 2px;
        border-top: 1px solid #e2e8f0;
        padding-top: 1px;
        text-align: center;
        font-size: 7.5px;
        color: #94a3b8;
    }
</style>
