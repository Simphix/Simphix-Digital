<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIM Activation Details</title>
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
            padding: 36px 32px;
        }
        .title {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 12px 0;
            text-align: center;
        }
        .subtitle {
            font-size: 14px;
            color: #64748b;
            margin: 0 0 24px 0;
            text-align: center;
            line-height: 20px;
        }
        .sim-card {
            background: linear-gradient(135deg, #0056D2 0%, #003e99 100%);
            color: #ffffff;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 6px 16px rgba(0, 86, 210, 0.2);
        }
        .sim-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .sim-provider {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
        }
        .sim-status {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            background: #10b981;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
        }
        .sim-number {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1.5px;
            font-family: 'Courier New', Courier, monospace;
            margin-bottom: 8px;
        }
        .sim-category {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .details-table td {
            padding: 12px 16px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .details-label {
            color: #64748b;
            font-weight: 600;
            width: 38%;
        }
        .details-value {
            color: #1e293b;
            font-weight: 700;
        }
        .tip-box {
            background-color: #eff6ff;
            border-left: 4px solid #0056D2;
            padding: 14px 16px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 13px;
            line-height: 20px;
            color: #1e40af;
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
            margin: 0 0 6px 0;
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
                <h1 class="title">SIM Activated Successfully!</h1>
                <p class="subtitle">Hello <strong>{{ $customer?->full_name ?? 'Valued Customer' }}</strong>, your SIM card has been activated and is ready for use.</p>

                <!-- SIM Digital Card -->
                <div class="sim-card">
                    <div class="sim-card-top">
                        <span class="sim-provider">{{ strtoupper($sim->provider) }}</span>
                        <span class="sim-status">ACTIVATED</span>
                    </div>
                    <div class="sim-number">{{ $sim->number }}</div>
                    <div class="sim-category">{{ $sim->category }} Card</div>
                </div>

                <!-- Details Summary -->
                <table class="details-table">
                    <tr>
                        <td class="details-label">Customer Name</td>
                        <td class="details-value">{{ $customer?->full_name ?? 'N/A' }}</td>
                    </tr>
                    @if ($customer?->phone_number)
                    <tr>
                        <td class="details-label">Contact Phone</td>
                        <td class="details-value">{{ $customer->phone_number }}</td>
                    </tr>
                    @endif
                    @if ($customer?->email)
                    <tr>
                        <td class="details-label">Email Address</td>
                        <td class="details-value">{{ $customer->email }}</td>
                    </tr>
                    @endif
                    @if ($customer?->address)
                    <tr>
                        <td class="details-label">Registered Address</td>
                        <td class="details-value">{{ $customer->address }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="details-label">Network</td>
                        <td class="details-value">{{ strtoupper($sim->provider) }}</td>
                    </tr>
                    <tr>
                        <td class="details-label">SIM Category</td>
                        <td class="details-value">{{ $sim->category }}</td>
                    </tr>
                    <tr>
                        <td class="details-label">Date Activated</td>
                        <td class="details-value">{{ $sim->updated_at ? $sim->updated_at->format('M d, Y · h:i A') : now()->format('M d, Y · h:i A') }}</td>
                    </tr>
                    @if ($sender)
                    <tr>
                        <td class="details-label">Fulfilled By</td>
                        <td class="details-value">{{ $sender->first_name }} {{ $sender->last_name }} ({{ ucwords(str_replace('_', ' ', $sender->role)) }})</td>
                    </tr>
                    @endif
                </table>

                <div class="tip-box">
                    <strong>Quick Tip:</strong> Insert the SIM card into your device and restart it. Once the network signal registers, your device is connected and ready to transmit data.
                </div>
            </div>

            <div class="footer">
                <p class="footer-text">&copy; {{ date('Y') }} SimPhix. All rights reserved.</p>
                <p class="footer-text">Empowering Businesses Through Smart Connectivity.</p>
                <p class="footer-text" style="font-size: 11px; margin-top: 6px;">Need assistance? Contact your partner or reach our support team.</p>
            </div>
        </div>
    </div>
</body>
</html>
