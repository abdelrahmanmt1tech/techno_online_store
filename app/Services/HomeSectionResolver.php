<?php

namespace App\Services;

use App\Http\Resources\Tenant\HomeSectionResource;
use App\Models\Tenant\HomeSection;

class HomeSectionResolver
{
    public function resolve(): array
    {
        $sections = HomeSection::query()
            ->active()
            ->ordered()
            ->get();

        return HomeSectionResource::collection($sections)
            ->toArray(request());
    }
}
