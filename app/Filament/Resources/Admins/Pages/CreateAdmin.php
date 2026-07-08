<?php

namespace App\Filament\Resources\Admins\Pages;

use App\Filament\Resources\Admins\AdminResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $data['is_active'] ??= true;

        return static::getModel()::create($data);
    }
}
