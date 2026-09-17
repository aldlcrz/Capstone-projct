<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'LumBarong Notification' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #F6F2EC;
            color: #2D241E;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        .container {
            max-width: 560px;
            margin: 36px auto;
            background-color: #FFFFFF;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 16px 44px -8px rgba(30, 25, 21, 0.08), 0 0 0 1px rgba(212, 175, 55, 0.16);
            border: 1px solid #EAE3D9;
        }
        .gold-bar {
            height: 3px;
            background: linear-gradient(90deg, #9C782F 0%, #E8CA65 50%, #9C782F 100%);
            background-color: #D4AF37;
            font-size: 0;
            line-height: 0;
        }
        .header {
            background-color: #1E1915;
            background: linear-gradient(160deg, #181310 0%, #291A14 50%, #1A1512 100%);
            padding: 34px 32px 30px 32px;
            text-align: center;
            border-bottom: 2px solid #C49520;
        }
        .header h1 {
            color: #FFFFFF;
            margin: 12px 0 0 0;
            font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
            font-size: 27px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .header p {
            color: #D4AF37;
            margin: 6px 0 0 0;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }
        .body {
            padding: 38px 36px 32px 36px;
            line-height: 1.75;
            color: #483E35;
            font-size: 14.5px;
        }
        .greeting {
            font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
            font-size: 23px;
            font-weight: 700;
            color: #1E1915;
            margin-bottom: 16px;
            letter-spacing: -0.3px;
        }
        .code-vault, .code-box {
            background-color: #FAF7F2;
            background: linear-gradient(180deg, #FBF9F5 0%, #F5EFE6 100%);
            border: 1px solid #E2D6C5;
            border-radius: 16px;
            padding: 26px 16px 22px 16px;
            text-align: center;
            margin: 28px 0;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8), 0 4px 14px rgba(61,43,31,0.04);
        }
        .code-vault-label {
            font-size: 10px;
            font-weight: 800;
            color: #8C7866;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            margin-bottom: 18px;
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
        }
        .code-number {
            font-size: 36px;
            font-weight: 900;
            letter-spacing: 10px;
            color: #1E1915;
            font-family: 'Playfair Display', Georgia, monospace;
            display: inline-block;
        }
        .code-expiry {
            font-size: 11.5px;
            color: #8C590E;
            margin-top: 16px;
            font-weight: 700;
        }
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
        }
        .badge-info { background: #F0F5FA; color: #1E4E79; border: 1px solid #D6E4F0; }
        .badge-warning { background: #FEF9EE; color: #92580C; border: 1px solid #F8E5BA; }
        .badge-danger { background: #FDF2F2; color: #991B1B; border: 1px solid #F8CECE; }
        .badge-success { background: #F0FDF4; color: #166534; border: 1px solid #C5EED0; }

        .security-notice-box {
            background-color: #FAF8F5;
            border-left: 3px solid #C49520;
            border-radius: 4px 12px 12px 4px;
            padding: 14px 18px;
            font-size: 12.5px;
            color: #6E5F52;
            line-height: 1.6;
            margin-top: 24px;
        }

        .footer {
            background-color: #F8F5F0;
            padding: 28px 32px;
            text-align: center;
            border-top: 1px solid #EFEAE2;
            font-size: 12px;
            color: #8C7E72;
        }
        .footer-no-reply {
            background-color: #EDE7DE;
            color: #5A4E42;
            border: 1px solid #DFD6C9;
            padding: 5px 14px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 700;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        @media only screen and (max-width: 540px) {
            .container {
                width: 94% !important;
                margin: 16px auto !important;
                border-radius: 14px !important;
            }
            .header {
                padding: 26px 16px 22px 16px !important;
            }
            .header h1 {
                font-size: 23px !important;
                letter-spacing: 2px !important;
            }
            .body {
                padding: 28px 18px 22px 18px !important;
                font-size: 14px !important;
            }
            .code-vault, .code-box {
                padding: 20px 8px 16px 8px !important;
            }
            .digit-tile {
                width: 38px !important;
                height: 48px !important;
                font-size: 23px !important;
                border-radius: 8px !important;
            }
            .digit-cell {
                padding: 0 2px !important;
            }
            .footer {
                padding: 22px 16px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="gold-bar"></div>
        <div class="header">
            <!-- Royal Heritage Medallion Seal (Pure HTML/CSS - Never blocked by spam or image filters) -->
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                <tr>
                    <td align="center" style="width: 56px; height: 56px; background: radial-gradient(circle, #3D2B1F 0%, #1E1915 100%); border: 1.5px solid #D4AF37; border-radius: 50%; box-shadow: 0 4px 14px rgba(0,0,0,0.4), inset 0 0 0 2px rgba(212, 175, 55, 0.25); text-align: center; vertical-align: middle;">
                        <span style="font-family: 'Playfair Display', Georgia, 'Times New Roman', serif; font-size: 20px; font-weight: 700; color: #F5E6C8; letter-spacing: 2px; line-height: 56px; display: inline-block; padding-left: 2px;">LB</span>
                    </td>
                </tr>
            </table>
            <h1>LumBarong</h1>
            <p>✦ &nbsp; Filipino Heritage &middot; Modern Elegance &nbsp; ✦</p>
        </div>
        <div class="body">
            @yield('content')
        </div>
        <div class="footer">
            <div class="footer-no-reply">
                🛡️ Automated Official Notification &bull; Do Not Reply
            </div>
            <p style="margin: 0 0 6px 0; font-size: 12px; color: #877769;">This is an automated notification from LumBarong. Direct email replies are not monitored.</p>
            <p style="margin: 0; font-size: 11px; color: #A6998C;">Handcrafted with Filipino Pride &bull; Lumban, Laguna, Philippines &bull; &copy; {{ date('Y') }} LumBarong Inc. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
