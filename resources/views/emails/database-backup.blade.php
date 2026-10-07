<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup Snapshot</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 24px 12px;
            line-height: 1.5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 28px 24px;
            color: #ffffff;
        }
        .badge {
            display: inline-block;
            background-color: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.3);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 4px 10px;
            border-radius: 9999px;
            margin-bottom: 12px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 13px;
            color: #94a3b8;
        }
        .content {
            padding: 24px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .info-grid tr {
            border-bottom: 1px solid #f1f5f9;
        }
        .info-grid tr:last-child {
            border-bottom: none;
        }
        .info-grid td {
            padding: 12px 16px;
            font-size: 13px;
        }
        .info-label {
            font-weight: 600;
            color: #64748b;
            width: 38%;
            background-color: #f8fafc;
        }
        .info-value {
            font-weight: 500;
            color: #0f172a;
        }
        .highlight-value {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            background-color: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            color: #0f172a;
        }
        .restore-box {
            background-color: #0f172a;
            color: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
            font-size: 12px;
        }
        .restore-title {
            color: #38bdf8;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .restore-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #a5f3fc;
            background-color: rgba(0, 0, 0, 0.3);
            padding: 8px 10px;
            border-radius: 6px;
            word-break: break-all;
            margin-top: 4px;
            display: block;
        }
        .footer {
            padding: 18px 24px;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            font-size: 11px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge">Disaster Recovery Archive</span>
            <h1>{{ $tenantName }}</h1>
            <p>Automated database snapshot generated on {{ $generatedAt }}</p>
        </div>

        <div class="content">
            <p style="font-size: 14px; margin-top: 0; margin-bottom: 18px;">
                An automated, compressed database backup has been created and attached to this email. In the event of a server incident or data corruption, this archive can be used for a full point-in-time recovery.
            </p>

            <table class="info-grid">
                <tr>
                    <td class="info-label">Scope / School</td>
                    <td class="info-value"><strong>{{ $tenantName }}</strong></td>
                </tr>
                <tr>
                    <td class="info-label">Database Name</td>
                    <td class="info-value"><span class="highlight-value">{{ $databaseName }}</span></td>
                </tr>
                <tr>
                    <td class="info-label">Database Engine</td>
                    <td class="info-value">{{ $driver }}</td>
                </tr>
                <tr>
                    <td class="info-label">Archive Size</td>
                    <td class="info-value"><strong style="color: #059669;">{{ $summarySize }}</strong> (Gzip compressed)</td>
                </tr>
                <tr>
                    <td class="info-label">Generated At</td>
                    <td class="info-value">{{ $generatedAt }}</td>
                </tr>
            </table>

            @if(!empty($tableSummary))
                <div style="margin-top: 16px; margin-bottom: 20px;">
                    <div style="font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
                        Key Table Record Counts
                    </div>
                    <div style="font-size: 12px; color: #334155; background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        @foreach($tableSummary as $tableName => $count)
                            <span style="display: inline-block; margin-right: 14px; margin-bottom: 4px;">
                                <strong>{{ $tableName }}:</strong> {{ number_format($count) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="restore-box">
                <div class="restore-title">
                    Emergency Restore Guide (PostgreSQL)
                </div>
                <div style="color: #94a3b8; font-size: 11px; margin-bottom: 6px;">
                    To restore this backup into your database instance:
                </div>
                <code class="restore-code">
                    gunzip -c {{ $filesToAttach[0]['name'] ?? 'backup.sql.gz' }} | psql -h 127.0.0.1 -U wonder_tenant_user -d {{ $databaseName }}
                </code>
            </div>
        </div>

        <div class="footer">
            Sent automatically by Wonders Automated Backup System • Keep this file secure as it contains encrypted school records.
        </div>
    </div>
</body>
</html>
