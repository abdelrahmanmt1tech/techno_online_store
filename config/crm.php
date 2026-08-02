<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maximum commission percentage without override permission
    |--------------------------------------------------------------------------
    */
    'commission_max_percentage' => env('CRM_COMMISSION_MAX_PERCENTAGE', '25.00'),

    /*
    |--------------------------------------------------------------------------
    | Maximum default opportunity commission percentage per employee
    |--------------------------------------------------------------------------
    | Upper bound for the per-employee "Opportunity Commission Percentage" used to
    | auto-create a commission when an opportunity is closed as won. Defaults to 100.
    */
    'employee_commission_max_percentage' => env('CRM_EMPLOYEE_COMMISSION_MAX_PERCENTAGE', '100'),
];
