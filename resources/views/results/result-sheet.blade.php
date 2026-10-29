<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Academic Report Sheet</title>
    @include('results.partials.report-card-styles', [
        'marginCss'  => $data['margin_css'] ?? '12mm 14mm 12mm 14mm',
        'brandColor' => $data['settings']['primary_color'] ?? config('app.tenant_primary_color', '#1e3a8a'),
    ])
</head>
<body>
    @include('results.partials.report-card-content', ['data' => $data])
</body>
</html>
