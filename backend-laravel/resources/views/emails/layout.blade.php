<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'LumBarong Notification' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
        }
        .container {
            max-width: 580px;
            margin: 28px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            border: 1px solid #f1f5f9;
        }
        .header {
            background-color: #8B0000;
            background: linear-gradient(135deg, #8B0000 0%, #C0420A 100%);
            padding: 28px 32px 26px 32px;
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
            color: rgba(255, 255, 255, 0.9);
            margin: 5px 0 0 0;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
        }
        .body {
            padding: 36px 32px;
            line-height: 1.6;
            color: #334155;
            font-size: 14px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
        }
        .code-box {
            background: #FFF7ED;
            border: 2px dashed #FB923C;
            border-radius: 12px;
            padding: 22px 20px;
            text-align: center;
            margin: 24px 0;
        }
        .code-number {
            font-size: 38px;
            font-weight: 900;
            letter-spacing: 8px;
            color: #C0420A;
            font-family: 'Courier New', Courier, monospace;
            display: inline-block;
        }
        .code-expiry {
            font-size: 12px;
            color: #9A3412;
            margin-top: 8px;
            font-weight: 600;
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
            padding: 6px 14px;
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
            <!-- Bulletproof brand seal that renders 100% reliably in Spam, Dark Mode, or with images disabled -->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto 12px auto;">
                <tr>
                    <td align="center" style="width: 48px; height: 48px; background-color: rgba(255, 255, 255, 0.16); border: 2px solid rgba(255, 235, 205, 0.55); border-radius: 50%; text-align: center; vertical-align: middle;">
                        <span style="font-family: 'Georgia', 'Times New Roman', serif; font-size: 19px; font-weight: bold; color: #FFF8E7; letter-spacing: 1.5px; line-height: 48px; display: inline-block;">LB</span>
                    </td>
                </tr>
            </table>
            <h1>LumBarong</h1>
            <p>Filipino Heritage, Modern Elegance</p>
        </div>
        <div class="body">
            @yield('content')
        </div>
        <div class="footer">
            <div class="footer-no-reply">
                Automated Notification — Do Not Reply Directly
            </div>
            <p>This is an automated notification from LumBarong. No direct email replies are accepted.</p>
            <p>&copy; {{ date('Y') }} LumBarong. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
