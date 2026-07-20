<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('auth.verification_code_subject') }}</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f7f7f7; padding:20px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #eee;">
        <tr>
            <td style="padding:24px;">
                <h2 style="margin:0 0 12px 0;">{{ __('auth.verification_code_subject') }}</h2>
                <p style="margin:0 0 16px 0;">{{ __('auth.use_code_to_verify') }}</p>
                <div style="font-size:28px;font-weight:bold;letter-spacing:4px;background:#f3f4f6;padding:12px 16px;border-radius:6px;text-align:center;margin-bottom:16px;">
                    {{ $code }}
                </div>
                <p style="margin:0 0 8px 0; color:#555;">
                    {{ __('auth.code_expires_in', ['minutes' => $minutes]) }}
                </p>
                <p style="margin:0; color:#999; font-size:12px;">
                    {{ __('auth.ignore_if_not_you') }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
