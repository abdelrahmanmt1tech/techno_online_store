<?php

namespace App\Services\Accounting\Posting;

use App\Enums\Erp\PaymentMethod;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\Client;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\TenantSetting;
use Illuminate\Validation\ValidationException;

final class ResolvePostingAccountsService
{
    /**
     * True when core auto-posting account settings are present.
     * Incomplete config → skip posting (do not break invoice Actions).
     */
    public function postingConfigured(): bool
    {
        foreach ([
            'sales_revenue_account_tree_id',
            'sales_returns_account_tree_id',
            'inventory_account_tree_id',
            'cogs_account_tree_id',
            'default_cash_account_tree_id',
            'default_bank_account_tree_id',
            'default_wallet_account_tree_id',
            'walk_in_ar_account_tree_id',
            'suppliers_account_tree_id',
            'clients_account_tree_id',
        ] as $key) {
            if ((int) (TenantSetting::getValue($key) ?? 0) <= 0) {
                return false;
            }
        }

        return true;
    }

    public function requireSettingAccount(string $key): AccountTree
    {
        $id = (int) (TenantSetting::getValue($key) ?? 0);
        if ($id <= 0) {
            throw ValidationException::withMessages([
                $key => __('dashboard.pages.accounting_settings.missing_posting_account', ['key' => $key]),
            ]);
        }

        $account = AccountTree::query()->find($id);
        if (! $account) {
            throw ValidationException::withMessages([
                $key => __('dashboard.pages.accounting_settings.missing_posting_account', ['key' => $key]),
            ]);
        }

        if (! $account->isPostable()) {
            throw ValidationException::withMessages([
                $key => __('dashboard.financial_periods.messages.parent_account_not_allowed', [
                    'account' => $account->account_name,
                ]),
            ]);
        }

        return $account;
    }

    public function salesRevenue(): AccountTree
    {
        return $this->requireSettingAccount('sales_revenue_account_tree_id');
    }

    public function salesReturns(): AccountTree
    {
        return $this->requireSettingAccount('sales_returns_account_tree_id');
    }

    public function inventory(): AccountTree
    {
        return $this->requireSettingAccount('inventory_account_tree_id');
    }

    public function cogs(): AccountTree
    {
        return $this->requireSettingAccount('cogs_account_tree_id');
    }

    public function salesTaxPayable(): ?AccountTree
    {
        $id = (int) (TenantSetting::getValue('sales_tax_payable_account_tree_id') ?? 0);

        return $id > 0 ? AccountTree::query()->find($id) : null;
    }

    public function purchaseTaxReceivable(): ?AccountTree
    {
        $id = (int) (TenantSetting::getValue('purchase_tax_receivable_account_tree_id') ?? 0);

        return $id > 0 ? AccountTree::query()->find($id) : null;
    }

    public function cashForMethod(PaymentMethod $method): AccountTree
    {
        $key = match ($method) {
            PaymentMethod::Cash, PaymentMethod::Other => 'default_cash_account_tree_id',
            PaymentMethod::Wallet => 'default_wallet_account_tree_id',
            PaymentMethod::BankTransfer, PaymentMethod::Card, PaymentMethod::Online => 'default_bank_account_tree_id',
        };

        return $this->requireSettingAccount($key);
    }

    /**
     * Resolve AR leaf for a store customer / CRM client.
     */
    public function receivableForCustomer(?int $customerId): AccountTree
    {
        if ($customerId) {
            $client = Client::query()->find($customerId);
            if ($client) {
                if (! $client->account_tree_id) {
                    $client->accTree();
                    $client->refresh();
                }
                if ($client->account_tree_id) {
                    return AccountTree::query()->findOrFail($client->account_tree_id);
                }
            }

            $customer = Customer::query()->find($customerId);
            if ($customer?->account_tree_id) {
                return AccountTree::query()->findOrFail($customer->account_tree_id);
            }
        }

        return $this->requireSettingAccount('walk_in_ar_account_tree_id');
    }

    public function payableForSupplier(int $supplierId): AccountTree
    {
        $supplier = Supplier::query()->findOrFail($supplierId);
        if (! $supplier->account_tree_id) {
            $supplier->accTree();
            $supplier->refresh();
        }

        if (! $supplier->account_tree_id) {
            throw ValidationException::withMessages([
                'supplier_id' => __('dashboard.pages.accounting_settings.supplier_account_missing'),
            ]);
        }

        return AccountTree::query()->findOrFail($supplier->account_tree_id);
    }
}
