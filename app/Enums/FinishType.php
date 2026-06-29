<?php

namespace App\Enums;

/**
 * 對戰勝負類型（Finish），對齊 Beyblade X 官方點數制。
 */
enum FinishType: string
{
    case Spin = 'spin';      // 持續力勝：對手先停止
    case Over = 'over';      // 出場勝：將對手打出戰鬥盤
    case Burst = 'burst';    // 爆裂勝：對手陀螺解體
    case Xtreme = 'xtreme';  // 極限勝：送入 Xtreme 專用區出場（需 Xtreme Stadium）

    /** 該 finish 取得的點數（Beyblade X 官方標準）。 */
    public function points(): int
    {
        return match ($this) {
            self::Spin => 1,
            self::Over, self::Burst => 2,
            self::Xtreme => 3,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Spin => '持續力勝 (Spin)',
            self::Over => '出場勝 (Over)',
            self::Burst => '爆裂勝 (Burst)',
            self::Xtreme => '極限勝 (Xtreme)',
        };
    }
}
