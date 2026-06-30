<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'slug', 'code', 'description',
        'status', 'require_approval', 'allow_anonymous', 'require_company', 'starts_at',
    ];

    protected $casts = [
        'require_approval' => 'boolean',
        'allow_anonymous' => 'boolean',
        'require_company' => 'boolean',
        'starts_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            $event->slug ??= static::uniqueSlug($event->title);
            $event->code ??= static::uniqueCode();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 主辦者（host 為語意別名）。 */
    public function host(): BelongsTo
    {
        return $this->user();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    public static function uniqueCode(): string
    {
        do {
            // 去掉容易混淆的字元（0/O、1/I）
            $code = strtoupper(Str::password(6, true, true, false, false));
            $code = strtr($code, ['0' => '2', 'O' => 'P', '1' => '3', 'I' => 'J', 'L' => 'M']);
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
