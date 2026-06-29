<?php

namespace App\Enums;

/**
 * 使用者分級（RBAC）。
 */
enum UserRole: string
{
    case Platform = 'platform';        // 平台方
    case Organizer = 'organizer';      // 主辦單位
    case System = 'system';            // 系統管理
    case Referee = 'referee';          // 裁判
    case Scorekeeper = 'scorekeeper';  // 賽事記錄員
    case Participant = 'participant';   // 參賽者

    public function label(): string
    {
        return match ($this) {
            self::Platform => '平台方',
            self::Organizer => '主辦單位',
            self::System => '系統管理',
            self::Referee => '裁判',
            self::Scorekeeper => '賽事記錄員',
            self::Participant => '參賽者',
        };
    }
}
