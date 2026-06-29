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

    /** 已登錄且照片處理完成、可出戰的陀螺。 */
    public function readyBeyblades(): HasMany
    {
        return $this->hasMany(Beyblade::class)->where('photo_status', 'done');
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

    /**
     * 是否具備出戰資格：必須先登錄足夠數量、且照片處理完成的陀螺。
     * 兒童組另需監護人同意。回傳問題清單（空陣列代表通過）。
     *
     * @return array<string>
     */
    public function competeBlockers(Division $division): array
    {
        $blockers = [];

        if ($division->age_group->value === 'kids' && ! $this->guardian_consent) {
            $blockers[] = '兒童組需監護人同意';
        }

        $need = $division->deck_mode->value === 'deck' ? $division->deck_size : 1;
        $have = $this->readyBeyblades()->count();
        if ($have < $need) {
            $blockers[] = "需先登錄 {$need} 顆陀螺（含照片處理完成），目前 {$have} 顆";
        }

        return $blockers;
    }
}
