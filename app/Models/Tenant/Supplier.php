<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\Tenant\Concerns\HasErpAuthors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use BelongsToTenantConnection;
    use HasErpAuthors;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'gondc_name',
        'code',
        'phone',
        'email',
        'tax_number',
        'address',
        'payment_terms_days',
        'notes',
        'is_active',
        'account_tree_id',
        'accounts_center_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (Supplier $model): void {
            $model->accTree();
        });
    }

    public function accountTree(): BelongsTo
    {
        return $this->belongsTo(AccountTree::class, 'account_tree_id');
    }

    public function accountsCenter(): BelongsTo
    {
        return $this->belongsTo(AccountsCenter::class, 'accounts_center_id');
    }

    /**
     * Sync leaf account under suppliers parent from TenantSetting.
     */
    public function accTree(): void
    {
        $parentId = TenantSetting::getValue('suppliers_account_tree_id');
        if (! $parentId) {
            return;
        }

        $displayName = trim((string) ($this->name ?? ''));
        if ($displayName === '' && ! empty($this->gondc_name)) {
            $displayName = trim((string) $this->gondc_name);
        }
        if ($displayName === '') {
            $displayName = 'مورد #'.$this->id;
        }

        $code = 'SUPPLIER#'.$this->id;

        $acc = AccountTree::updateOrCreate(
            [
                'account_code' => $code,
            ],
            [
                'parent_id' => (int) $parentId,
                'account_name' => $displayName,
                'account_code' => $code,
                'account_type' => 'credit',
                'main_acc_status' => 'sub',
            ]
        );

        if ((int) ($this->account_tree_id ?? 0) !== (int) $acc->id) {
            $this->account_tree_id = $acc->id;
            $this->saveQuietly();
        } elseif ((int) ($this->account_tree_id ?? 0) > 0) {
            AccountTree::query()
                ->whereKey($this->account_tree_id)
                ->update(['account_name' => $displayName]);
        }
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class);
    }
}
