<?php

namespace App\Services\Crm;

use App\Models\Tenant\Note;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;

class CreateNoteService
{
    public function handle(
        Model $noteable,
        string $note,
        bool $isPrivate,
        TenantUser $user,
    ): Note {
        return $noteable->notes()->create([
            'created_by' => $user->id,
            'note' => $note,
            'is_private' => $isPrivate,
        ]);
    }
}
