<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $guarded = [];

    protected $casts = [
        'photos' => 'array',
        'is_sponsored' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function googleMapsUrl(): ?string
    {
        if ($this->lat === null || $this->lng === null) {
            return null;
        }
        return "https://www.google.com/maps/search/?api=1&query={$this->lat},{$this->lng}";
    }
}
