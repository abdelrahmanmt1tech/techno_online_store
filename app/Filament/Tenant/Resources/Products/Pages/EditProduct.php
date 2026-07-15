<?php

namespace App\Filament\Tenant\Resources\Products\Pages;

use App\Filament\Tenant\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['variations'] = $this->record->variations()
            ->with('options')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($variation) => [
                'name' => $variation->name,
                'type' => $variation->type,
                'sort_order' => $variation->sort_order,
                'options' => $variation->options
                    ->sortBy('order')
                    ->map(fn ($option) => [
                        'value' => $option->value,
                        'color_code' => $option->color_code,
                        'order' => $option->order,
                    ])
                    ->values()
                    ->toArray(),
            ])
            ->toArray();

        $data['variants'] = $this->record->variants()
            ->with('options.variation')
            ->get()
            ->map(function ($variant) {
                $combination = $variant->options
                    ->sortBy('variation.name')
                    ->mapWithKeys(fn ($option) => [
                        $option->variation->name => $option->value,
                    ])
                    ->toArray();

                $combinationLabel = implode(' - ', $combination);

                return [
                    'combination_label' => $combinationLabel,
                    'combination' => $combination,
                    'price' => $variant->price,
                    'sale_price' => $variant->sale_price,
                    'expense' => $variant->expense,
                    'quantity' => $variant->quantity,
                    'sku' => $variant->sku,
                    'image' => $variant->image,
                    'is_active' => $variant->is_active,
                ];
            })
            ->toArray();

        return $data;
    }

    protected function afterSave(): void
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
        $existingVariants = $this->record->variants()->with('options')->get()
            ->keyBy(function ($variant) {
                return $variant->options
                    ->sortBy('id')
                    ->pluck('id')
                    ->implode('-');
            });

        $this->record->variants()->delete();

        foreach ($data['variants'] ?? [] as $variantData) {
            $combination = $variantData['combination'] ?? [];

            if (empty($combination)) {
                continue;
            }

            $optionIds = $this->resolveOptionIds($combination);

            $combinationKey = collect($optionIds)->sort()->implode('-');
            $existing = $existingVariants->get($combinationKey);

            $variant = $this->record->variants()->create([
                'price' => $variantData['price'] ?? $existing?->price ?? $this->record->price,
                'sale_price' => $variantData['sale_price'] ?? $existing?->sale_price,
                'expense' => $variantData['expense'] ?? $existing?->expense,
                'quantity' => $variantData['quantity'] ?? $existing?->quantity ?? 0,
                'sku' => $variantData['sku'] ?? $existing?->sku,
                'image' => $this->resolveImagePath($variantData['image'] ?? null) ?? $existing?->image,
                'is_active' => $variantData['is_active'] ?? $existing?->is_active ?? true,
            ]);

            $variant->options()->sync($optionIds);
        }
    }

    private function resolveOptionIds(array $combination): array
    {
        $optionIds = [];

        foreach ($combination as $variationName => $optionValue) {
            $option = $this->record->variations()
                ->where('name', $variationName)
                ->first()
                ?->options()
                ->where('value', $optionValue)
                ->first();

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
