<?php

namespace App\Filament\Tenant\Resources\HrPayrollPeriods\RelationManagers;

use App\Models\Tenant\HrPayrollEmployee;
use App\Models\Tenant\HrPayrollPeriod;
use App\Services\Hr\PayrollGenerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('hr.resources.payroll_employees');
    }

    public static function getModelLabel(): string
    {
        return __('hr.resources.payroll_employee');
    }

    public static function getPluralModelLabel(): string
    {
        return __('hr.resources.payroll_employees');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('employee.employee_number')->label(__('hr.fields.employee_number')),
                TextColumn::make('employee.full_name')->label(__('hr.fields.employee'))->searchable(),
                TextColumn::make('base_salary_snapshot')->label(__('hr.fields.base_salary')),
                TextColumn::make('present_days')->label(__('hr.fields.present_days')),
                TextColumn::make('late_days')->label(__('hr.fields.late_days')),
                TextColumn::make('absent_days')->label(__('hr.fields.absent_days')),
                TextColumn::make('absence_deduction')->label(__('hr.fields.absence_deduction')),
                TextColumn::make('late_deduction')->label(__('hr.fields.late_deduction')),
                TextColumn::make('manual_deduction')->label(__('hr.fields.manual_deduction')),
                TextColumn::make('total_deductions')->label(__('hr.fields.total_deductions')),
                TextColumn::make('net_salary')->label(__('hr.fields.net_salary'))->weight('bold'),
                TextColumn::make('status')->label(__('hr.fields.status'))->badge(),
            ])
            ->recordActions([
                Action::make('adjustManualDeduction')
                    ->label(__('hr.actions.adjust'))
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn () => Auth::user()->can('hr.payroll.generate')
                        && ! $this->getOwnerRecord()->isLocked())
                    ->form([
                        TextInput::make('manual_deduction')
                            ->label(__('hr.fields.manual_deduction'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        Textarea::make('manual_deduction_reason')
                            ->label(__('hr.fields.manual_deduction_reason'))
                            ->rows(2),
                    ])
                    ->fillForm(fn (HrPayrollEmployee $record) => [
                        'manual_deduction' => $record->manual_deduction,
                        'manual_deduction_reason' => $record->manual_deduction_reason,
                    ])
                    ->action(function (HrPayrollEmployee $record, array $data) {
                        try {
                            app(PayrollGenerationService::class)->applyManualDeduction(
                                $record,
                                (string) $data['manual_deduction'],
                                $data['manual_deduction_reason'] ?? null,
                            );
                            Notification::make()->title(__('hr.notifications.manual_deduction_applied'))->success()->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->title(collect($e->errors())->flatten()->first() ?? __('hr.notifications.error'))
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([])
            ->emptyStateHeading(__('hr.empty.default'));
    }

    public function getOwnerRecord(): HrPayrollPeriod
    {
        /** @var HrPayrollPeriod $record */
        $record = parent::getOwnerRecord();

        return $record;
    }
}
