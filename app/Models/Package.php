<?php

namespace App\Models;

use App\Support\Modules\TenantModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Package extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name', 'desc'];

    protected $fillable = [
        'module',
        'is_full_package',
        'name',
        'desc',
        'trials_duration',
        'sort',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_full_package' => 'boolean',
            'trials_duration' => 'integer',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(PackagePrice::class);
    }

    /**
     * Modules granted by this package: all when it is a full package,
     * otherwise the single module it is tied to.
     *
     * @return list<string>
     */
    public function enabledModules(): array
    {
        if ($this->is_full_package) {
            return array_column(TenantModule::cases(), 'value');
        }

        return $this->module ? [$this->module] : [];
    }
}
