<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $classroom->name ?? 'Class' }} - Mid-Term Progress Slips</title>
    @include('results.partials.midterm-slip-styles', [
        'brandColor' => $settings['primary_color'] ?? config('app.tenant_primary_color', '#1e3a8a'),
    ])
</head>
<body>
    @foreach ($studentsData as $studentData)
        <div class="student-page-wrapper" style="{{ ! $loop->last ? 'page-break-after: always;' : '' }}">
            @include('results.partials.midterm-slip-content', ['data' => $studentData])
        </div>
    @endforeach
</body>
</html>
