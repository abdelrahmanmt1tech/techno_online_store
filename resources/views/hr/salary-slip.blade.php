<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('hr.resources.payroll_employee') }} — {{ $employee->employee_number }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; margin: 0; padding: 16px; color: #111; }
        .slip { max-width: 720px; margin: 0 auto; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .muted { color: #555; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: start; padding: 6px 0; font-size: 13px; border-bottom: 1px solid #eee; }
        .right { text-align: end; }
        .grand { font-weight: bold; font-size: 15px; }
        hr { border: none; border-top: 1px dashed #999; margin: 12px 0; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<div class="slip">
    <button class="no-print" onclick="window.print()">{{ __('hr.actions.print') }}</button>
    <h1>{{ config('app.name') }} — {{ __('hr.labels.salary_slip') }}</h1>
    <div class="muted">{{ now()->toDateTimeString() }}</div>
    <hr>
    <table>
        <tr><td>{{ __('hr.fields.employee') }}</td><td class="right">{{ $employee->full_name }}</td></tr>
        <tr><td>{{ __('hr.fields.employee_number') }}</td><td class="right">{{ $employee->employee_number }}</td></tr>
        <tr><td>{{ __('hr.fields.department') }}</td><td class="right">{{ $employee->department?->name ?: '—' }}</td></tr>
        <tr><td>{{ __('hr.fields.job_title') }}</td><td class="right">{{ $employee->jobTitle?->name ?: '—' }}</td></tr>
        <tr><td>{{ __('hr.fields.period') }}</td><td class="right">{{ $period->name }} ({{ $period->start_date?->toDateString() }} → {{ $period->end_date?->toDateString() }})</td></tr>
        <tr><td>{{ __('hr.fields.base_salary') }}</td><td class="right">{{ $line->base_salary_snapshot }}</td></tr>
        <tr><td>{{ __('hr.fields.present_days') }}</td><td class="right">{{ $line->present_days }}</td></tr>
        <tr><td>{{ __('hr.fields.absent_days') }}</td><td class="right">{{ $line->absent_days }}</td></tr>
        <tr><td>{{ __('hr.fields.total_late_minutes') }}</td><td class="right">{{ $line->total_late_minutes }}</td></tr>
        <tr><td>{{ __('hr.fields.absence_deduction') }}</td><td class="right">{{ $line->absence_deduction }}</td></tr>
        <tr><td>{{ __('hr.fields.late_deduction') }}</td><td class="right">{{ $line->late_deduction }}</td></tr>
        <tr><td>{{ __('hr.fields.manual_deduction') }}</td><td class="right">{{ $line->manual_deduction }}</td></tr>
        <tr><td>{{ __('hr.fields.manual_deduction_reason') }}</td><td class="right">{{ $line->manual_deduction_reason ?: '—' }}</td></tr>
        <tr><td>{{ __('hr.fields.total_deductions') }}</td><td class="right">{{ $line->total_deductions }}</td></tr>
        <tr class="grand"><td>{{ __('hr.fields.net_salary') }}</td><td class="right">{{ $line->net_salary }}</td></tr>
        <tr><td>{{ __('hr.fields.status') }}</td><td class="right">{{ $line->status }}</td></tr>
        <tr><td>{{ __('hr.fields.paid_at') }}</td><td class="right">{{ $line->paid_at ?: '—' }}</td></tr>
    </table>
</div>
</body>
</html>
