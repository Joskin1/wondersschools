<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal Credentials & Sign-Off Sheet</title>
    <style>
        @page {
            margin: 7mm 8mm 8mm 8mm;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 7.5pt;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.15;
        }
        .header {
            text-align: center;
            border-bottom: 1.5px solid #1e3a8a;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }
        .school-name {
            font-size: 13pt;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .doc-title {
            font-size: 9pt;
            font-weight: 700;
            color: #0f172a;
            margin: 2px 0 1px 0;
            text-transform: uppercase;
        }
        .sub-header {
            font-size: 7pt;
            color: #64748b;
        }
        .instructions-box {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-left: 3px solid #2563eb;
            padding: 3px 6px;
            margin-bottom: 5px;
            font-size: 6.8pt;
        }
        .instructions-box strong {
            color: #1e3a8a;
        }
        .class-section {
            margin-bottom: 0px;
        }
        .class-title {
            font-size: 8.5pt;
            font-weight: bold;
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 3px 6px;
            margin: 0 0 3px 0;
            border-radius: 2px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        th {
            background-color: #e2e8f0;
            color: #1e293b;
            font-weight: bold;
            font-size: 6.8pt;
            padding: 2.5px 3px;
            border: 1px solid #94a3b8;
            text-transform: uppercase;
            text-align: left;
        }
        th.center, td.center {
            text-align: center;
        }
        td {
            padding: 2.2px 3px;
            border: 1px solid #cbd5e1;
            font-size: 7.2pt;
            vertical-align: middle;
        }
        tr:nth-child(even) {
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
            font-size: 7.2pt;
        }
        .pwd {
            font-family: monospace;
            font-weight: bold;
            color: #047857;
            background-color: #ecfdf5;
            padding: 0px 2px;
            border-radius: 2px;
            border: 0.5px dashed #a7f3d0;
            font-size: 7.2pt;
        }
        .badge-active {
            background-color: #dcfce7;
            color: #15803d;
            font-weight: bold;
            font-size: 6pt;
            padding: 0.5px 3px;
            border-radius: 2px;
            text-align: center;
            display: inline-block;
        }
        .signature-line {
            border-bottom: 1px dotted #94a3b8;
            height: 11px;
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
            height: 10px;
            font-size: 6.5pt;
            color: #94a3b8;
            text-align: center;
            border-top: 0.5px solid #e2e8f0;
            padding-top: 1px;
        }
    </style>
</head>
<body>
    <div class="footer">
        {{ $schoolName }} • Student Portal Credentials Roster • Session: {{ $sessionName }} • Portal: {{ $portalUrl }}
    </div>

    @foreach($studentsByClass as $class => $students)
        @if(!$loop->first)
            <div class="page-break"></div>
        @endif

        <div class="header">
            <div class="school-name">{{ $schoolName }}</div>
            <div class="doc-title">Student Portal Credentials & Sign-Off Sheet</div>
            <div class="sub-header">Academic Session: {{ $sessionName }} | Class: {{ $class }} ({{ count($students) }} Students) | Generated: {{ $generatedDate }}</div>
        </div>

        <div class="instructions-box">
            <strong>Portal URL:</strong> {{ $portalUrl }} &bull;
            <strong>Login:</strong> Use Admission Number (or email) + Password &bull;
            <strong>Action:</strong> Check details and sign upon receiving credentials.
        </div>

        <div class="class-section">
            <table>
                <thead>
                    <tr>
                        <th class="center" style="width: 4%;">S/N</th>
                        <th style="width: 34%;">Student Full Name</th>
                        <th class="center" style="width: 20%;">Admission No. (Username)</th>
                        <th class="center" style="width: 17%;">Password</th>
                        <th class="center" style="width: 8%;">Portal</th>
                        <th class="center" style="width: 17%;">Student Signature</th>
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
