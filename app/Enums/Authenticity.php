<?php

namespace App\Enums;

/** 陀螺正版性：用於分流不同性質的賽事。 */
enum Authenticity: string
{
    case Official = 'official';   // 正版
    case Replica = 'replica';     // 非正版（副廠/自製）

    public function label(): string
    {
        return match ($this) {
            self::Official => '正版',
            self::Replica => '非正版',
        };
    }
}
