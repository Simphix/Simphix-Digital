<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activated SIMs Export - SimPhix</title>
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
        }
        .title {
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 16px 0;
            text-align: center;
        }
        .text {
            font-size: 15px;
            line-height: 24px;
            color: #475569;
            margin: 0 0 20px 0;
        }
        .stats {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin: 28px 0;
            text-align: center;
        }
        .stats-number {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
        }
        .stats-label {
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 4px 0 0 0;
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
                <h1 class="title">{{ isset($since) && $since ? 'Daily Activated SIMs Export' : 'Activated SIMs Export' }}</h1>
                <p class="text">Hello,</p>
                @if (isset($since) && $since)
                    @php
                        $lagosSince = $since->copy()->setTimezone('Africa/Lagos');
                        $lagosUntil = isset($until) && $until ? $until->copy()->setTimezone('Africa/Lagos') : $generatedAt->copy()->setTimezone('Africa/Lagos');
                    @endphp
                    @if (isset($reportDate) && $reportDate)
                        <p class="text">Attached is the daily CSV export of SIM numbers activated on SimPhix for <strong>{{ $reportDate->format('F d, Y') }}</strong> (full calendar day, from {{ $lagosSince->format('h:i A') }} to {{ $lagosUntil->format('h:i A') }} WAT).</p>
                    @else
                        <p class="text">Attached is the daily CSV export of SIM numbers activated on SimPhix (from {{ $lagosSince->format('F d, Y \a\t h:i A') }} to {{ $lagosUntil->format('F d, Y \a\t h:i A') }} WAT).</p>
                    @endif
                @else
                    <p class="text">Attached is the CSV export of every SIM number currently activated on SimPhix, generated on {{ $generatedAt->copy()->setTimezone('Africa/Lagos')->format('F d, Y \a\t h:i A') }} WAT.</p>
                @endif

                <div class="stats">
                    <p class="stats-number">{{ number_format($count) }}</p>
                    <p class="stats-label">{{ isset($since) && $since ? (isset($reportDate) && $reportDate ? 'SIM Numbers Activated on ' . $reportDate->format('M d, Y') : 'SIM Numbers Activated') : 'Activated SIM Numbers' }}</p>
                </div>

                <p class="text" style="font-size: 13px; color: #64748b;">
                    This is an automated report. Change the recipient or turn it off from Admin &rsaquo; SIMs Operations.
                </p>
            </div>
            <div class="footer">
                <p class="footer-text">© {{ date('Y') }} SimPhix. All rights reserved.</p>
                <p class="footer-text">Empowering Businesses Through Smart Connectivity.</p>
            </div>
        </div>
    </div>
</body>
</html>
