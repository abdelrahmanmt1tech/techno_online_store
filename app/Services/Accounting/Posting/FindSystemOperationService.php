<?php

namespace App\Services\Accounting\Posting;

use App\Models\Tenant\Operation;
use Illuminate\Database\Eloquent\Model;

final class FindSystemOperationService
{
    public function find(Model $service, ?string $referenceNo = null): ?Operation
    {
        $query = Operation::query()
            ->where('service_type', $service->getMorphClass())
            ->where('service_id', $service->getKey())
            ->where('is_system_generated', true);

        if ($referenceNo !== null) {
            $query->where('reference_no', $referenceNo);
        }

        return $query->first();
    }
}
