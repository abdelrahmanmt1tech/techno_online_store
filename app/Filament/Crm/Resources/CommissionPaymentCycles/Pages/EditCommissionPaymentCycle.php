<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Pages;

use App\Filament\Crm\Resources\CommissionPaymentCycles\Actions\CommissionPaymentCycleActions;
use App\Filament\Crm\Resources\CommissionPaymentCycles\CommissionPaymentCycleResource;
use App\Models\TenantUser;
use App\Services\Crm\Commission\CommissionPaymentCycleWorkflowService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditCommissionPaymentCycle extends EditRecord
{
    protected static string $resource = CommissionPaymentCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...CommissionPaymentCycleActions::headerActions($this->record),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return app(CommissionPaymentCycleWorkflowService::class)->update($record, $data, $user);
    }
}
