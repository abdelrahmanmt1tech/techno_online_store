<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('dashboard.forgot_password_email_subject') }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: 'Helvetica Neue', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f5f7; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="padding: 40px 32px; text-align: center;">
                            <h2 style="margin: 0 0 8px; font-size: 20px; color: #1a1a1a;">
                                {{ config('app.name') }}
                            </h2>
                            <p style="margin: 0 0 32px; font-size: 14px; color: #6b7280;">
                                {{ __('dashboard.forgot_password_email_heading') }}
                            </p>

                            <div style="background-color: #f9fafb; border: 2px dashed #d1d5db; border-radius: 12px; padding: 24px; margin: 0 auto; display: inline-block;">
                                <p style="margin: 0 0 8px; font-size: 13px; color: #6b7280; text-transform: uppercase; letter-spacing: 2px;">
                                    {{ __('dashboard.forgot_password_otp_label') }}
                                </p>
                                <p style="margin: 0; font-size: 36px; font-weight: 700; color: #166534; letter-spacing: 8px;">
                                    {{ $otp }}
                                </p>
                            </div>

                            <p style="margin: 24px 0 0; font-size: 13px; color: #9ca3af;">
                                {{ __('dashboard.forgot_password_email_expires') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
