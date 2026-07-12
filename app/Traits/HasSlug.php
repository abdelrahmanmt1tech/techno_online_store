<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    protected static function bootHasSlug()
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generateSlug($model);
            }
        });
    }

    protected static function generateSlug($model)
    {
        $value = null;

        // 1. جرب name أولاً
        if (isset($model->name) && ! empty($model->name)) {
            $value = $model->name;

            if (method_exists($model, 'getTranslation')) {
                $value = $model->getTranslation('name', 'en') ?? $value;
            }
        }

        // 2. إذا فاضي، جرب title
        if (empty($value) && isset($model->title) && ! empty($model->title)) {
            $value = $model->title;

            if (method_exists($model, 'getTranslation')) {
                $value = $model->getTranslation('title', 'en') ?? $value;
            }
        }

        // 3. إذا كلهم فاضيين، استخدم ID أو timestamp
        if (empty($value)) {
            // إذا في ID (في حالة التحديث)
            if (isset($model->id) && ! empty($model->id)) {
                $value = 'product-'.$model->id;
            } else {
                // إذا مافيش ID (إنشاء جديد)
                $value = 'product-'.time().'-'.Str::random(5);
            }
        }

        $slug = Str::slug($value);

        // التأكد من الفرادة
        $originalSlug = $slug;
        $counter = 1;

        while ($model::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        return $slug;
    }
}
