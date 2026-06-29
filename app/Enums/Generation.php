<?php

namespace App\Enums;

enum Generation: string
{
    case Plastic = 'plastic';      // 塑膠世代
    case Metal = 'metal';          // 鋼鐵戰魂
    case Burst = 'burst';          // 爆烈世代
    case BeybladeX = 'beyblade_x'; // Beyblade X
    case Other = 'other';          // 其他/自製

    public function label(): string
    {
        return match ($this) {
            self::Plastic => 'Plastic（塑膠世代）',
            self::Metal => 'Metal（鋼鐵戰魂）',
            self::Burst => 'Burst（爆烈世代）',
            self::BeybladeX => 'Beyblade X',
            self::Other => '其他/自製',
        };
    }

    /** 此世代是否支援 Xtreme Finish（僅 Beyblade X 的 Xtreme Stadium）。 */
    public function supportsXtreme(): bool
    {
        return $this === self::BeybladeX;
    }
}
