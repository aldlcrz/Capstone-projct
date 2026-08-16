<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'LumBarong Notification' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
        }
        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #f1f5f9;
        }
        .header {
            background: linear-gradient(135deg, #8B0000 0%, #C0420A 100%);
            padding: 28px 32px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .header p {
            color: rgba(255, 255, 255, 0.85);
            margin: 4px 0 0 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }
        .body {
            padding: 36px 32px;
            line-height: 1.6;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
        }
        .code-box {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }
        .code-number {
            font-size: 32px;
            font-weight: 900;
            letter-spacing: 6px;
            color: #C0420A;
            font-family: monospace;
        }
        .code-expiry {
            font-size: 12px;
            color: #64748b;
            margin-top: 6px;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .badge-info { background: #e0f2fe; color: #0369a1; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .badge-success { background: #dcfce7; color: #15803d; }

        .button-wrapper {
            text-align: center;
            margin: 28px 0;
        }
        .btn-primary {
            display: inline-block;
            background-color: #C0420A;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(192, 66, 10, 0.25);
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #64748b;
        }
        .footer-no-reply {
            background-color: #f1f5f9;
            color: #475569;
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-block;
            font-weight: 600;
            font-size: 11px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="margin-bottom: 10px;">
                <img src="{{ config('app.url') }}/images/logo-icon.png" alt="LumBarong" width="52" height="52" style="border-radius: 50%; display: inline-block; vertical-align: middle; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
            </div>
            <h1>LumBarong</h1>
            <p>Filipino Heritage, Modern Elegance</p>
        </div>
        <div class="body">
            @yield('content')
        </div>
        <div class="footer">
            <div class="footer-no-reply">
                ⛔ Automated Notification — Do Not Reply Directly
            </div>
            <p>This is an automated system email from LumBarong. Please do not reply directly to this email address.</p>
            <p>&copy; {{ date('Y') }} LumBarong. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
