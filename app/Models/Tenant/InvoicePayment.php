<?php

namespace App\Models\Tenant;

use App\Enums\Erp\InvoicePayableType;
use App\Enums\Erp\PaymentMethod;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'document_number',
        'payable_type',
        'payable_id',
        'payment_method',
        'amount',
        'payment_reference',
        'paid_at',
        'notes',
        'status',
        'reversed_at',
        'reversed_by',
        'reversal_of_id',
        'created_by',
    ];

    protected $casts = [
        'payable_type' => InvoicePayableType::class,
        'payment_method' => PaymentMethod::class,
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'reversed_by');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }

    public function payable(): SalesInvoice|PurchaseInvoice|null
    {
        return match ($this->payable_type) {
            InvoicePayableType::SalesInvoice => SalesInvoice::query()->find($this->payable_id),
            InvoicePayableType::PurchaseInvoice => PurchaseInvoice::query()->find($this->payable_id),
            default => null,
        };
    }
}
