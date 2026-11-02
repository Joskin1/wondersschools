<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal Credentials & Sign-Off Sheet</title>
    <style>
        @page {
            margin: 10mm 12mm 12mm 12mm;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 5px;
            margin-bottom: 6px;
        }
        .header-table td {
            border: none;
            padding: 0;
        }
        .school-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .doc-title {
            font-size: 10pt;
            font-weight: 700;
            color: #0f172a;
            margin: 2px 0 1px 0;
            text-transform: uppercase;
        }
        .sub-header {
            font-size: 7.8pt;
            color: #64748b;
        }
        .instructions-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 3.5px solid #2563eb;
            padding: 4px 8px;
            margin-bottom: 8px;
            font-size: 7.5pt;
            border-radius: 2px;
        }
        .instructions-box strong {
            color: #1e3a8a;
        }
        .class-section {
            margin-bottom: 0px;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        table.data-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 8pt;
            padding: 5px 6px;
            border: 1px solid #0f172a;
            text-transform: uppercase;
            text-align: left;
        }
        table.data-table th.center, table.data-table td.center {
            text-align: center;
        }
        table.data-table td {
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            font-size: 8.5pt;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .student-name {
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
        }
        .adm-no {
            font-family: monospace;
            font-weight: bold;
            color: #1e40af;
            letter-spacing: 0.3px;
            font-size: 8.5pt;
        }
        .pwd {
            font-family: monospace;
            font-weight: bold;
            color: #047857;
            background-color: #ecfdf5;
            padding: 1px 3px;
            border-radius: 2px;
            border: 0.5px dashed #a7f3d0;
            font-size: 8.5pt;
        }
        .badge-active {
            background-color: #dcfce7;
            color: #15803d;
            font-weight: bold;
            font-size: 6.8pt;
            padding: 1px 4px;
            border-radius: 2px;
            text-align: center;
            display: inline-block;
        }
        .signature-line {
            border-bottom: 1px dotted #94a3b8;
            height: 14px;
            width: 100%;
        }
        .page-break {
            page-break-before: always;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 12px;
            font-size: 7pt;
            color: #94a3b8;
            text-align: center;
            border-top: 0.5px solid #e2e8f0;
            padding-top: 2px;
        }
    </style>
</head>
<body>
    <div class="footer">
        {{ $schoolName }} &bull; Student Portal Credentials &bull; Session: {{ $sessionName }} &bull; Portal: {{ $portalUrl }}
    </div>

    @foreach($studentsByClass as $class => $students)
        @if(!$loop->first)
            <div class="page-break"></div>
        @endif

        <table class="header-table">
            <tr>
                @if(!empty($logoBase64))
                    <td style="width: 55px; vertical-align: middle; text-align: left;">
                        <img src="{{ $logoBase64 }}" style="max-height: 48px; max-width: 48px;">
                    </td>
                @endif
                <td style="text-align: center; vertical-align: middle;">
                    <div class="school-name">{{ $schoolName }}</div>
                    <div class="doc-title">Student Portal Credentials & Sign-Off Sheet</div>
                    <div class="sub-header">Academic Session: {{ $sessionName }} &bull; Class: <strong style="color: #1e3a8a; font-size: 8.5pt;">{{ $class }}</strong> ({{ count($students) }} Students) &bull; Generated: {{ $generatedDate }}</div>
                </td>
                @if(!empty($logoBase64))
                    <td style="width: 55px;"></td>
                @endif
            </tr>
        </table>

        <div class="instructions-box">
            <strong>Portal Login URL:</strong> {{ $portalUrl }} &bull;
            <strong>Username:</strong> Admission Number (e.g. {{ $students[0]['admission_number'] ?? 'AVS2026...' }}) or Email &bull;
            <strong>Default Password:</strong> {{ $students[0]['password'] ?? 'Avoos2026#' }}
        </div>

        <div class="class-section">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="center" style="width: 5%;">S/N</th>
                        <th style="width: 33%;">Student Full Name</th>
                        <th class="center" style="width: 21%;">Admission No. (Username)</th>
                        <th class="center" style="width: 17%;">Password</th>
                        <th class="center" style="width: 8%;">Portal</th>
                        <th class="center" style="width: 16%;">Parent / Student Sign</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $s)
                        <tr>
                            <td class="center">{{ $s['sn'] }}</td>
                            <td class="student-name">{{ $s['name'] }}</td>
                            <td class="center adm-no">{{ $s['admission_number'] }}</td>
                            <td class="center"><span class="pwd">{{ $s['password'] }}</span></td>
                            <td class="center"><span class="badge-active">ACTIVE</span></td>
                            <td><div class="signature-line"></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
