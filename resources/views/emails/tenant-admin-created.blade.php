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
        .btn { display: inline-block; background: #f59e0b; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 16px; }
        .footer { margin-top: 32px; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome to Wonders!</h2>
        <p>Hi {{ $user->name }},</p>
        <p>Your school admin account has been created. Use the credentials below to log in and manage your school.</p>

        <div class="credentials">
            <p><strong>Login URL:</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Password:</strong> {{ $password }}</p>
        </div>

        <a href="{{ $loginUrl }}" class="btn">Log in to your school panel</a>

        <p style="margin-top: 24px;">Please change your password after your first login.</p>

        <div class="footer">
            <p>This email was sent by the Wonders platform. If you did not expect this, please contact your system administrator.</p>
        </div>
    </div>
</body>
</html>
