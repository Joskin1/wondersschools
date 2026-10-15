<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Result Checker PINs - {{ $batch->batch_code }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 5mm 5mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        body {
            background-color: #ffffff;
            color: #1e293b;
        }
        .sheet {
            width: 100%;
            page-break-after: always;
        }
        .sheet:last-child {
            page-break-after: avoid;
        }
        table.pins-grid {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        table.pins-grid td {
            width: 25%;
            height: 27.8mm;
            max-height: 27.8mm;
            border: 0.8pt dashed #94a3b8;
            padding: 1.2mm 1.5mm;
            vertical-align: middle;
            text-align: center;
            box-sizing: border-box;
        }
        .school-name {
            font-size: 6.2pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.15pt;
            line-height: 1.15;
            margin-bottom: 0.8mm;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: normal;
        }
        .meta-line {
            font-size: 5.2pt;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.2pt;
            margin-bottom: 0.9mm;
        }
        .pin-box {
            background: #f1f5f9;
            border: 0.8pt solid #cbd5e1;
            border-radius: 3pt;
            padding: 0.9mm 1.8mm;
            display: inline-block;
            margin: 0 auto;
        }
        .pin-label {
            font-size: 5pt;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.4pt;
            margin-right: 2pt;
        }
        .pin-number {
            font-size: 9.8pt;
            font-weight: 800;
            color: #09090b;
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 1.2pt;
        }
        .footer-line {
            margin-top: 0.8mm;
            font-size: 4.8pt;
            color: #94a3b8;
            letter-spacing: 0.2pt;
        }
        .serial-no {
            font-weight: 600;
            color: #64748b;
        }
    </style>
</head>
<body>
    @foreach ($pages as $cardsOnPage)
        <div class="sheet">
            <table class="pins-grid">
                @foreach (array_chunk($cardsOnPage, 4) as $row)
                    <tr>
                        @foreach ($row as $card)
                            <td>
                                <div class="school-name">{{ $schoolName }}</div>
                                <div class="meta-line">{{ $sessionName }} &bull; {{ $termName }}</div>
                                <div class="pin-box">
                                    <span class="pin-label">PIN:</span>
                                    <span class="pin-number">{{ $card['pin'] }}</span>
                                </div>
                                <div class="footer-line">
                                    <span class="serial-no">{{ $card['serial_number'] }}</span>
                                    <span>&bull; Result Checker</span>
                                </div>
                            </td>
                        @endforeach
                        {{-- Fill remaining cells if last row has fewer than 4 items --}}
                        @for ($i = count($row); $i < 4; $i++)
                            <td style="border: 0.8pt dashed #e2e8f0;"></td>
                        @endfor
                    </tr>
                @endforeach
            </table>
        </div>
    @endforeach
</body>
</html>
