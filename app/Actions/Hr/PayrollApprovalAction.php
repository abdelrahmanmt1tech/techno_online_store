<?php

namespace App\Actions\Hr;

use App\Enums\Hr\PayrollPeriodStatus;
use App\Models\Tenant\HrPayrollPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PayrollApprovalAction
{
    public function execute(HrPayrollPeriod $period): HrPayrollPeriod
    {
        if ($period->status !== PayrollPeriodStatus::Generated) {
            throw ValidationException::withMessages([
                'status' => __('hr.validation.payroll_must_be_generated'),
            ]);
        }

        return DB::connection('tenant')->transaction(function () use ($period) {
            /** @var HrPayrollPeriod $locked */
            $locked = HrPayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();
            if (! $locked->status->canTransitionTo(PayrollPeriodStatus::Approved)) {
                throw ValidationException::withMessages([
                    'status' => __('hr.validation.invalid_payroll_transition'),
                ]);
            }

            $locked->status = PayrollPeriodStatus::Approved;
            $locked->approved_at = now();
            $locked->approved_by = Auth::guard('tenant')->id();
            $locked->save();

            $locked->employees()->update(['status' => 'approved']);

            return $locked->fresh();
        });
    }
}
