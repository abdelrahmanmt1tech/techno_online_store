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

    public function test_purchase_invoice_and_payment_create_balanced_operations(): void
    {
        $this->seedPostingChart();

        $supplier = \App\Models\Tenant\Supplier::query()->create([
            'name' => 'GL Supplier',
            'is_active' => true,
        ]);
        $supplier->accTree();

        $invoice = \App\Models\Tenant\PurchaseInvoice::query()->create([
            'document_number' => 'PI-GL-1',
            'supplier_id' => $supplier->id,
            'invoice_date' => now()->toDateString(),
            'status' => \App\Enums\Erp\InvoiceStatus::Issued,
            'subtotal' => 200,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 200,
            'paid_amount' => 0,
            'due_amount' => 200,
            'issued_at' => now(),
            'created_by' => $this->user->id,
        ]);

        $op = app(\App\Services\Accounting\Posting\PostPurchaseInvoiceToJournalService::class)->handle($invoice);
        $this->assertNotNull($op);
        $this->assertEquals(200.0, (float) $op->entries()->sum('debit'));
        $this->assertEquals(200.0, (float) $op->entries()->sum('credit'));

        $payment = app(RecordInvoicePaymentAction::class)->execute(
            InvoicePayableType::PurchaseInvoice,
            $invoice->id,
            '200',
            PaymentMethod::BankTransfer,
        );

        $payOp = Operation::query()->where('reference_no', 'PAY-'.$payment->id)->first();
        $this->assertNotNull($payOp);
        $this->assertEquals(200.0, (float) $payOp->entries()->sum('debit'));
        $this->assertEquals(200.0, (float) $payOp->entries()->sum('credit'));
    }

    public function test_sales_return_posts_revenue_and_cogs_reversals(): void
    {
        $this->seedPostingChart();

        $sale = Sale::query()->create([
            'document_number' => 'S-RET-1',
            'branch_id' => $this->branch->id,
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

        $saleItem = SaleItem::query()->create([
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
            'invoiced_quantity' => 2,
            'returned_quantity' => 0,
        ]);

        $ret = \App\Models\Tenant\SalesReturn::query()->create([
            'document_number' => 'SR-1',
            'sale_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'return_date' => now()->toDateString(),
            'status' => \App\Enums\Erp\DocumentStatus::Posted,
            'reason' => 'damaged',
            'posted_at' => now(),
            'posted_by' => $this->user->id,
            'created_by' => $this->user->id,
        ]);

        \App\Models\Tenant\SalesReturnItem::query()->create([
            'sales_return_id' => $ret->id,
            'sale_item_id' => $saleItem->id,
            'source_type' => SaleItemSourceType::Manual->value,
            'quantity' => 1,
            'disposition' => \App\Enums\Erp\ReturnDisposition::Restock->value,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $result = app(\App\Services\Accounting\Posting\PostSalesReturnToJournalService::class)
            ->handle($ret->fresh(['items.saleItem', 'sale']));

        $this->assertNotNull($result['revenue']);
        $this->assertEquals(50.0, (float) $result['revenue']->entries()->sum('debit'));
        $this->assertEquals(50.0, (float) $result['revenue']->entries()->sum('credit'));

        $this->assertNotNull($result['cogs']);
        $this->assertEquals(20.0, (float) $result['cogs']->entries()->sum('debit'));
        $this->assertEquals(20.0, (float) $result['cogs']->entries()->sum('credit'));
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
