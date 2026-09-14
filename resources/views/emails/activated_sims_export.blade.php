<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activated SIMs Export - SmartSIM</title>
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e293b 0%, #42517c 100%);
            padding: 32px;
            text-align: center;
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
                <span style="color: #ffffff; font-size: 22px; font-weight: 800; letter-spacing: 1px;">SmartSIM</span>
            </div>
            <div class="content">
                <h1 class="title">Activated SIMs Export</h1>
                <p class="text">Hello,</p>
                <p class="text">Attached is the CSV export of every SIM number currently activated on SmartSIM, generated on {{ $generatedAt->format('F d, Y \a\t h:i A') }}.</p>

                <div class="stats">
                    <p class="stats-number">{{ number_format($count) }}</p>
                    <p class="stats-label">Activated SIM Numbers</p>
                </div>

                <p class="text" style="font-size: 13px; color: #64748b;">
                    This is an automated report. Change the recipient or turn it off from Admin &rsaquo; SIMs Operations.
                </p>
            </div>
            <div class="footer">
                <p class="footer-text">© {{ date('Y') }} SmartSIM. All rights reserved.</p>
                <p class="footer-text">Empowering Businesses Through Smart Connectivity.</p>
            </div>
        </div>
    </div>
</body>
</html>
