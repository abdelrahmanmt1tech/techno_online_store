<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    use HasTranslations;

    public array $translatable = ['question', 'answer'];

    protected $fillable = [
        'question',
        'answer',
        'order',
        'is_active',
        'faqable_type',
        'faqable_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function faqable(): MorphTo
    {
        return $this->morphTo();
    }
}
