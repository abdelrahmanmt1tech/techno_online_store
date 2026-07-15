<?php

namespace App\Filament\Tenant\Resources\Products\Pages;

use App\Filament\Tenant\Resources\Products\ProductResource;
use App\Models\Tenant\ProductVariationOption;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $data = $this->data;

        if (empty($data['variations'])) {
            return;
        }

        DB::transaction(function () use ($data) {
            $this->syncVariations($data);
            $this->syncVariants($data);
        });
    }

    private function syncVariations(array $data): void
    {
        $this->record->variations()->delete();

        foreach ($data['variations'] as $variationData) {
            if (empty($variationData['name'])) {
                continue;
            }

            $variation = $this->record->variations()->create([
                'name' => $variationData['name'],
                'type' => $variationData['type'] ?? 'button',
                'sort_order' => $variationData['sort_order'] ?? 0,
            ]);

            foreach ($variationData['options'] ?? [] as $optionData) {
                if (empty($optionData['value'])) {
                    continue;
                }

                $variation->options()->create([
                    'value' => $optionData['value'],
                    'color_code' => $optionData['color_code'] ?? null,
                    'order' => $optionData['order'] ?? 0,
                ]);
            }
        }
    }

    private function syncVariants(array $data): void
    {
        $this->record->variants()->delete();

        foreach ($data['variants'] ?? [] as $variantData) {
            $combination = $variantData['combination'] ?? [];

            if (empty($combination)) {
                continue;
            }

            $optionIds = $this->resolveOptionIds($combination);

            $variant = $this->record->variants()->create([
                'price' => $variantData['price'] ?? $this->record->price,
                'sale_price' => $variantData['sale_price'] ?? null,
                'expense' => $variantData['expense'] ?? null,
                'quantity' => $variantData['quantity'] ?? 0,
                'sku' => $variantData['sku'] ?? null,
                'image' => $this->resolveImagePath($variantData['image'] ?? null),
                'is_active' => $variantData['is_active'] ?? true,
            ]);

            $variant->options()->sync($optionIds);
        }
    }

    private function resolveOptionIds(array $combination): array
    {
        $optionIds = [];

        foreach ($combination as $variationName => $optionValue) {
            $option = ProductVariationOption::whereHas('variation', function ($q) use ($variationName) {
                $q->where('product_id', $this->record->id)
                    ->where('name', $variationName);
            })->where('value', $optionValue)->first();

            if ($option) {
                $optionIds[] = $option->id;
            }
        }

        return $optionIds;
    }

    private function resolveImagePath(mixed $image): ?string
    {
        if (blank($image)) {
            return null;
        }

        if (is_string($image)) {
            return $image;
        }

        if ($image instanceof UploadedFile || $image instanceof TemporaryUploadedFile) {
            return $this->storeVariantFile($image);
        }

        if (is_array($image)) {
            $flat = Arr::flatten($image);

            foreach ($flat as $item) {
                if ($item instanceof UploadedFile || $item instanceof TemporaryUploadedFile) {
                    return $this->storeVariantFile($item);
                }

                if (is_string($item) && ! empty($item)) {
                    return $item;
                }
            }
        }

        return null;
    }

    private function storeVariantFile(UploadedFile|TemporaryUploadedFile $file): string
    {
        $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
        $path = 'products/variants/'.$filename;

        Storage::disk('public')->put($path, file_get_contents($file->getPathname()));

        return $path;
    }
}
