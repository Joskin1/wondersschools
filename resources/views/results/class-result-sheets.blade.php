<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $classroom->name ?? 'Class' }} - Terminal Report Sheets</title>
    @include('results.partials.report-card-styles', [
        'marginCss'  => $margin_css ?? '12mm 14mm 12mm 14mm',
        'brandColor' => $settings['primary_color'] ?? config('app.tenant_primary_color', '#1e3a8a'),
    ])
</head>
<body>
    @foreach ($studentsData as $studentData)
        <div class="student-page-wrapper" style="{{ ! $loop->last ? 'page-break-after: always;' : '' }}">
            @include('results.partials.report-card-content', ['data' => $studentData])
        </div>
    @endforeach
</body>
</html>
