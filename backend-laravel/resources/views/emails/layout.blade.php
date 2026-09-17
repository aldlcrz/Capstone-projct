<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $title ?? 'LumBarong Notification' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #F8F5EE;
            color: #1E1915;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table {
            border-collapse: collapse;
        }
        .canvas {
            background-color: #F8F5EE;
            padding: 36px 14px;
        }
        .container {
            max-width: 540px;
            margin: 0 auto;
            background-color: #FDFBF7;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.04);
            border: 1px solid #EAE2D2;
            padding: 32px 28px 24px 28px;
        }
        .header-brand {
            margin: 0 0 18px 0;
        }
        .brand-title {
            font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #151F28;
            line-height: 1.2;
            margin: 0;
            text-transform: uppercase;
        }
        .brand-subtitle {
            font-size: 13px;
            color: #78716C;
            margin: 4px 0 0 0;
            font-weight: 400;
        }
        .star-divider {
            width: 100%;
            margin: 18px 0 22px 0;
        }
        .star-divider-line {
            border-top: 1px solid #EAE1D0;
        }
        .star-divider-center {
            padding: 0 10px;
            color: #C49520;
            font-size: 13px;
            font-weight: bold;
            line-height: 1;
            text-align: center;
        }
        .hero-card {
            background-color: #FFFFFF;
            border: 1px solid #ECE3D2;
            border-radius: 20px;
            padding: 28px 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            color: #1E1915;
        }
        .greeting-icon-box {
            width: 44px;
            height: 44px;
            background-color: #FAF5EA;
            border: 1px solid #EADDC8;
            border-radius: 12px;
            text-align: center;
            vertical-align: middle;
        }
        .greeting {
            font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
            font-size: 22px;
            font-weight: 700;
            color: #1E1915;
            letter-spacing: -0.01em;
            margin: 0;
            line-height: 1.25;
        }
        .body-text {
            font-size: 13.5px;
            line-height: 1.65;
            color: #5A524A;
            margin: 16px 0 20px 0;
        }
        .code-vault, .code-box {
            background-color: #FAF6EE;
            background: linear-gradient(180deg, #FBF8F3 0%, #F5EDE0 100%);
            border: 1px solid #EAE0D0;
            border-radius: 18px;
            padding: 22px 16px 18px 16px;
            text-align: center;
            margin: 20px 0;
        }
        .code-vault-label {
            font-size: 11px;
            font-weight: 800;
            color: #996515;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .code-number {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #1E1915;
            font-family: 'Playfair Display', Georgia, monospace;
            display: inline-block;
        }
        .code-expiry {
            font-size: 11.5px;
            color: #996515;
            margin-top: 14px;
            font-weight: 600;
        }
        .digit-tile {
            width: 44px;
            height: 54px;
            background-color: #FFFFFF;
            border: 1px solid #E5D9C8;
            border-radius: 12px;
            font-family: 'Playfair Display', Georgia, 'Times New Roman', serif;
            font-size: 28px;
            font-weight: 700;
            color: #1A1A1A;
            text-align: center;
            vertical-align: middle;
            box-shadow: 0 4px 10px rgba(180, 150, 110, 0.12);
        }
        .digit-cell {
            padding: 0 4px;
        }
        .security-notice-box {
            background-color: #FAF8F5;
            border-left: 4px solid #C49520;
            border-top: 1px solid #ECE3D2;
            border-right: 1px solid #ECE3D2;
            border-bottom: 1px solid #ECE3D2;
            border-radius: 14px;
            padding: 14px 16px;
            margin-top: 20px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 14px;
        }
        .badge-info { background: #FAF5EA; color: #996515; border: 1px solid #E6D8BA; }
        .badge-warning { background: #FEF9EE; color: #92580C; border: 1px solid #F8E5BA; }
        .badge-danger { background: #FDF2F2; color: #991B1B; border: 1px solid #F8CECE; }
        .badge-success { background: #F0FDF4; color: #166534; border: 1px solid #C5EED0; }

        .footer-section {
            text-align: center;
            margin-top: 24px;
        }
        .footer-pill {
            background-color: #FAF5EA;
            color: #78716C;
            border: 1px solid #E6D8BA;
            padding: 5px 16px;
            border-radius: 9999px;
            display: inline-block;
            font-weight: 700;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .footer-text-1 {
            font-size: 11px;
            color: #8A8177;
            margin: 0 0 4px 0;
            line-height: 1.5;
        }
        .footer-text-2 {
            font-size: 10px;
            color: #ABA298;
            margin: 0;
            line-height: 1.5;
        }

        @media only screen and (max-width: 540px) {
            .canvas {
                padding: 12px 8px !important;
            }
            .container {
                width: 100% !important;
                border-radius: 20px !important;
                padding: 22px 16px 18px 16px !important;
            }
            .brand-title {
                font-size: 21px !important;
                letter-spacing: 1.5px !important;
            }
            .brand-subtitle {
                font-size: 12px !important;
            }
            .hero-card {
                padding: 20px 16px !important;
                border-radius: 16px !important;
            }
            .code-vault, .code-box {
                padding: 16px 6px 14px 6px !important;
            }
            .digit-tile {
                width: 38px !important;
                height: 48px !important;
                font-size: 24px !important;
                border-radius: 10px !important;
            }
            .digit-cell {
                padding: 0 2px !important;
            }
        }
    </style>
    <!--[if mso]>
    <style type="text/css">
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    </style>
    <![endif]-->
</head>
<body>
    @php
        $appUrl = config('app.url');
        // Use public HTTPS URL to prevent email clients (like Gmail) from treating the logo as an attachment
        if (!$appUrl || str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
            $logoSrc = 'https://raw.githubusercontent.com/aldlcrz/Capstone-projct/main/backend-laravel/public/images/logo-icon.png';
        } else {
            $logoSrc = rtrim($appUrl, '/') . '/images/logo-icon.png';
        }
    @endphp

    <div class="canvas">
        <div class="container">
            {{-- Header: Circular LumBarong Logo + LUMBARONG + Subtitle --}}
            <table role="presentation" class="header-brand" border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td style="width: 52px; vertical-align: middle; padding-right: 14px;">
                        @if($logoSrc)
                            <img src="{{ $logoSrc }}" alt="LumBarong" width="52" height="52" style="width: 52px; height: 52px; border-radius: 50%; display: block; border: 1.5px solid #C49520; object-fit: cover;">
                        @else
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="width: 52px; height: 52px; background: radial-gradient(circle, #1F2833 0%, #0F171E 100%); background-color: #0F171E; border: 1.5px solid #C49520; border-radius: 50%; text-align: center; vertical-align: middle;">
                                        <span style="font-family: 'Playfair Display', Georgia, serif; font-size: 26px; font-weight: 700; color: #D4AF37; line-height: 52px; display: inline-block;">L</span>
                                    </td>
                                </tr>
                            </table>
                        @endif
                    </td>
                    <td style="vertical-align: middle;">
                        <h1 class="brand-title">LUMBARONG</h1>
                        <p class="brand-subtitle">Filipino Heritage &middot; Modern Elegance</p>
                    </td>
                </tr>
            </table>

            {{-- Star Divider: ─── ✦ ─── --}}
            <table role="presentation" class="star-divider" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="star-divider-line" style="width: 46%;"></td>
                    <td class="star-divider-center" style="width: 8%;">✦</td>
                    <td class="star-divider-line" style="width: 46%;"></td>
                </tr>
            </table>

            {{-- Main Hero White Card --}}
            <div class="hero-card">
                @yield('content')
            </div>

            {{-- Footer Section --}}
            <div class="footer-section">
                <div>
                    <span class="footer-pill">
                        🛡️ AUTOMATED OFFICIAL NOTIFICATION &bull; DO NOT REPLY
                    </span>
                </div>
                <p class="footer-text-1">
                    This is an automated notification from LumBarong. Direct email replies are not monitored.
                </p>
                <p class="footer-text-2">
                    Handcrafted with Filipino Pride &middot; Lumbarong, Laguna, Philippines &middot; &copy; {{ date('Y') }} LumBarong Inc. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
