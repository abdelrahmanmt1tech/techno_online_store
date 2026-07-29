<?php

namespace App\Actions\Hr;

use App\Enums\Hr\PayrollPeriodStatus;
use App\Models\Tenant\HrPayrollPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PayrollPaymentAction
{
    public function execute(HrPayrollPeriod $period): HrPayrollPeriod
    {
        if ($period->status !== PayrollPeriodStatus::Approved) {
            throw ValidationException::withMessages([
                'status' => __('hr.validation.payroll_must_be_approved'),
            ]);
        }

        return DB::connection('tenant')->transaction(function () use ($period) {
            /** @var HrPayrollPeriod $locked */
            $locked = HrPayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();
            if (! $locked->status->canTransitionTo(PayrollPeriodStatus::Paid)) {
                throw ValidationException::withMessages([
                    'status' => __('hr.validation.invalid_payroll_transition'),
                ]);
            }

            $now = now();
            $userId = Auth::guard('tenant')->id();

            $locked->status = PayrollPeriodStatus::Paid;
            $locked->paid_at = $now;
            $locked->paid_by = $userId;
            $locked->save();

            $locked->employees()->update([
                'status' => 'paid',
                'paid_at' => $now,
            ]);

            return $locked->fresh();
        });
    }
}
