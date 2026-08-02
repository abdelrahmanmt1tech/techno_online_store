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
        $options = $this->getAccountTreeOptions();

        return $schema
            ->components([
                Section::make(__('dashboard.pages.accounting_settings.section'))
                    ->description(__('dashboard.pages.accounting_settings.section_description'))
                    ->columns(2)
                    ->schema([
                        Select::make('clients_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.clients_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('suppliers_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.suppliers_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('accounts_center_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.accounts_center_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('income_summary_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.income_summary_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('retained_earnings_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.retained_earnings_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                    ]),
                Section::make(__('dashboard.pages.accounting_settings.posting_section'))
                    ->description(__('dashboard.pages.accounting_settings.posting_section_description'))
                    ->columns(2)
                    ->schema([
                        Select::make('sales_revenue_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.sales_revenue_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('sales_returns_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.sales_returns_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('inventory_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.inventory_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('cogs_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.cogs_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('sales_tax_payable_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.sales_tax_payable_account_tree_id'))
                            ->options($options)->searchable()->preload(),
                        Select::make('purchase_tax_receivable_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.purchase_tax_receivable_account_tree_id'))
                            ->options($options)->searchable()->preload(),
                        Select::make('default_cash_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.default_cash_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('default_bank_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.default_bank_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('default_wallet_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.default_wallet_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
                        Select::make('walk_in_ar_account_tree_id')
                            ->label(__('dashboard.pages.accounting_settings.walk_in_ar_account_tree_id'))
                            ->options($options)->searchable()->preload()->required(),
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
        $data = [];

        foreach ($this->settingKeys() as $key) {
            $value = TenantSetting::getValue($key);
            $data[$key] = $value !== null ? (int) $value : null;
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    protected function settingKeys(): array
    {
        return [
            'clients_account_tree_id',
            'suppliers_account_tree_id',
            'accounts_center_account_tree_id',
            'income_summary_account_tree_id',
            'retained_earnings_account_tree_id',
            'sales_revenue_account_tree_id',
            'sales_returns_account_tree_id',
            'inventory_account_tree_id',
            'cogs_account_tree_id',
            'sales_tax_payable_account_tree_id',
            'purchase_tax_receivable_account_tree_id',
            'default_cash_account_tree_id',
            'default_bank_account_tree_id',
            'default_wallet_account_tree_id',
            'walk_in_ar_account_tree_id',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getAccountTreeOptions(): array
    {
        return AccountTree::query()
            ->orderBy('account_code')
            ->get()
            ->mapWithKeys(fn (AccountTree $a) => [
                $a->id => trim(($a->account_code ? $a->account_code.' — ' : '').$a->account_name),
            ])
            ->all();
    }
}
