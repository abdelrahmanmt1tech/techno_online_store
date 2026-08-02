<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\AccountTree;
use App\Models\Tenant\TenantSetting;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountingSettings extends Page
{
    protected string $view = 'filament.pages.accounting-settings';

    protected static ?int $navigationSort = 5;

    public ?array $data = [];

    public function getTitle(): string
    {
        return __('dashboard.pages.accounting_settings.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.accounting_settings.nav');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public function mount(): void
    {
        $this->form->fill($this->getSettingsData());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.pages.accounting_settings.section'))
                    ->description(__('dashboard.pages.accounting_settings.section_description'))
                    ->columns(2)
                    ->schema([
                        Select::make('clients_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.clients_account_tree_id'))
                            ->options($this->getAccountTreeOptions())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('suppliers_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.suppliers_account_tree_id'))
                            ->options($this->getAccountTreeOptions())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('accounts_center_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.accounts_center_account_tree_id'))
                            ->options($this->getAccountTreeOptions())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('income_summary_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.income_summary_account_tree_id'))
                            ->options($this->getAccountTreeOptions())
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('retained_earnings_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.retained_earnings_account_tree_id'))
                            ->options($this->getAccountTreeOptions())
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            TenantSetting::setValue($key, $value);
        }

        Notification::make()
            ->success()
            ->title(__('dashboard.settings_saved_successfully'))
            ->send();
    }

    /**
     * @return array<string, int|string|null>
     */
    protected function getSettingsData(): array
    {
        $keys = [
            'clients_account_tree_id',
            'suppliers_account_tree_id',
            'accounts_center_account_tree_id',
            'income_summary_account_tree_id',
            'retained_earnings_account_tree_id',
        ];

        $data = [];

        foreach ($keys as $key) {
            $value = TenantSetting::getValue($key);
            $data[$key] = $value !== null ? (int) $value : null;
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    protected function getAccountTreeOptions(): array
    {
        return AccountTree::query()
            ->orderBy('account_code')
            ->pluck('account_name', 'id')
            ->all();
    }
}
