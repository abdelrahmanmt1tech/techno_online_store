<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait DeletesFiles
{
    protected static function bootDeletesFiles(): void
    {
        static::deleting(function ($model) {
            if (! property_exists($model, 'filesToDelete')) {
                return;
            }

            foreach ($model->filesToDelete as $attribute) {
                $file = $model->{$attribute};

                if ($file) {
                    Storage::disk($model->fileDisk())->delete($file);
                }
            }
        });

        static::updating(function ($model) {
            if (! property_exists($model, 'filesToDelete')) {
                return;
            }

            foreach ($model->filesToDelete as $attribute) {

                if (! $model->isDirty($attribute)) {
                    continue;
                }

                $oldFile = $model->getOriginal($attribute);

                if ($oldFile) {
                    Storage::disk($model->fileDisk())->delete($oldFile);
                }
            }
        });
    }

    protected function fileDisk(): string
    {
        return property_exists($this, 'fileDisk')
            ? $this->fileDisk
            : 'public';
    }
}
