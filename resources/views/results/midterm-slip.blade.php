<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['report_title'] ?? 'Mid-Term Progress Report' }}</title>
    @include('results.partials.midterm-slip-styles', [
        'brandColor' => $data['settings']['primary_color'] ?? config('app.tenant_primary_color', '#1e3a8a'),
    ])
</head>
<body>
    @include('results.partials.midterm-slip-content', ['data' => $data])
</body>
</html>
