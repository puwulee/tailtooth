<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    protected $fillable = ['question_id', 'voter_token', 'tax_id'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
