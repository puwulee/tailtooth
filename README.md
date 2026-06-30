# 活動問答 — 現場互動提問平台（Slido 風）

協會／社團活動的現場互動問答工具：開立場次（日期／主講人／主題）後，主辦者可**用 AI 依主題產生 10 題**;
觀眾用一組代碼即可加入，**提問、為問題按讚投票**（含 AI 題與觀眾提問），讚數高的問題自動排到前面;
主辦者在後台**即時審核、回覆、置頂**，畫面以輪詢自動更新。觀眾**免註冊**，可匿名;
開啟統編公司身分時，輸入統編者顯示為公司名（選填，不強制）。

## 技術棧

- PHP 8.2+ / **Laravel 13**
- SQLite（開發預設）／MySQL（正式）
- **Claude API（`anthropic-ai/sdk`，模型 `claude-opus-4-8`）** — AI 產生題目
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
2. 建立場次：**必填日期、主講人、主題**（主題是 AI 出題依據）→ 取得**加入代碼**與觀眾連結。
3. 在活動管理頁點「🤖 產生 10 題」→ 依主題用 Claude 產生題目（會取代既有 AI 題，觀眾提問保留）。
4. **回覆、置頂、封存、刪除**提問；開啟「需審核」時，提問先進待審區，核准後才上牆。

## AI 產生題目（Claude API）

開立場次並填妥主題後，後台可一鍵用 **Claude（`claude-opus-4-8`）** 依主題生成 10 題,
透過官方 PHP SDK（`anthropic-ai/sdk`）與結構化輸出取得題目。需在 `.env` 設定金鑰：

```
ANTHROPIC_API_KEY=sk-ant-...
```

- 生成的題目以 `source = ai` 標記，觀眾端顯示「🤖 主辦提供」。
- 觀眾仍可自由追加提問（`source = audience`），與 AI 題一起被投票排序。
- 實作於 `app/Services/ClaudeQuestionGenerator.php`（介面 `App\Contracts\QuestionGenerator`，可替換／測試替身）。

## 統編公司身分（選填）

每場活動可開「**啟用統編公司身分**」（`company_identity`）：

- 開啟後，觀眾**可選填** 8 碼統一編號，對照主辦者的**公司名單**顯示為公司名;
- **未輸入者仍可匿名提問與投票**（不擋人）；統編僅作為身分顯示與紀錄。
- 公司名單於 `/admin/companies` 維護（主辦者共用、跨活動）：單筆 CRUD + 批次貼上匯入。

## 路由總覽

| 範圍 | 路由 | 說明 |
|------|------|------|
| 觀眾 | `GET /` · `POST /join` | 首頁輸入代碼加入 |
| 觀眾 | `GET /e/{event}` | 活動提問牆 |
| 觀眾 | `GET /e/{event}/questions` | 提問列表 JSON（前端輪詢） |
| 觀眾 | `POST /e/{event}/questions` | 送出提問 |
| 觀眾 | `POST /e/{event}/questions/{q}/vote` | 按讚／取消（每瀏覽器限一次） |
| 觀眾 | `POST /e/{event}/verify` | 統編對照查公司名（選填身分） |
| 主辦 | `GET /admin/events` · `POST /admin/events` · `PUT /admin/events/{event}` | 場次列表／建立／更新（日期/主講人/主題必填） |
| 主辦 | `POST /admin/events/{event}/generate` | 用 AI 依主題產生 10 題 |
| 主辦 | `POST /admin/events/{event}/toggle` | 開放／關閉提問 |
| 主辦 | `GET /admin/companies` · `POST /admin/companies` · `POST /admin/companies/import` | 公司名單：列表／新增單筆／批次匯入 |
| 主辦 | `POST /admin/events/{event}/questions/{q}/answer` | 回覆 |
| 主辦 | `POST …/approve` · `…/pin` · `…/archive` · `DELETE …` | 核准／置頂／封存／刪除 |

## 資料模型

| 表 | 說明 |
|----|------|
| `events` | 場次：標題、`slug`、加入 `code`、`event_date`/`speaker`/`topic`、狀態、需審核／允許匿名／`company_identity` |
| `questions` | 提問：`source`（ai／audience）、內容、提問者（可匿名）、公司身分（`tax_id`/`company_name`）、狀態、回覆、置頂、讚數 |
| `votes` | 按讚：以 `(question_id, voter_token)` 唯一鍵確保每瀏覽器每題僅一票，記錄 `tax_id` |
| `companies` | 公司名單：`(user_id, tax_id)` 唯一，主辦者共用、跨活動 |

## 設計重點

- **場次資訊**：開立時必填日期、主講人、主題；主題作為 AI 出題依據。
- **AI 出題 + 觀眾提問並存**：AI 題與觀眾提問同列、同樣可被按讚排序。
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
