<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods\Pages;

use App\Filament\Tenant\Resources\FinancialPeriods\FinancialPeriodResource;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\FinancialPeriod;
use App\Services\Accounting\CreateOpeningEntryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Validation\ValidationException;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Form as SchemaForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreateOpeningEntry extends Page
{
    use InteractsWithRecord;

    protected static string $resource = FinancialPeriodResource::class;

    protected string $view = 'filament.resources.financial-periods.pages.create-opening-entry';

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->form->fill([
            'entries' => [
                [
                    'account_tree_id' => null,
                    'debit' => null,
                    'credit' => null,
                    'notes' => null,
                ],
            ],
        ]);
    }

    public function getTitle(): string
    {
        return __('dashboard.resources.financial_period.opening_entry_title', [
            'period' => $this->getRecord()->name,
        ]);
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
                                ->default(__('dashboard.financial_periods.messages.opening_entry_comment', [
                                    'period' => $this->getRecord()->name,
                                ]))
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
                    ->livewireSubmitHandler('createOpeningEntry')
                    ->footer([
                        SchemaActions::make([
                            Action::make('create_opening_entry')
                                ->label(__('dashboard.resources.financial_period.create_opening_entry'))
                                ->submit('createOpeningEntry'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function createOpeningEntry(): void
    {
        /** @var FinancialPeriod $period */
        $period = $this->getRecord();
        $data = $this->form->getState();
//dd($data);
        $dayDate = $this->getRecord()->start_date?->toDateString();

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
            app(CreateOpeningEntryService::class)->handle(
                $period,
                $entries,
                $data['comment'] ?? null,
                $data['reference_no'] ?? null,
                auth()->user(),
                $period->start_date?->toDateString(),
                false,
            );
        } catch (ValidationException $exception) {
            $mapped = $this->mapOpeningEntryValidationErrors($exception);

            $firstMessage = collect($mapped)->flatten()->filter()->first();
            if (filled($firstMessage)) {
                Notification::make()
                    ->danger()
                    ->title(__('dashboard.resources.financial_period.opening_entry_validation_title'))
                    ->body($firstMessage)
                    ->persistent()
                    ->send();
            }

            throw ValidationException::withMessages($mapped);
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

        $this->redirect(FinancialPeriodResource::getUrl('view', ['record' => $period]));
    }

    /**
     * @return array<string, list<string>>
     */
    protected function mapOpeningEntryValidationErrors(ValidationException $exception): array
    {
        $merged = [];

        foreach ($exception->errors() as $key => $messages) {
            $target = match (true) {
                $key === 'entries' => 'data.entries',
                str_starts_with($key, 'entries.') => 'data.'.$key,
                $key === 'account_tree_id' => 'data.entries',
                in_array($key, ['financial_period_id', 'date', 'operation', 'entry'], true) => 'data.comment',
                default => str_starts_with($key, 'data.') ? $key : 'data.comment',
            };

            $merged[$target] = array_values(array_merge($merged[$target] ?? [], $messages));
        }

        return $merged;
    }
}
