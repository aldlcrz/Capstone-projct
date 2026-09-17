@php
    $digits = str_split(trim((string)($code ?? '000000')));
    $expiryText = $expiryText ?? 'Valid for 5 minutes';
    $vaultLabel = $vaultLabel ?? 'Security Authentication Code';
@endphp

<div class="code-vault" style="background-color: #FAF7F2; background: linear-gradient(180deg, #FBF9F5 0%, #F5EFE6 100%); border: 1px solid #E2D6C5; border-radius: 16px; padding: 26px 16px 22px 16px; text-align: center; margin: 28px 0; box-shadow: inset 0 1px 0 rgba(255,255,255,0.8), 0 4px 14px rgba(61,43,31,0.04);">
    <div class="code-vault-label" style="font-size: 10px; font-weight: 800; color: #8C7866; letter-spacing: 2.5px; text-transform: uppercase; margin-bottom: 18px; font-family: 'Plus Jakarta Sans', Arial, sans-serif;">
        ✦ &nbsp; {{ $vaultLabel }} &nbsp; ✦
    </div>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
        <tr>
            @foreach($digits as $digit)
            <td align="center" class="digit-cell" style="padding: 0 4px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" class="digit-tile" style="width: 46px; height: 56px; background-color: #FFFFFF; border: 1.5px solid #D8CFC4; border-radius: 12px; font-family: 'Playfair Display', Georgia, 'Times New Roman', serif; font-size: 28px; font-weight: 800; color: #1E1915; text-align: center; vertical-align: middle; box-shadow: 0 3px 8px rgba(61,43,31,0.06);">
                            {{ $digit }}
                        </td>
                    </tr>
                </table>
            </td>
            @endforeach
        </tr>
    </table>
    <div style="margin-top: 18px;">
        <span class="code-expiry" style="background-color: #FFF9EE; border: 1px solid #F4DFBF; color: #8C590E; font-size: 11.5px; font-weight: 700; border-radius: 20px; padding: 6px 16px; display: inline-block; letter-spacing: 0.2px;">
            ⏱️ {{ $expiryText }} &bull; Single-use security passkey
        </span>
    </div>
</div>
