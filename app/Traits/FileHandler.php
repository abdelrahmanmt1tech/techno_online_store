<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait FileHandler
{
    public function saveFiles(array $files, string $directory = 'uploads'): void
    {
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $path = $file->store($directory, 'public');
                $this->files()->create(['path' => $path]);
            }
        }
    }

    public function updateFiles(array $files, string $directory = 'uploads'): void
    {
        // Delete old files
        foreach ($this->files as $file) {
            Storage::disk('public')->delete($file->path);
            $file->delete();
        }

        // Save new ones
        $this->saveFiles($files, $directory);
    }

    protected function updateSingleFile(
        object $model,
        UploadedFile $file,
        string $directory,
        string $column = 'file'
    ): void {
        // Delete old file if exists
        if (! empty($model->{$column})) {
            Storage::disk('public')->delete($model->{$column});
        }

        // Store new file
        $path = $file->store($directory, 'public');

        // Update model column
        $model->update([
            $column => $path,
        ]);
    }

    protected function saveOrUpdateFile(
        ?Model $model, // Model موجود أو null
        UploadedFile $file,
        string $directory,
        string $column = 'file'
    ): string {
        // Store new file
        $path = $file->store($directory, 'public');

        // Update model if exists in DB
        if ($model?->exists) {
            // Delete old file if exists
            if (! empty($model->{$column})) {
                Storage::disk('public')->delete($model->{$column});
            }

            $model->update([
                $column => $path,
            ]);
        }

        // Return path for use in creating new model
        return $path;
    }
}
