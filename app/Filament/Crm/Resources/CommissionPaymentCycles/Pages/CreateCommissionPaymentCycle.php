<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\Pages;

use App\Enums\PaymentMethod;
use App\Filament\Crm\Resources\CommissionPaymentCycles\CommissionPaymentCycleResource;
use App\Filament\Crm\Resources\CommissionPaymentCycles\Schemas\CommissionPaymentCycleWizard;
use App\Models\Tenant\Branch;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Services\Crm\Commission\CommissionPaymentCycleCommissionSelector;
use App\Services\Crm\Commission\CommissionPaymentCycleWorkflowService;
use App\Support\Crm\CrmBranchVisibility;
use App\Support\Money\DecimalMath;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateCommissionPaymentCycle extends CreateRecord
{
    use HasWizard;

    protected static string $resource = CommissionPaymentCycleResource::class;

    protected static bool $canCreateAnother = false;

    /** @var array<int, array{id: int, user_id: int, user_name: string, opportunity_title: string, remaining_amount: string, due_at: ?string}> */
    public array $payableCommissions = [];

    /** @var array<int, Step>|null */
    protected ?array $cachedWizardSteps = null;

    public function getTitle(): string
    {
        return __('crm.payment_cycles.wizard.title');
    }

    protected function fillForm(): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $defaultBranchId = null;

        if (! CrmBranchVisibility::canViewAllBranches($user)) {
            $branchIds = CrmBranchVisibility::branchIdsFor($user);

            if (count($branchIds) === 1) {
                $defaultBranchId = $branchIds[0];
            }
        }

        $this->form->fill([
            'period_from' => now()->startOfMonth()->toDateString(),
            'period_to' => now()->endOfMonth()->toDateString(),
            'branch_id' => $defaultBranchId,
            'employee_scope' => $this->defaultEmployeeScope(),
            'employee_id' => null,
            'employee_ids' => [],
            'selected_commission_ids' => [],
            'allocations' => [],
            'payment_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
            'reference_number' => null,
            'notes' => null,
            'submit_for_approval' => false,
        ]);
    }

    /**
     * @return array<int, Step>
     */
    public function getSteps(): array
    {
        return $this->cachedWizardSteps ??= CommissionPaymentCycleWizard::steps($this);
    }

    public function getWizardComponent(): Component
    {
        return Wizard::make($this->getSteps())
            ->startOnStep($this->getStartStep())
            ->cancelAction($this->getCancelFormAction())
            ->submitAction($this->getSubmitFormAction())
            ->alpineSubmitHandler("\$wire.{$this->getSubmitFormLivewireMethodName()}()")
            ->skippable($this->hasSkippableSteps())
            ->contained(false)
            ->nextAction(
                fn (Action $action): Action => $action
                    ->label(__('crm.payment_cycles.wizard.next')),
            )
            ->previousAction(
                fn (Action $action): Action => $action
                    ->label(__('crm.payment_cycles.wizard.previous'))
                    ->color('gray'),
            );
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label(__('crm.payment_cycles.wizard.submit'))
            ->color('success');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return __('crm.payment_cycles.notifications.created');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $workflow = app(CommissionPaymentCycleWorkflowService::class);

        $cycle = $workflow->createDraft(
            $this->cyclePayload($data),
            $this->buildAllocationsPayload($data),
            $user,
        );

        if (($data['submit_for_approval'] ?? false) && $user->can('crm_commission_payment_cycles.update')) {
            $workflow->submitForApproval($cycle->fresh(['allocations']), $user);
        }

        return $cycle;
    }

    public function branchSelectField(): Select
    {
        $user = Auth::user();

        $field = Select::make('branch_id')
            ->label(__('dashboard.fields.branch'))
            ->searchable()
            ->preload()
            ->placeholder('-');

        if ($user !== null && CrmBranchVisibility::canViewAllBranches($user)) {
            return $field
                ->options(fn (): array => Branch::query()
                    ->get()
                    ->mapWithKeys(fn (Branch $branch): array => [$branch->id => $branch->name_translated])
                    ->all())
                ->visible(fn (): bool => $this->showBranchSelect());
        }

        $branchIds = $user !== null ? CrmBranchVisibility::branchIdsFor($user) : [];

        return $field
            ->options(fn (): array => Branch::query()
                ->whereIn('id', $branchIds)
                ->get()
                ->mapWithKeys(fn (Branch $branch): array => [$branch->id => $branch->name_translated])
                ->all())
            ->default(count($branchIds) === 1 ? $branchIds[0] : null)
            ->visible(fn (): bool => $this->showBranchSelect());
    }

    /**
     * @return array<int, string>
     */
    public function employeeOptions(): array
    {
        return TenantUser::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    public function employeeScopeOptions(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $options = [];

        if ($user->can('crm_commission_payment_cycles.pay_single_employee')) {
            $options['single'] = __('crm.payment_cycles.modes.single_employee');
        }

        if ($user->can('crm_commission_payment_cycles.pay_multiple_employees')) {
            $options['multiple'] = __('crm.payment_cycles.modes.multiple_employees');
        }

        if ($user->can('crm_commission_payment_cycles.pay_all_employees')) {
            $options['all'] = __('crm.payment_cycles.modes.all_employees');
        }

        return $options;
    }

    public function loadPayableCommissions(): void
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $data = $this->form->getRawState();

        $commissions = CommissionPaymentCycleCommissionSelector::payableCommissions(
            $user,
            Carbon::parse($data['period_from']),
            Carbon::parse($data['period_to']),
            isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            $this->resolveEmployeeIds($data),
        );

        $this->payableCommissions = collect($commissions)
            ->map(fn (OpportunityCommission $commission): array => [
                'id' => $commission->id,
                'user_id' => $commission->user_id,
                'user_name' => $commission->user?->name ?? '#'.$commission->user_id,
                'opportunity_title' => $commission->opportunity?->title ?? '-',
                'remaining_amount' => $commission->remaining_amount,
                'due_at' => $commission->due_at?->format('Y-m-d'),
            ])
            ->keyBy('id')
            ->all();

        $this->form->fillPartially(
            ['selected_commission_ids' => []],
            ['selected_commission_ids'],
            shouldCallHydrationHooks: false,
            shouldFillStateWithNull: false,
        );
    }

    /**
     * @return array<int, string>
     */
    public function commissionCheckboxOptions(): array
    {
        return collect($this->payableCommissions)
            ->mapWithKeys(fn (array $commission): array => [
                $commission['id'] => $this->formatCommissionOption($commission),
            ])
            ->all();
    }

    public function initializeAllocations(): void
    {
        $selectedCommissionIds = $this->data['selected_commission_ids']
            ?? $this->form->getRawState()['selected_commission_ids']
            ?? [];

        $allocations = [];

        foreach ($selectedCommissionIds as $commissionId) {
            $commission = $this->payableCommissions[(int) $commissionId]
                ?? $this->payableCommissions[(string) $commissionId]
                ?? null;

            if ($commission === null) {
                continue;
            }

            $allocations[] = [
                'opportunity_commission_id' => $commission['id'],
                'user_id' => $commission['user_id'],
                'employee_label' => $commission['user_name'],
                'opportunity_label' => $commission['opportunity_title'],
                'remaining_amount' => $commission['remaining_amount'],
                'payment_mode' => 'full',
                'planned_payment_amount' => $commission['remaining_amount'],
            ];
        }

        $this->data['allocations'] = $allocations;

        $this->form->fillPartially(
            ['allocations' => $allocations],
            ['allocations'],
            shouldCallHydrationHooks: false,
            shouldFillStateWithNull: false,
        );
    }

    public function validateStepThree(): void
    {
        $data = $this->form->getRawState();

        foreach ($data['allocations'] ?? [] as $index => $allocation) {
            $remaining = (string) ($allocation['remaining_amount'] ?? DecimalMath::zero());
            $amount = DecimalMath::normalize((string) ($allocation['planned_payment_amount'] ?? DecimalMath::zero()));
            $mode = (string) ($allocation['payment_mode'] ?? 'full');

            if ($mode === 'partial' && ! (Auth::user()?->can('crm_commission_payment_cycles.pay_partial') ?? false)) {
                throw ValidationException::withMessages([
                    "data.allocations.{$index}.payment_mode" => __('crm.payment_cycles.validation.partial_not_allowed'),
                ]);
            }

            if ($mode === 'full' && DecimalMath::compare($amount, $remaining) !== 0) {
                throw ValidationException::withMessages([
                    "data.allocations.{$index}.planned_payment_amount" => __('crm.payment_cycles.validation.full_amount_mismatch'),
                ]);
            }

            if (DecimalMath::compare($amount, $remaining) === 1) {
                throw ValidationException::withMessages([
                    "data.allocations.{$index}.planned_payment_amount" => __('crm.commissions.validation.payment_amount_exceeds_remaining'),
                ]);
            }
        }
    }

    public function buildPreviewSummary(): string
    {
        $data = $this->form->getRawState();
        $plannedTotal = DecimalMath::zero();

        foreach ($data['allocations'] ?? [] as $allocation) {
            $plannedTotal = DecimalMath::add(
                $plannedTotal,
                DecimalMath::normalize((string) ($allocation['planned_payment_amount'] ?? DecimalMath::zero())),
            );
        }

        $lines = [
            __('crm.payment_cycles.wizard.preview.period', [
                'from' => $data['period_from'] ?? '-',
                'to' => $data['period_to'] ?? '-',
            ]),
            __('crm.payment_cycles.wizard.preview.allocations_count', [
                'count' => count($data['allocations'] ?? []),
            ]),
            __('crm.payment_cycles.wizard.preview.planned_total', [
                'amount' => $plannedTotal,
            ]),
            __('crm.payment_cycles.wizard.preview.payment_date', [
                'date' => $data['payment_date'] ?? '-',
            ]),
            __('crm.payment_cycles.wizard.preview.payment_method', [
                'method' => filled($data['payment_method'] ?? null)
                    ? (PaymentMethod::tryFrom((string) $data['payment_method'])?->label() ?? '-')
                    : '-',
            ]),
        ];

        foreach ($data['allocations'] ?? [] as $allocation) {
            $lines[] = sprintf(
                '• %s — %s: %s',
                $allocation['employee_label'] ?? '-',
                $allocation['opportunity_label'] ?? '-',
                $allocation['planned_payment_amount'] ?? DecimalMath::zero(),
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array{id: int, user_id: int, user_name: string, opportunity_title: string, remaining_amount: string, due_at: ?string}  $commission
     */
    public function formatCommissionOption(array $commission): string
    {
        return __('crm.payment_cycles.wizard.commission_option', [
            'employee' => $commission['user_name'],
            'opportunity' => $commission['opportunity_title'],
            'remaining' => $commission['remaining_amount'],
            'due' => $commission['due_at'] ?? '-',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function resolveEmployeeIds(array $data): array
    {
        return match ($data['employee_scope'] ?? 'all') {
            'single' => isset($data['employee_id']) ? [(int) $data['employee_id']] : [],
            'multiple' => array_map(intval(...), $data['employee_ids'] ?? []),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{opportunity_commission_id: int, user_id: int, planned_payment_amount: string}>
     */
    private function buildAllocationsPayload(array $data): array
    {
        $payload = [];

        foreach ($data['allocations'] ?? [] as $allocation) {
            $payload[] = [
                'opportunity_commission_id' => (int) $allocation['opportunity_commission_id'],
                'user_id' => (int) $allocation['user_id'],
                'planned_payment_amount' => DecimalMath::normalize((string) $allocation['planned_payment_amount']),
            ];
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function cyclePayload(array $data): array
    {
        return [
            'period_from' => $data['period_from'],
            'period_to' => $data['period_to'],
            'branch_id' => $data['branch_id'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function defaultEmployeeScope(): string
    {
        $options = $this->employeeScopeOptions();

        if (isset($options['all'])) {
            return 'all';
        }

        if (isset($options['multiple'])) {
            return 'multiple';
        }

        return 'single';
    }

    private function showBranchSelect(): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        if (CrmBranchVisibility::canViewAllBranches($user)) {
            return true;
        }

        return count(CrmBranchVisibility::branchIdsFor($user)) > 1;
    }
}
