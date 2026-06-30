<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'tax_id', 'name', 'active', 'note'];

    protected $casts = ['active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /** 正規化統編：去除非數字。 */
    public static function normalizeTaxId(?string $taxId): string
    {
        return preg_replace('/\D/', '', (string) $taxId);
    }

    /** 統編格式是否正確（8 碼數字）。 */
    public static function isValidFormat(?string $taxId): bool
    {
        return (bool) preg_match('/^\d{8}$/', static::normalizeTaxId($taxId));
    }
}
