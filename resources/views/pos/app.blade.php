<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('commerce.nav.pos_terminal') }}</title>
    @vite(['resources/css/pos.css', 'resources/js/pos/app.js'])
</head>
<body>
    <div
        id="pos-app"
        data-bootstrap='@json($bootstrap)'
        data-dashboard-url="{{ $panelDashboardUrl }}"
        data-api-base="{{ url('/app/pos/api') }}"
        data-locale="{{ app()->getLocale() }}"
    ></div>
</body>
</html>
