# Tailtooth — 戰鬥陀螺競賽系統

戰鬥陀螺賽事營運平台：從報名收款、選手與陀螺登錄、裝備驗規、賽程編排、即時計分、
成績與賽季積分，到電子檔案公告投放的完整閉環。競賽規則對齊 **Beyblade X 官方點數制**
與 WBO 社群標準。

> 完整規劃與補強建議見 [`docs/competition-system-plan.md`](docs/competition-system-plan.md)。

## 技術棧

- PHP 8.2+ / **Laravel 13**
- MySQL／SQLite（開發）
- Redis（佇列、即時看板）— 後續導入
- **Laravel Reverb（WebSocket 即時推播）** — 看板免輪詢即時更新

### 即時推播（Reverb）

對戰計分變動會發出 `App\Events\BattleUpdated`，廣播到公開頻道 `tournament.{id}`，
觀眾看板與直播 TV 版面即時更新（連不上時自動退回輪詢）。啟動服務：

```bash
php artisan reverb:start          # 啟動 WebSocket 伺服器（VPS 上以 supervisor 常駐）
php artisan queue:work            # 佇列（照片處理、通知）
```

環境變數見 `.env`（`REVERB_*`、`BROADCAST_CONNECTION=reverb`）。

## 已實作的核心領域

| 模組 | 位置 | 說明 |
|------|------|------|
| 列舉（規則常數） | `app/Enums/` | Finish 點數、角色、組別、狀態機、賽制格式 |
| 資料模型 | `app/Models/` | 賽事/組別/選手/陀螺/對戰/回合/場地… |
| 資料表 | `database/migrations/2026_06_29_000001_create_competition_schema.php` | 完整競賽 schema |
| **計分引擎** | `app/Services/ScoringService.php` | Beyblade X 點數制（先達 N 點獲勝），逐回合計分、自動判定勝負、離線補送冪等 |
| **編排引擎** | `app/Services/BracketService.php` | 循環賽（圓桌法、輪空）、單敗淘汰（種子排序、補輪空）、衝突偵測 |
| **賽事影印** | `app/Services/TournamentService.php` | 複製賽事/範本（含組別與計分規則）快速開新活動、推廣優惠 |
| **陀螺統計** | `app/Services/BeybladeStatsService.php` | 每顆陀螺與每種組合(combo)的勝率/得分/finish 分布 |
| **排行榜** | `app/Services/LeaderboardService.php` | 賽季積分累計、冠軍大頭貼展示 |
| **照片處理** | `app/Services/BeybladePhotoService.php` | 使用者自拍上傳 → AI 去背（可插拔驅動）→ 浮水印 → 統一尺寸 |
| **裁判 SPA** | `resources/views/referee.blade.php` + `app/Http/Controllers/RefereeScoringController.php` | 離線優先計分（localStorage 佇列補送）、各場次直播訊號嵌入 |
| **報名主線** | `app/Services/RegistrationService.php` | 報名→LINE Pay 收款→光貿電子發票；額滿候補、兒童組同意、退費折讓 |
| **金流/發票驅動** | `app/Services/Payment/*`、`app/Services/Invoice/*` | 可插拔：manual／LINE Pay v3；null／光貿電子發票 |
| **上傳處理** | `app/Services/PlayerPhotoService.php`、`BeybladePhotoService` | 大頭照（正方裁切）與陀螺照（去背+浮水印+統一尺寸）分流 |
| **看板/直播** | `app/Services/BoardService.php` + `BroadcastController` | 即時看板、觀眾看板、1920×1080 直播 TV 版面 |
| **社群圖文/檔案** | `SocialCardService`、`ArchiveService` | 戰果卡圖片產生、賽事成績電子檔案（HTML/結構化） |
| **存證鏈** | `app/Services/AuditService.php` | 賽務操作以 SHA-256 串接前一筆，防竄改 |

### 重要頁面

| 頁面 | 路由 | 說明 |
|------|------|------|
| 裁判計分 SPA | `/referee/{battle}` | 離線優先、大按鈕計分 |
| 直播 TV 版面 | `/broadcast/{tournament}` | **左賽程/對戰組合、右直播訊號**，1920×1080 全螢幕投電視，含平台簡介；即時推播 |
| 觀眾看板 | `/board/{tournament}` | 手機/網頁即時戰況；即時推播 |
| 賽程編排後台 | `/admin/scheduling/{tournament}` | 產生循環賽/單敗淘汰、指派/抽選場地、衝突偵測 |
| 參賽者上傳 | `POST /api/players/{player}/avatar`、`/beyblades` | 大頭照、陀螺登錄（含自拍照，賽前必須完成才可出戰） |
| 一鍵匯出 | `GET /api/battles/{battle}/card`、`/broadcast/{tournament}/archive` | 社群戰果卡 PNG、賽事成績檔（html/json）|

### 賽程編排與字型

- 編排引擎依**賽季積分排種**，產生循環賽（圓桌法）或單敗淘汰（種子＋輪空），自動偵測選手衝突。
- 複賽/冠軍戰可**抽選場地**（以對戰 id 為種子，結果可重現並存證）。
- 社群圖文以 GD + TrueType 中文字型（`config/beyblade.php` 的 `font_path`，預設 WenQuanYi Zen Hei）渲染；
  正式環境請安裝 `fonts-wqy-zenhei` 或設 `BEY_FONT_PATH`。

### 出戰資格 gating

選手必須**先在後台登錄陀螺**（含照片處理完成）才能出戰：`Player::competeBlockers(Division)`
會檢查「足夠數量的可出戰陀螺」與「兒童組監護人同意」。

### 平台特色（與官方不同之處）

- **正版／非正版分流**：陀螺標記 `authenticity`，賽事以 `authenticity_policy`（僅正版／僅非正版／皆可）分流，可開不同性質的活動觸及更多玩家。
- **多世代支援**：Plastic／Metal／Burst／Beyblade X／其他；Xtreme Finish 僅限 Beyblade X。
- **陀螺組合戰績榜**：以 `combo_signature`（世代＋零件，順序無關）跨選手聚合，找出最強 meta。
- **裁判計分 SPA**：路由 `/referee/{battle}`，網路不穩時離線暫存、回連自動補送（`client_event_id` 冪等不重複計分）。
- **直播訊號**：賽事層級與各場次（`battles.stream_url`）皆可嵌入直播。

### 照片處理設定

去背驅動以 `BEY_BG_DRIVER` 切換：`null`（預設，不去背）或 `http`（自架 rembg / remove.bg 相容服務，設 `BEY_BG_ENDPOINT`、`BEY_BG_API_KEY`）。
輸出統一尺寸與浮水印見 `config/beyblade.php`。處理走佇列 `App\Jobs\ProcessBeybladePhoto`。

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
