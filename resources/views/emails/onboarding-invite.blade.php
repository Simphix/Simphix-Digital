<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Invited - SimPhix</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Outfit', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f8fafc;
            padding: 40px 20px;
            box-sizing: border-box;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            border-top: 4px solid #0056D2;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .header {
            background-color: #ffffff;
            padding: 28px 24px 22px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }
        .header img {
            height: 40px;
            max-height: 40px;
            width: auto;
        }
        .content {
            padding: 40px 32px;
            text-align: center;
        }
        .title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 12px 0;
        }
        .text {
            font-size: 15px;
            line-height: 24px;
            color: #64748b;
            margin: 0 0 28px 0;
        }
        .role-badge {
            display: inline-block;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 6px 16px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #42517c;
            margin: 0 0 24px 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #42517c;
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            padding: 14px 32px;
            border-radius: 10px;
        }
        .expiry-text {
            font-size: 13px;
            color: #ef4444;
            font-weight: 500;
            margin: 16px 0 0 0;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #f1f5f9;
        }
        .footer-text {
            font-size: 12px;
            color: #94a3b8;
            margin: 0 0 8px 0;
            line-height: 18px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <a href="{{ config('app.url') }}" target="_blank" style="text-decoration: none; display: inline-block;">
                    <img src="{{ rtrim(config('app.url'), '/') . '/assets/images/logo/logo1.png' }}" alt="SimPhix" height="40" style="display: block; margin: 0 auto; height: 40px; max-height: 40px; width: auto; border: 0; outline: none; font-family: 'Outfit', -apple-system, sans-serif; font-size: 22px; font-weight: 800; color: #0056D2;" />
                </a>
            </div>
            <div class="content">
                <h1 class="title">You've Been Invited</h1>
                <p class="text">
                    {{ $onboarder->name }} has invited you to join SimPhix as a
                </p>
                <div class="role-badge">{{ str_replace('_', ' ', $invitee->role) }}</div>
                <p class="text">Click below to set your password and activate your account.</p>

                <a href="{{ $acceptUrl }}" class="cta-button">Accept Invite &amp; Set Password</a>

                <p class="expiry-text">This invite link expires in 3 days.</p>
                <p class="text" style="font-size: 14px; margin-top: 24px; margin-bottom: 0;">If you weren't expecting this invite, you can safely ignore this email.</p>
            </div>
            <div class="footer">
                <p class="footer-text">© {{ date('Y') }} SimPhix. All rights reserved.</p>
                <p class="footer-text">Empowering Businesses Through Smart Connectivity.</p>
            </div>
        </div>
    </div>
</body>
</html>
