<?php

namespace App\Models;

use App\Enums\Authenticity;
use App\Enums\Generation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beyblade extends Model
{
    protected $guarded = [];

    protected $casts = [
        'parts' => 'array',
        'generation' => Generation::class,
        'authenticity' => Authenticity::class,
    ];

    protected static function booted(): void
    {
        // 依零件組合自動產生簽章，用於聚合「同一種陀螺組合」的統計。
        static::saving(function (Beyblade $bey) {
            $bey->combo_signature = static::signatureFor($bey->generation, $bey->parts);
        });
    }

    /** 將世代＋零件組合正規化為穩定簽章（順序無關）。 */
    public static function signatureFor(Generation|string|null $generation, ?array $parts): ?string
    {
        if (empty($parts)) {
            return null;
        }
        $gen = $generation instanceof Generation ? $generation->value : (string) $generation;
        $normalized = array_map(fn ($v) => strtolower(trim((string) $v)), array_values($parts));
        sort($normalized);

        return $gen . ':' . implode('-', $normalized);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
