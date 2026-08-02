<?php

namespace App\Models\Tenant;

use App\Models\Tenant\AccountsCenterMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountsCenter extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'debit',
        'credit',
        'account_tree_id',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function accountTree(): BelongsTo
    {
        return $this->belongsTo(AccountTree::class, 'account_tree_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AccountsCenterMovement::class, 'accounts_center_id');
    }

    /**
     * Rebuild stored debit/credit from all movement rows (ticket/reservation profit + manual operation sides).
     */
    public static function recalculateDebitCreditFromMovements(int $accountsCenterId): void
    {
        $debit = 0.0;
        $credit = 0.0;

        AccountsCenterMovement::query()
            ->where('accounts_center_id', $accountsCenterId)
            ->get(['debit', 'credit', 'amount'])
            ->each(function (AccountsCenterMovement $row) use (&$debit, &$credit): void {
                $rowDebit = (float) ($row->debit ?? 0);
                $rowCredit = (float) ($row->credit ?? 0);

                if ($rowDebit > 0 || $rowCredit > 0) {
                    $debit += $rowDebit;
                    $credit += $rowCredit;

                    return;
                }

                $amount = (float) ($row->amount ?? 0);
                if ($amount > 0) {
                    $debit += $amount;
                } elseif ($amount < 0) {
                    $credit += abs($amount);
                }
            });

        static::query()
            ->whereKey($accountsCenterId)
            ->update([
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
            ]);
    }

    protected static function booted(): void
    {
        parent::booted();
//
        self::saved(function (AccountsCenter $model) {
            $model->accTree();
        });
    }

    /**
     * إنشاء/تحديث عقدة في شجرة الحسابات أسفل الفرع المُعد في إعدادات التذاكر (حسابات).
     */
    public function accTree(): void
    {
        $parentId = TenantSetting::getValue('accounts_center_account_tree_id');
        if (! $parentId) {
            return;
        }
        $acc = AccountTree::updateOrCreate(
            [
                'account_code' => 'ACCOUNTS_CENTER#' . $this->id,
            ],
            [
                'parent_id' => $parentId,
                'account_name' => $this->name,
                'account_type' => 'debit',
            ]
        );

        if ((int) ($this->account_tree_id ?? 0) !== (int) $acc->id) {
            $this->account_tree_id = $acc->id;
            $this->saveQuietly();
        } elseif ((int) ($this->account_tree_id ?? 0) > 0) {
            AccountTree::query()
                ->whereKey($this->account_tree_id)
                ->update(['account_name' => (string) $this->name]);
        }
    }



}
