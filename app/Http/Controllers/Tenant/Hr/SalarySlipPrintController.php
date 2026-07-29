<?php

namespace App\Http\Controllers\Tenant\Hr;

use App\Http\Controllers\Controller;
use App\Models\Tenant\HrPayrollEmployee;
use Illuminate\View\View;

final class SalarySlipPrintController extends Controller
{
    public function __invoke(HrPayrollEmployee $payrollEmployee): View
    {
        abort_unless(auth('tenant')->user()?->can('hr.payroll.view'), 403);

        $payrollEmployee->load(['employee.department', 'employee.jobTitle', 'employee.branch', 'period']);

        return view('hr.salary-slip', [
            'line' => $payrollEmployee,
            'employee' => $payrollEmployee->employee,
            'period' => $payrollEmployee->period,
        ]);
    }
}
