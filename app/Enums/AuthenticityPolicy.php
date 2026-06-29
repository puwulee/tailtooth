<?php

namespace App\Enums;

/** 賽事允許的陀螺正版性政策。 */
enum AuthenticityPolicy: string
{
    case OfficialOnly = 'official_only'; // 僅限正版
    case ReplicaOnly = 'replica_only';   // 僅限非正版
    case Both = 'both';                  // 不限

    public function allows(Authenticity $a): bool
    {
        return match ($this) {
            self::OfficialOnly => $a === Authenticity::Official,
            self::ReplicaOnly => $a === Authenticity::Replica,
            self::Both => true,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OfficialOnly => '僅限正版',
            self::ReplicaOnly => '僅限非正版',
            self::Both => '正版/非正版皆可',
        };
    }
}
