<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'source', 'author_name', 'author_token', 'tax_id', 'company_name', 'body',
        'status', 'answer', 'answered_at', 'answered_by', 'pinned',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'pinned' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }

    /** 公開可見（已上牆，未封存）。 */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function isAi(): bool
    {
        return $this->source === 'ai';
    }

    /** 顯示用名稱（AI 題 > 公司 > 自填名 > 匿名）。 */
    public function displayName(): string
    {
        if ($this->isAi()) {
            return '主辦提供';
        }

        return $this->company_name ?: ($this->author_name ?: '匿名');
    }
}
