<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'LumBarong Notification' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #FAF8F5;
            color: #1E1915;
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
        .canvas {
            background-color: #FAF8F5;
            padding: 32px 14px;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background-color: #FDFBF7;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.06);
            border: 1px solid #EAE2D2;
        }
        .header-section {
            padding: 26px 26px 0 26px;
        }
        .header-title {
            font-family: ui-serif, Georgia, Cambria, 'Times New Roman', serif;
            font-size: 22px;
            font-weight: 700;
            color: #1E1915;
            letter-spacing: -0.01em;
            line-height: 1.2;
            margin: 0;
        }
        .header-subtitle {
            font-size: 12.5px;
            color: #78716C;
            margin: 3px 0 0 0;
        }
        .header-badge {
            background-color: #FAF6EE;
            border: 1px solid #E2D9C8;
            color: #78716C;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 12px;
            border-radius: 12px;
            display: inline-block;
            text-decoration: none;
        }
        .star-divider {
            width: 100%;
            margin: 18px 0 20px 0;
        }
        .star-divider-line {
            border-top: 1px solid #EAE1D0;
        }
        .star-divider-center {
            padding: 0 10px;
            color: #C49520;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
            text-align: center;
        }
        .body-section {
            padding: 0 24px 26px 24px;
        }
        .hero-card {
            background-color: #FFFFFF;
            border: 1px solid #ECE3D2;
            border-radius: 20px;
            padding: 24px 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            color: #1E1915;
        }
        .verified-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            background-color: #FAF5EA;
            border: 1px solid #E6D8BA;
            color: #996515;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 14px;
        }
        .greeting {
            font-family: ui-serif, Georgia, Cambria, 'Times New Roman', serif;
            font-size: 22px;
            font-weight: 700;
            color: #1E1915;
            letter-spacing: -0.01em;
            margin: 0 0 12px 0;
            line-height: 1.25;
        }
        .body-text {
            font-size: 14px;
            line-height: 1.7;
            color: #574F47;
            margin: 0 0 18px 0;
        }
        .code-vault, .code-box {
            background-color: #FAF6EE;
            background: linear-gradient(135deg, #FAF6EE 0%, #F2E9DA 100%);
            border: 1px solid #DECBB1;
            border-radius: 18px;
            padding: 24px 16px 20px 16px;
            text-align: center;
            margin: 22px 0;
            box-shadow: 0 4px 14px rgba(153, 101, 21, 0.05);
        }
        .code-vault-label {
            font-size: 10.5px;
            font-weight: 800;
            color: #996515;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .code-number {
            font-size: 34px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #1E1915;
            font-family: ui-serif, Georgia, monospace;
            display: inline-block;
        }
        .code-expiry {
            font-size: 11.5px;
            color: #996515;
            margin-top: 14px;
            font-weight: 700;
        }
        .digit-tile {
            width: 44px;
            height: 54px;
            background-color: #FFFFFF;
            border: 1.5px solid #DFCDB0;
            border-radius: 12px;
            font-family: ui-serif, Georgia, Cambria, serif;
            font-size: 26px;
            font-weight: 800;
            color: #1E1915;
            text-align: center;
            vertical-align: middle;
            box-shadow: 0 2px 6px rgba(153, 101, 21, 0.08);
        }
        .digit-cell {
            padding: 0 4px;
        }
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        .badge-info { background: #FAF5EA; color: #996515; border: 1px solid #E6D8BA; }
        .badge-warning { background: #FEF9EE; color: #92580C; border: 1px solid #F8E5BA; }
        .badge-danger { background: #FDF2F2; color: #991B1B; border: 1px solid #F8CECE; }
        .badge-success { background: #F0FDF4; color: #166534; border: 1px solid #C5EED0; }

        .security-notice-box {
            background-color: #FAF8F5;
            border-left: 3px solid #C49520;
            border-top: 1px solid #ECE3D2;
            border-right: 1px solid #ECE3D2;
            border-bottom: 1px solid #ECE3D2;
            border-radius: 14px;
            padding: 14px 18px;
            font-size: 12.5px;
            color: #6E5F52;
            line-height: 1.6;
            margin-top: 20px;
        }

        .footer-section {
            background-color: #FAF8F5;
            padding: 22px 24px;
            text-align: center;
            border-top: 1px solid #EAE1D0;
            font-size: 12px;
            color: #78716C;
        }
        .footer-pill {
            background-color: #FAF5EA;
            color: #996515;
            border: 1px solid #E6D8BA;
            padding: 4px 12px;
            border-radius: 9999px;
            display: inline-block;
            font-weight: 800;
            font-size: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        @media only screen and (max-width: 540px) {
            .canvas {
                padding: 12px 8px !important;
            }
            .container {
                width: 100% !important;
                border-radius: 18px !important;
            }
            .header-section {
                padding: 20px 16px 0 16px !important;
            }
            .header-title {
                font-size: 20px !important;
            }
            .body-section {
                padding: 0 14px 20px 14px !important;
            }
            .hero-card {
                padding: 18px 16px !important;
                border-radius: 16px !important;
            }
            .code-vault, .code-box {
                padding: 18px 6px 16px 6px !important;
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
            .footer-section {
                padding: 18px 16px !important;
            }
        }
    </style>
</head>
<body>
    <div class="canvas">
        <div class="container">
            {{-- Top Header matching My Profile & Account theme --}}
            <div class="header-section">
                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="left" style="vertical-align: middle;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    {{-- Heraldic Laurel Wreath Medallion in Gold (#C49520) --}}
                                    <td style="width: 44px; height: 44px; vertical-align: middle; padding-right: 12px;">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" style="width: 44px; height: 44px; background-color: #FAF5EA; border: 1.5px solid #C49520; border-radius: 50%; text-align: center; vertical-align: middle; box-shadow: 0 2px 8px rgba(196, 149, 32, 0.15);">
                                                    <span style="font-family: ui-serif, Georgia, serif; font-size: 18px; font-weight: bold; color: #996515; line-height: 44px; display: inline-block;">LB</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <h1 class="header-title">LumBarong</h1>
                                        <p class="header-subtitle">Filipino Heritage &middot; Authentic Artisan Marketplace</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td align="right" style="vertical-align: middle;">
                            <span class="header-badge">✦ Security</span>
                        </td>
                    </tr>
                </table>

                {{-- Star Divider matching the Profile theme --}}
                <table role="presentation" class="star-divider" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="star-divider-line" style="width: 46%;"></td>
                        <td class="star-divider-center" style="width: 8%;">✦</td>
                        <td class="star-divider-line" style="width: 46%;"></td>
                    </tr>
                </table>
            </div>

            {{-- Main Body Card Content --}}
            <div class="body-section">
                <div class="hero-card">
                    @yield('content')
                </div>
            </div>

            {{-- Footer matching the Profile & Account theme --}}
            <div class="footer-section">
                <div>
                    <span class="footer-pill">
                        🛡️ Automated Security Notification &bull; Do Not Reply
                    </span>
                </div>
                <p style="margin: 6px 0 3px 0; font-size: 12px; color: #78716C;">
                    Handcrafted with Filipino Pride &bull; Lumban, Laguna, Philippines
                </p>
                <p style="margin: 0; font-size: 11px; color: #A89F95;">
                    &copy; {{ date('Y') }} LumBarong Inc. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
