<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            // 'image' => $this->image
            //     ? asset('storage/tenant'.tenant('id').'/'.$this->image)
            //     : null,
            'sort_order' => $this->sort_order,
            'show_in_header' => $this->show_in_header,
            'show_in_footer' => $this->show_in_footer,
            'content' => $this->when(
                $request->route()?->getName() === 'tenant.pages.show',
                $this->content
            ),
        ];
    }
}
