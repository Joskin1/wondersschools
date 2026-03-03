<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 40px auto; padding: 32px; border: 1px solid #e5e7eb; border-radius: 8px; }
        h2 { color: #1f2937; }
        .credentials { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin: 20px 0; }
        .credentials p { margin: 4px 0; }
        .btn { 
            display: inline-block; 
            background: {{ config('app.tenant_primary_color', '#16a34a') }}; 
            color: #fff; 
            padding: 12px 24px; 
            text-decoration: none; 
            border-radius: 6px; 
            font-weight: bold; 
            margin-top: 16px; 
        }
        .footer { margin-top: 32px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">

        {{-- Logo --}}
        <div style="margin-bottom: 20px;">
            @if(\App\Services\FrontendLibrary::getSetting('school_logo'))
                <img 
                    src="{{ Storage::url(\App\Services\FrontendLibrary::getSetting('school_logo')) }}" 
                    alt="{{ \App\Services\FrontendLibrary::getSetting('school_name', 'School') }} Logo" 
                    style="height:60px;"
                >
            @else
                <h2>{{ \App\Services\FrontendLibrary::getSetting('school_name', 'School') }}</h2>
            @endif
        </div>

        <p>Hi {{ $user->name }},</p>

        <p>
            Your administrator account has been created for 
            <strong>{{ \App\Services\FrontendLibrary::getSetting('school_name', 'your school') }}</strong>.
            Use the credentials below to log in.
        </p>

        <div class="credentials">
            <p><strong>Login URL:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Password:</strong> {{ $password }}</p>
        </div>

        <a href="{{ $loginUrl }}" class="btn">
            Log in to {{ \App\Services\FrontendLibrary::getSetting('school_name', 'your school') }}
        </a>

        <p style="margin-top: 24px;">
            Please change your password immediately after your first login.
        </p>

        <div class="footer">
            <p>
                © {{ date('Y') }} {{ \App\Services\FrontendLibrary::getSetting('school_name', 'School') }}.
                {{ \App\Services\FrontendLibrary::getSetting('school_email', 'Contact your administrator if needed.') }}
            </p>
        </div>

    </div>
</body>
</html>