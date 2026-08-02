<?php

namespace Tests\Feature\Erp;

use App\Actions\Erp\CreateSalesInvoiceAction;
use App\Actions\Erp\RecordInvoicePaymentAction;
use App\Enums\Erp\InvoicePayableType;
use App\Enums\Erp\PaymentMethod;
use App\Enums\Erp\SaleStatus;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\Operation;
use App\Models\Tenant\Sale;
use App\Models\Tenant\SaleItem;
use App\Models\Tenant\TenantSetting;
use App\Enums\Erp\SaleItemSourceType;
use App\Support\Erp\Decimal;

class AccountingJournalPostingTest extends ErpTestCase
{
    public function test_sales_invoice_and_payment_create_balanced_system_operations(): void
    {
        $this->seedPostingChart();

        $sale = Sale::query()->create([
            'document_number' => 'S-POST-1',
            'branch_id' => $this->branch->id,
            'customer_id' => null,
            'status' => SaleStatus::Confirmed,
            'sale_date' => now()->toDateString(),
            'currency_code' => 'EGP',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100,
            'cost_total' => 40,
            'created_by' => $this->user->id,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'source_type' => SaleItemSourceType::Manual->value,
            'description_snapshot' => 'Item',
            'quantity' => 2,
            'unit_price' => 50,
            'discount' => 0,
            'tax' => 0,
            'line_total' => 100,
            'unit_cost' => 20,
            'cost_total' => 40,
            'invoiced_quantity' => 0,
            'returned_quantity' => 0,
        ]);

        $invoice = app(CreateSalesInvoiceAction::class)->execute($sale->fresh('items'));

        $revOps = Operation::query()
            ->where('service_id', $invoice->id)
            ->where('reference_no', 'SI-'.$invoice->id.'-REV')
            ->where('is_system_generated', true)
            ->get();

        $this->assertCount(1, $revOps);
        $rev = $revOps->first();
        $this->assertEquals(100.0, (float) $rev->entries()->sum('debit'));
        $this->assertEquals(100.0, (float) $rev->entries()->sum('credit'));

        $cogsOps = Operation::query()
            ->where('reference_no', 'SI-'.$invoice->id.'-COGS')
            ->get();
        $this->assertCount(1, $cogsOps);
        $this->assertEquals(40.0, (float) $cogsOps->first()->entries()->sum('debit'));

        // Idempotent re-post via second call path (finder short-circuit)
        app(\App\Services\Accounting\Posting\PostSalesInvoiceToJournalService::class)->handle($invoice->fresh('items'));
        $this->assertEquals(1, Operation::query()->where('reference_no', 'SI-'.$invoice->id.'-REV')->count());

        $payment = app(RecordInvoicePaymentAction::class)->execute(
            InvoicePayableType::SalesInvoice,
            $invoice->id,
            '100',
            PaymentMethod::Cash,
        );

        $payOp = Operation::query()->where('reference_no', 'PAY-'.$payment->id)->first();
        $this->assertNotNull($payOp);
        $this->assertEquals(100.0, (float) $payOp->entries()->sum('debit'));
        $this->assertEquals(100.0, (float) $payOp->entries()->sum('credit'));
    }

    public function test_posting_skipped_when_settings_missing(): void
    {
        TenantSetting::query()->delete();

        $sale = Sale::query()->create([
            'document_number' => 'S-POST-2',
            'branch_id' => $this->branch->id,
            'status' => SaleStatus::Confirmed,
            'sale_date' => now()->toDateString(),
            'currency_code' => 'EGP',
            'subtotal' => 50,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 50,
            'cost_total' => 0,
            'created_by' => $this->user->id,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'source_type' => SaleItemSourceType::Manual->value,
            'description_snapshot' => 'Item',
            'quantity' => 1,
            'unit_price' => 50,
            'discount' => 0,
            'tax' => 0,
            'line_total' => 50,
            'unit_cost' => 0,
            'cost_total' => 0,
            'invoiced_quantity' => 0,
            'returned_quantity' => 0,
        ]);

        $invoice = app(CreateSalesInvoiceAction::class)->execute($sale->fresh('items'));

        $this->assertEquals(0, Operation::query()->where('service_id', $invoice->id)->count());
        $this->assertTrue(Decimal::isPositive((string) $invoice->grand_total));
    }

    private function seedPostingChart(): void
    {
        $mk = function (string $code, string $name, string $type = 'debit'): AccountTree {
            return AccountTree::query()->create([
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'main_acc_status' => 'sub',
                'level' => 3,
                'order' => 1,
            ]);
        };

        $map = [
            'sales_revenue_account_tree_id' => $mk('4101', 'Revenue', 'credit'),
            'sales_returns_account_tree_id' => $mk('4191', 'Returns', 'debit'),
            'inventory_account_tree_id' => $mk('1501', 'Inventory'),
            'cogs_account_tree_id' => $mk('5110', 'COGS'),
            'default_cash_account_tree_id' => $mk('1101', 'Cash'),
            'default_bank_account_tree_id' => $mk('1102', 'Bank'),
            'default_wallet_account_tree_id' => $mk('1103', 'Wallet'),
            'walk_in_ar_account_tree_id' => $mk('1202', 'Walk-in AR'),
            'clients_account_tree_id' => $mk('1201', 'Clients', 'debit'),
            'suppliers_account_tree_id' => $mk('210101', 'Suppliers', 'credit'),
        ];

        foreach ($map as $key => $account) {
            TenantSetting::setValue($key, $account->id);
        }
    }
}
