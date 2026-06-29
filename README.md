# Tailtooth — 戰鬥陀螺競賽系統

戰鬥陀螺賽事營運平台：從報名收款、選手與陀螺登錄、裝備驗規、賽程編排、即時計分、
成績與賽季積分，到電子檔案公告投放的完整閉環。競賽規則對齊 **Beyblade X 官方點數制**
與 WBO 社群標準。

> 完整規劃與補強建議見 [`docs/competition-system-plan.md`](docs/competition-system-plan.md)。

## 技術棧

- PHP 8.2+ / **Laravel 13**
- MySQL／SQLite（開發）
- Redis（佇列、即時看板）— 後續導入
- Laravel Reverb（WebSocket 即時計分）— 後續導入

## 已實作的核心領域

| 模組 | 位置 | 說明 |
|------|------|------|
| 列舉（規則常數） | `app/Enums/` | Finish 點數、角色、組別、狀態機、賽制格式 |
| 資料模型 | `app/Models/` | 賽事/組別/選手/陀螺/對戰/回合/場地… |
| 資料表 | `database/migrations/2026_06_29_000001_create_competition_schema.php` | 完整競賽 schema |
| **計分引擎** | `app/Services/ScoringService.php` | Beyblade X 點數制（先達 N 點獲勝），逐回合計分、自動判定勝負 |
| **編排引擎** | `app/Services/BracketService.php` | 循環賽（圓桌法、輪空）、單敗淘汰（種子排序、補輪空）、衝突偵測 |
| **存證鏈** | `app/Services/AuditService.php` | 賽務操作以 SHA-256 串接前一筆，防竄改 |

### 計分規則（Beyblade X）

| Finish | 點數 |
|--------|:---:|
| 持續力勝 Spin | 1 |
| 出場勝 Over | 2 |
| 爆裂勝 Burst | 2 |
| 極限勝 Xtreme | 3 |

預設：3 顆一組（Deck）、單場先達 **4 點**獲勝、回合上限 180 秒。每組別可覆寫。

## 開發

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan test
```

## 路線圖

- **MVP**：報名＋LINE Pay＋光貿電子發票＋登錄＋RBAC＋裁判計分＋同意書
- **第 2 期**：編排引擎 UI＋驗規＋申訴＋離線計分＋即時看板
- **第 3 期**：賽季積分/ELO＋單顆陀螺績效榜＋電子檔案＋社群投放＋贊助管理

---

<details>
<summary>Laravel 框架說明（預設）</summary>

This project is built on the [Laravel](https://laravel.com) framework.
See the [official documentation](https://laravel.com/docs).

</details>
