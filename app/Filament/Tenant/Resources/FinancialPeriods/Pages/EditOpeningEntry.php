<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods\Pages;

use App\Enums\OperationType;
use App\Filament\Tenant\Resources\FinancialPeriods\FinancialPeriodResource;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\Entry;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Tenant\Operation;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Form as SchemaForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditOpeningEntry extends Page
{
    use InteractsWithRecord;

    protected static string $resource = FinancialPeriodResource::class;

    protected string $view = 'filament.resources.financial-periods.pages.edit-opening-entry';

    public ?array $data = [];

    public Operation $openingOperation;

    public function mount(int|string $record, int|string $operation): void
    {
        $this->record = $this->resolveRecord($record);

        $this->openingOperation = Operation::query()
            ->with('entries')
            ->where('financial_period_id', $this->getRecord()->id)
            ->where('operation_type', OperationType::OPENING->value)
            ->findOrFail((int) $operation);

        $this->form->fill([
            'reference_no' => $this->openingOperation->reference_no,
            'comment' => $this->openingOperation->comment,
            'entries' => $this->openingOperation->entries->map(fn (Entry $e): array => [
                'account_tree_id' => $e->account_tree_id,
                'debit' => $e->debit,
                'credit' => $e->credit,
                'notes' => $e->notes,
            ])->values()->all(),
        ]);
    }

    public function getTitle(): string
    {
        return __('dashboard.resources.financial_period.create_opening_entry') . ' #' . $this->openingOperation->id;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaForm::make([
                    Section::make(__('dashboard.resources.financial_period.opening_entry_section'))
                        ->schema([
                            TextInput::make('reference_no')
                                ->label(__('dashboard.resources.financial_period.reference_no')),
                            Textarea::make('comment')
                                ->label(__('dashboard.resources.financial_period.comment'))
                                ->required()
                                ->columnSpanFull(),
                            Repeater::make('entries')
                                ->label(__('dashboard.resources.financial_period.opening_entries'))
                                ->minItems(1)
                                ->addActionLabel(__('dashboard.resources.financial_period.add_opening_entry'))
                                ->schema([
                                    Select::make('account_tree_id')
                                        ->label(__('dashboard.resources.financial_period.account'))
                                        ->options(fn (): array => AccountTree::query()
                                            ->orderBy('account_name')
                                            ->pluck('account_name', 'id')
                                            ->toArray())
                                        ->searchable()
                                        ->preload()
                                        ->native(false),
                                    TextInput::make('debit')
                                        ->label(__('dashboard.resources.operation.debit'))
                                        ->numeric()
                                        ->default(null)
                                        ->minValue(0)
                                        ->step(0.01),
                                    TextInput::make('credit')
                                        ->label(__('dashboard.resources.operation.credit'))
                                        ->numeric()
                                        ->default(null)
                                        ->minValue(0)
                                        ->step(0.01),
                                    Textarea::make('notes')
                                        ->label(__('dashboard.resources.financial_period.notes')),
                                ])
                                ->columns(4)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                    ->livewireSubmitHandler('saveOpeningEntry')
                    ->footer([
                        SchemaActions::make([
                            Action::make('save_opening_entry')
                                ->label('Save')
                                ->submit('saveOpeningEntry'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function saveOpeningEntry(): void
    {
        /** @var FinancialPeriod $period */
        $period = $this->getRecord();
        $data = $this->form->getState();
        $dayDate = $period->start_date?->toDateString();

        $entries = collect($data['entries'] ?? [])
            ->map(function (array $row) use ($dayDate): ?array {
                $accountId = $row['account_tree_id'] ?? null;
                $debit = isset($row['debit']) && $row['debit'] !== '' ? round((float) $row['debit'], 2) : 0.0;
                $credit = isset($row['credit']) && $row['credit'] !== '' ? round((float) $row['credit'], 2) : 0.0;

                if (! $accountId && $debit <= 0 && $credit <= 0) {
                    return null;
                }

                if (! $accountId) {
                    throw ValidationException::withMessages([
                        'data.entries' => __('validation.required', [
                            'attribute' => __('dashboard.resources.financial_period.account'),
                        ]),
                    ]);
                }

                if ($debit <= 0 && $credit <= 0) {
                    throw ValidationException::withMessages([
                        'data.entries' => __('dashboard.resources.financial_period.opening_entry_row_amount_required'),
                    ]);
                }

                return [
                    'account_tree_id' => (int) $accountId,
                    'debit' => $debit > 0 ? $debit : null,
                    'credit' => $credit > 0 ? $credit : null,
                    'notes' => $row['notes'] ?? null,
                    'day_date' => $dayDate,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($entries === []) {
            throw ValidationException::withMessages([
                'data.entries' => __('dashboard.resources.financial_period.opening_entry_no_lines'),
            ]);
        }

        try {
            DB::transaction(function () use ($period, $data, $entries): void {
                // [ADDED] تحديث القيد الافتتاحي واستبدال سطوره من شاشة التعديل المنفصلة.
                $operation = Operation::query()
                    ->where('financial_period_id', $period->id)
                    ->where('operation_type', OperationType::OPENING->value)
                    ->findOrFail($this->openingOperation->id);

                $operation->update([
                    'reference_no' => $data['reference_no'] ?? null,
                    'comment' => $data['comment'] ?? $operation->comment,
                ]);

                $operation->entries()->delete();

                foreach ($entries as $row) {
                    Entry::query()->create([
                        'operation_id' => $operation->id,
                        'account_tree_id' => (int) $row['account_tree_id'],
                        'debit' => $row['debit'] ?? null,
                        'credit' => $row['credit'] ?? null,
                        'notes' => $row['notes'] ?? null,
                        'day_date' => $row['day_date'] ?? $period->start_date?->toDateString(),
                    ]);
                }

                app(\App\Services\Accounting\SyncOperationMetadataService::class)
                    ->refreshOperation($operation, $period->start_date?->toDateString());
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->danger()
                ->title(__('dashboard.resources.financial_period.opening_entry_failed'))
                ->body($e->getMessage())
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('dashboard.resources.financial_period.opening_entry_success'))
            ->send();

        $this->redirect(FinancialPeriodResource::getUrl('opening-entries', ['record' => $period]));
    }
}
