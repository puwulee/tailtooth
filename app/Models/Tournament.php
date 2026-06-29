<?php

namespace App\Models;

use App\Enums\Generation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_date' => 'date',
        'prizes' => 'array',
        'generation' => Generation::class,
    ];

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }
}
