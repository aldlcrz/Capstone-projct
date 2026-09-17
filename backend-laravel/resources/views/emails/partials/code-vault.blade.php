@php
    $digits = str_split(trim((string)($code ?? '000000')));
    $expiryText = $expiryText ?? 'Valid for 5 minutes';
    $vaultLabel = $vaultLabel ?? 'ACCOUNT VERIFICATION CODE';
@endphp

<div class="code-vault" style="background-color: #FAF6EE; background: linear-gradient(180deg, #FBF8F3 0%, #F5EDE0 100%); border: 1px solid #EAE0D0; border-radius: 18px; padding: 22px 16px 18px 16px; text-align: center; margin: 20px 0;">
    <div class="code-vault-label" style="font-size: 11px; font-weight: 800; color: #996515; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 16px;">
        ✦ &nbsp; {{ $vaultLabel }} &nbsp; ✦
    </div>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
        <tr>
            @foreach($digits as $digit)
            <td align="center" class="digit-cell" style="padding: 0 4px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" class="digit-tile" style="width: 44px; height: 54px; background-color: #FFFFFF; border: 1px solid #E5D9C8; border-radius: 12px; font-family: 'Playfair Display', Georgia, 'Times New Roman', serif; font-size: 28px; font-weight: 700; color: #1A1A1A; text-align: center; vertical-align: middle; box-shadow: 0 4px 10px rgba(180, 150, 110, 0.12);">
                            {{ $digit }}
                        </td>
                    </tr>
                </table>
            </td>
            @endforeach
        </tr>
    </table>
    <div style="margin-top: 16px;">
        <span style="background-color: #FAF5EA; border: 1px solid #EADDC8; color: #996515; font-size: 11.5px; font-weight: 600; border-radius: 9999px; padding: 6px 18px; display: inline-block;">
            ⏱️ &nbsp; {{ $expiryText }} &bull; Single-use security passkey
        </span>
    </div>
</div>
