<?php

namespace App\Filament\Tenant\Pages;

use App\Enums\Hr\PayrollPeriodStatus;
use App\Models\Tenant\HrPayrollEmployee;
use App\Models\Tenant\HrPayrollPeriod;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class HrPayrollSummaryPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?string $slug = 'hr-payroll-summary';

    protected static ?int $navigationSort = 521;

    protected string $view = 'filament.tenant.pages.hr-payroll-summary';

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('hr.nav.hr');
    }

    public static function getNavigationLabel(): string
    {
        return __('hr.reports.payroll_summary');
    }

    public function getTitle(): string|Htmlable
    {
        return __('hr.reports.payroll_summary');
    }

    public static function canAccess(): bool
    {
        return tenant_module_enabled(\App\Support\Modules\TenantModule::Hr) && (Auth::user()->can('hr.reports.view'));
    }

    public function mount(): void
    {
        $latest = HrPayrollPeriod::query()->latest('id')->value('id');

        $this->form->fill([
            'payroll_period_id' => $latest,
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedSchema::make('form'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make(__('hr.reports.filters'))
                        ->schema([
                            Select::make('payroll_period_id')
                                ->label(__('hr.fields.period'))
                                ->options(fn () => HrPayrollPeriod::query()
                                    ->orderByDesc('start_date')
                                    ->get()
                                    ->mapWithKeys(fn (HrPayrollPeriod $period) => [
                                        $period->id => $period->name.' ('.$period->start_date?->toDateString().' → '.$period->end_date?->toDateString().')',
                                    ])
                                    ->all())
                                ->searchable()
                                ->native(false)
                                ->nullable()
                                ->live(),
                        ]),
                ])
                    ->livewireSubmitHandler('noop')
                    ->statePath('data'),
            ])
            ->statePath('data');
    }

    public function noop(): void
    {
        //
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSummaryProperty(): ?array
    {
        $periodId = $this->data['payroll_period_id'] ?? null;

        if (! $periodId) {
            return null;
        }

        $period = HrPayrollPeriod::query()->find($periodId);

        if (! $period) {
            return null;
        }

        $lines = HrPayrollEmployee::query()
            ->where('payroll_period_id', $period->id)
            ->get();

        return [
            'period' => $period->name,
            'status' => $period->status instanceof PayrollPeriodStatus
                ? $period->status->label()
                : (string) $period->status,
            'employees_count' => $lines->count(),
            'total_base_salary' => (float) $lines->sum('base_salary_snapshot'),
            'total_absence_deduction' => (float) $lines->sum('absence_deduction'),
            'total_late_deduction' => (float) $lines->sum('late_deduction'),
            'total_manual_deduction' => (float) $lines->sum('manual_deduction'),
            'total_net_salary' => (float) $lines->sum('net_salary'),
        ];
    }
}
