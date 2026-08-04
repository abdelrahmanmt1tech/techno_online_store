<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __("dashboard.package_email_subject_{$action}") }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f5f7; font-family: 'Helvetica Neue', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f5f7; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="padding: 40px 32px;">
                            <h2 style="margin: 0 0 8px; font-size: 20px; color: #1a1a1a; text-align: center;">
                                {{ config('app.name') }}
                            </h2>
                            <p style="margin: 0 0 32px; font-size: 14px; color: #6b7280; text-align: center;">
                                {{ __('dashboard.package_email_heading') }}
                            </p>

                            <p style="margin: 0 0 24px; font-size: 14px; color: #374151; line-height: 1.7;">
                                {{ __("dashboard.package_email_intro_{$action}", ['package' => $packageName]) }}
                            </p>

                            <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px 24px;">
                                <p style="margin: 0 0 12px; font-size: 13px; color: #6b7280;">
                                    <span style="font-weight: 600; color: #374151;">{{ __('dashboard.tenant_welcome_store') }}:</span>
                                    {{ $tenantName }}
                                </p>
                                <p style="margin: 0 0 12px; font-size: 13px; color: #6b7280;">
                                    <span style="font-weight: 600; color: #374151;">{{ __('dashboard.package_email_details_heading') }}:</span>
                                    {{ $packageName }}
                                </p>
                                @if ($price !== null)
                                    <p style="margin: 0 0 12px; font-size: 13px; color: #6b7280;">
                                        <span style="font-weight: 600; color: #374151;">{{ __('dashboard.package_email_price') }}:</span>
                                        {{ $price }}
                                    </p>
                                @endif
                                @if ($duration !== null)
                                    <p style="margin: 0 0 12px; font-size: 13px; color: #6b7280;">
                                        <span style="font-weight: 600; color: #374151;">{{ __('dashboard.package_email_duration') }}:</span>
                                        {{ $duration }}
                                    </p>
                                @endif
                                @if ($expiresAt !== null)
                                    <p style="margin: 0; font-size: 13px; color: #6b7280;">
                                        <span style="font-weight: 600; color: #374151;">{{ __('dashboard.package_email_expires_at') }}:</span>
                                        {{ $expiresAt }}
                                    </p>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
