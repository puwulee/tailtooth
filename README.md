# 活動問答 — 現場互動提問平台（Slido 風）

協會／社團活動的現場互動問答工具：觀眾用一組代碼即可加入，**匿名提問**、**為問題按讚投票**，
讚數高的問題自動排到前面；主辦者在後台**即時審核、回覆、置頂**，畫面以輪詢自動更新。

## 技術棧

- PHP 8.2+ / **Laravel 13**
- SQLite（開發預設）／MySQL（正式）
- 前端為自帶 CSS 的 Blade 樣板 + 原生 JS 輪詢，**無需 Vite 建置**即可運行

## 快速開始

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # 建立資料表與預設主辦者帳號＋示範活動
php artisan serve
```

預設主辦者帳號（可在 `.env` 以 `ADMIN_EMAIL`／`ADMIN_PASSWORD` 覆寫）：

| 欄位 | 預設值 |
|------|--------|
| Email | `admin@example.com` |
| 密碼 | `password` |

> 正式環境請務必修改預設密碼。

## 使用流程

### 觀眾（不需登入）
1. 首頁 `/` 輸入主辦者提供的**活動代碼**加入。
2. 在提問牆送出問題（可匿名），並為想聽的問題**按讚**。
3. 切換「🔥 熱門 / 🕒 最新」排序；畫面每 4 秒自動更新，含主辦回覆。

### 主辦者（需登入）
1. `/login` 登入後進入 `/admin/events`。
2. 建立活動 → 取得**加入代碼**與觀眾連結，分享給現場觀眾。
3. 在活動管理頁**回覆、置頂、封存、刪除**提問；開啟「需審核」時，提問先進待審區，核准後才上牆。

## 統編驗證（以公司身分參與）

每場活動可開啟「**需輸入統編驗證才能參與**」（`require_company`）。開啟後：

- 觀眾進入活動須先輸入 **8 碼統一編號**，系統對照主辦者的**公司名單**查出公司名，**提問與投票皆須通過驗證**。
- 驗證通過後，公司身分存於瀏覽器 `localStorage`，之後沿用；提問會以**公司名稱**顯示。
- 投票去重仍以瀏覽器為單位（每瀏覽器每題一票），統編作為參與門檻並記錄公司身分。
- 觀眾全程**免註冊**。

### 公司名單（後台共用，跨活動）

`/admin/companies` 維護**主辦者共用**的統編 ↔ 公司名對照表：

- **單筆 CRUD**：新增、即時編輯、啟用/停用、刪除。
- **批次貼上匯入**：每行「統編,公司名稱」（逗號／Tab／空白皆可分隔），重複統編自動更新名稱。
- 統編需 8 碼數字且 `(主辦者, 統編)` 不重複；停用或不在名單的統編會被擋下。

## 路由總覽

| 範圍 | 路由 | 說明 |
|------|------|------|
| 觀眾 | `GET /` · `POST /join` | 首頁輸入代碼加入 |
| 觀眾 | `GET /e/{event}` | 活動提問牆 |
| 觀眾 | `GET /e/{event}/questions` | 提問列表 JSON（前端輪詢） |
| 觀眾 | `POST /e/{event}/questions` | 送出提問 |
| 觀眾 | `POST /e/{event}/questions/{q}/vote` | 按讚／取消（每瀏覽器限一次） |
| 觀眾 | `POST /e/{event}/verify` | 統編對照查公司名（需驗證的活動） |
| 主辦 | `GET /admin/events` | 我的活動列表 |
| 主辦 | `GET /admin/companies` | 公司名單（CRUD） |
| 主辦 | `POST /admin/companies` · `POST /admin/companies/import` | 新增單筆／批次匯入 |
| 主辦 | `POST /admin/events` · `PUT /admin/events/{event}` | 建立／更新活動 |
| 主辦 | `POST /admin/events/{event}/toggle` | 開放／關閉提問 |
| 主辦 | `POST /admin/events/{event}/questions/{q}/answer` | 回覆 |
| 主辦 | `POST …/approve` · `…/pin` · `…/archive` · `DELETE …` | 核准／置頂／封存／刪除 |

## 資料模型

| 表 | 說明 |
|----|------|
| `events` | 活動：標題、`slug`、加入 `code`、狀態、是否需審核／允許匿名／需統編驗證 |
| `questions` | 提問：內容、提問者（可匿名）、公司身分（`tax_id`/`company_name`）、狀態、回覆、置頂、讚數 |
| `votes` | 按讚：以 `(question_id, voter_token)` 唯一鍵確保每瀏覽器每題僅一票，記錄 `tax_id` |
| `companies` | 公司名單：`(user_id, tax_id)` 唯一，主辦者共用、跨活動 |

## 設計重點

- **代碼加入**：每場活動產生去除易混淆字元的 6 碼代碼，觀眾免註冊即可參與。
- **匿名與身分**：觀眾瀏覽器產生隨機 `token` 存於 `localStorage`，用於投票去重與標記「自己的提問」，不需登入。
- **即時更新**：採輪詢（每 4 秒），免 WebSocket 基礎建設即可上線；網路抖動時保留畫面、下次自動補上。
- **內容治理**：可開啟「提問需審核」避免不當內容直接上牆；主辦可置頂、封存、刪除。
- **權限**：主辦者僅能管理自己建立的活動（後台動作皆驗證擁有者）。

## 測試

```bash
php artisan test
```

涵蓋：代碼加入、提問送出、審核流程、按讚去重與排序、後台權限與回覆等（`tests/Feature/QaFlowTest.php`）。
