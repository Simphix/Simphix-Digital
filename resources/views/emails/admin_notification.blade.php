<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Desk Alert - SimPhix</title>
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
            font-size: 22px;
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
            font-size: 13px;
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
        .message-bubble {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
            margin-top: 10px;
            font-size: 13px;
            line-height: 20px;
            color: #334155;
            white-space: pre-wrap;
        }
        .btn-container {
            text-align: center;
            margin: 32px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #1e293b 0%, #42517c 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 30px;
            font-weight: 600;
            font-size: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(30, 41, 59, 0.2);
            transition: all 0.2s ease;
        }
        .btn:hover {
            box-shadow: 0 6px 14px rgba(30, 41, 59, 0.3);
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
                <div style="margin-top: 8px;">
                    <span style="display: inline-block; background-color: #f1f5f9; color: #475569; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; padding: 3px 10px; border-radius: 9999px;">Support Desk</span>
                </div>
            </div>
            <div class="content">
                <h1 class="title">
                    @if($action === 'created')
                        New Ticket Submitted: #{{ $ticket->id }}
                    @else
                        User Replied on Ticket: #{{ $ticket->id }}
                    @endif
                </h1>
                
                <p class="text">
                    A support action has been recorded in the SimPhix dashboard. Please find the details below:
                </p>
                
                <div class="ticket-details">
                    <h3 class="details-title">Details</h3>
                    <div class="details-row">
                        <span class="details-label">User:</span>
                        <span class="details-val">{{ $ticket->user->first_name }} {{ $ticket->user->last_name }} ({{ $ticket->user->email }})</span>
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
                    <div class="details-row">
                        <span class="details-label">Status:</span>
                        <span class="details-val" style="text-transform: capitalize;">{{ $ticket->status }}</span>
                    </div>
                    
                    <div class="details-label" style="margin-top: 16px; margin-bottom: 4px;">Latest Message:</div>
                    <div class="message-bubble">{{ $msg->message }}</div>
                </div>

                <div class="btn-container">
                    <a href="{{ route('admin.manage.support.show', $ticket) }}" class="btn">Open In Admin Panel</a>
                </div>
            </div>
            <div class="footer">
                <p class="footer-text">© {{ date('Y') }} SimPhix Support Desk. Internal Use Only.</p>
                <p class="footer-text">Empowering Businesses Through Smart Connectivity.</p>
            </div>
        </div>
    </div>
</body>
</html>
