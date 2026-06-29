<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $guarded = [];
    protected $casts = ['is_secret' => 'boolean'];

    /** secret 值自動加解密。 */
    public function getDecryptedValueAttribute(): ?string
    {
        if ($this->value === null) {
            return null;
        }
        if (! $this->is_secret) {
            return $this->value;
        }
        try {
            return Crypt::decryptString($this->value);
        } catch (\Throwable) {
            return null;
        }
    }
}
