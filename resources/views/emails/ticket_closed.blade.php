<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Ticket Closed - SimPhix</title>
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
            position: relative;
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
        .ticket-details {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin: 28px 0;
        }
        .details-title {
            font-weight: 700;
            color: #1e293b;
            font-size: 14px;
            margin: 0 0 12px 0;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .details-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .details-row:last-child {
            margin-bottom: 0;
        }
        .details-label {
            color: #64748b;
            font-weight: 600;
        }
        .details-val {
            color: #1e293b;
            font-weight: 700;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #42517c 0%, #55699e 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 30px;
            font-weight: 600;
            font-size: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(66, 81, 124, 0.2);
            transition: all 0.2s ease;
        }
        .btn:hover {
            box-shadow: 0 6px 14px rgba(66, 81, 124, 0.3);
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
                <h1 class="title">Support Ticket #{{ $ticket->id }} Closed</h1>
                <p class="text">Hello {{ $ticket->user->first_name }},</p>
                <p class="text">We are writing to inform you that your support ticket has been marked as <strong>Resolved and Closed</strong> by our support team. We hope we were able to successfully resolve your issue!</p>
                
                <div class="ticket-details">
                    <h3 class="details-title">Ticket Information</h3>
                    <div class="details-row">
                        <span class="details-label">Ticket ID:</span>
                        <span class="details-val">#{{ $ticket->id }}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Subject:</span>
                        <span class="details-val">{{ $ticket->subject }}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Category:</span>
                        <span class="details-val" style="text-transform: capitalize;">{{ $ticket->category }}</span>
                    </div>
                    <div class="details-row">
                        <span class="details-label">Priority:</span>
                        <span class="details-val" style="text-transform: capitalize;">{{ $ticket->priority }}</span>
                    </div>
                </div>

                <p class="text">If you still need assistance, or if the issue was not fully resolved, you can simply reply to the support thread in your dashboard. Replying to a closed ticket will automatically reopen it and notify our support agents.</p>

                <div class="btn-container">
                    <a href="{{ route('support.show', $ticket) }}" class="btn">View Support Thread</a>
                </div>
                
                <p class="text" style="font-size: 14px; text-align: center; margin-bottom: 0; color: #64748b;">
                    Thank you for choosing SimPhix. We appreciate your partnership!
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
