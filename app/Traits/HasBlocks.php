<?php

namespace App\Traits;

use App\Models\Block;

trait HasBlocks
{
    public function blocks()
    {
        return $this->morphMany(Block::class, 'blockable')->orderBy('sort');
    }
}
