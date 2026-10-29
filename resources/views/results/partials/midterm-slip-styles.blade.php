@php
    $brandColor = $brandColor ?? ($settings['primary_color'] ?? config('app.tenant_primary_color', '#1e3a8a'));
@endphp
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    @page {
        size: a4 portrait;
        margin: 14mm 14mm 14mm 14mm;
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
        width: 70%;
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

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }
    .info-table td {
        padding: 3px 6px;
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

    .section-header {
        font-size: 8.5px;
        font-weight: 800;
        color: #ffffff;
        background: {{ $brandColor }};
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 2.5px 7px;
        margin-bottom: 0;
        border-radius: 2px 2px 0 0;
    }

    .results-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }
    .results-table th {
        background: #f1f5f9;
        color: #334155;
        font-size: 7.5px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 3px 4px;
        border: 1px solid #cbd5e1;
        text-align: center;
    }
    .results-table th.th-left { text-align: left; }
    .results-table td {
        padding: 3px 4px;
        border: 1px solid #cbd5e1;
        font-size: 8px;
        vertical-align: middle;
        text-align: center;
    }
    .results-table td.td-left { text-align: left; font-weight: 700; color: #0f172a; }
    .results-table tr:nth-child(even) td { background: #f8fafc; }

    .summary-grid-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }
    .summary-grid-table td {
        padding: 4px 6px;
        border: 1px solid #cbd5e1;
        text-align: center;
        vertical-align: middle;
    }
    .summary-grid-table .s-lbl {
        font-weight: 700;
        color: #475569;
        background: #f8fafc;
        font-size: 7.5px;
        text-transform: uppercase;
    }
    .summary-grid-table .s-val {
        font-weight: 900;
        color: {{ $brandColor }};
        font-size: 9.5px;
    }

    .remark-card {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 4px 8px;
        margin-bottom: 5px;
        background: #ffffff;
        page-break-inside: avoid;
    }
    .remark-title {
        font-size: 7.5px;
        font-weight: 800;
        color: {{ $brandColor }};
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 2px;
    }
    .remark-text {
        font-size: 8.5px;
        font-style: italic;
        color: #1e293b;
        min-height: 14px;
    }

    .sig-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        page-break-inside: avoid;
    }
    .sig-col {
        width: 33.33%;
        text-align: center;
        vertical-align: bottom;
        padding: 0 8px;
    }
    .sig-line {
        border-top: 1px solid #64748b;
        margin-top: 35px;
        padding-top: 3px;
        font-size: 8px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
    }
</style>
