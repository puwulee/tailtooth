<?php

namespace App\Models;

use App\Enums\AuthenticityPolicy;
use App\Enums\Generation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $guarded = [];

    protected $casts = [
        'event_date' => 'date',
        'prizes' => 'array',
        'promo' => 'array',
        'generation' => Generation::class,
        'authenticity_policy' => AuthenticityPolicy::class,
        'is_template' => 'boolean',
    ];

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function clonedFrom(): BelongsTo
    {
        return $this->belongsTo(Tournament::class, 'cloned_from_id');
    }

    public function clones(): HasMany
    {
        return $this->hasMany(Tournament::class, 'cloned_from_id');
    }
}
