<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    protected $guarded = [];

    protected $casts = [
        'birthdate' => 'date',
        'guardian_consent' => 'boolean',
        'portrait_consent' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beyblades(): HasMany
    {
        return $this->hasMany(Beyblade::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /** 兒童組需監護人同意。 */
    public function isMinor(): bool
    {
        return $this->birthdate && $this->birthdate->age < 18;
    }
}
