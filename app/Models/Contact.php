<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'message', 'read_at', 'status', 'phone'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function getIsReadAttribute(): bool
    {
        return ! is_null($this->read_at);
    }
}
