@php
    $digits = str_split(trim((string)($code ?? '000000')));
    $expiryText = $expiryText ?? 'Valid for 5 minutes';
    $vaultLabel = $vaultLabel ?? 'Security Authentication Code';
@endphp

<div class="code-vault" style="background-color: #FAF6EE; background: linear-gradient(135deg, #FAF6EE 0%, #F2E9DA 100%); border: 1px solid #DECBB1; border-radius: 18px; padding: 24px 14px 20px 14px; text-align: center; margin: 22px 0; box-shadow: 0 4px 14px rgba(153, 101, 21, 0.05);">
    <div class="code-vault-label" style="font-size: 10px; font-weight: 800; color: #996515; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 16px;">
        ✦ &nbsp; {{ $vaultLabel }} &nbsp; ✦
    </div>
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
        <tr>
            @foreach($digits as $digit)
            <td align="center" class="digit-cell" style="padding: 0 4px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" class="digit-tile" style="width: 44px; height: 54px; background-color: #FFFFFF; border: 1.5px solid #DFCDB0; border-radius: 12px; font-family: ui-serif, Georgia, Cambria, serif; font-size: 26px; font-weight: 800; color: #1E1915; text-align: center; vertical-align: middle; box-shadow: 0 2px 6px rgba(153, 101, 21, 0.08);">
                            {{ $digit }}
                        </td>
                    </tr>
                </table>
            </td>
            @endforeach
        </tr>
    </table>
    <div style="margin-top: 16px;">
        <span class="code-expiry" style="background-color: #FAF5EA; border: 1px solid #E6D8BA; color: #996515; font-size: 11.5px; font-weight: 700; border-radius: 9999px; padding: 5px 14px; display: inline-block;">
            ⏱️ {{ $expiryText }} &bull; Single-use passkey
        </span>
    </div>
</div>
