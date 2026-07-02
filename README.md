# H.G.M. (Thailand) 一站式商務服務官方網站

以 **PHP + MySQL** 建置的 H.G.M. (Thailand) Co., Ltd. 官方網站，介紹公司一站式服務，並提供兩大線上申辦工具。

## 功能

| 頁面 | 說明 |
| --- | --- |
| `index.php` | 首頁：公司簡介、服務總覽、兩大工具入口、聯絡資訊 |
| `services.php` | 一站式服務項目說明（法律／財務／稅務、公司註冊、BOI、工作證簽證、商標專利、會計） |
| `company-registration.php` | **工具一**：泰國公司註冊資料檢核與上傳 |
| `boi-application.php` | **工具二**：BOI 申請工作時程表 + 費用價格試算表 |

### 工具一：泰國公司註冊 · 資料檢核與上傳
- 依成立公司所需 **10 項資料** 建立檢核清單。
- 客戶開立申辦案後取得專屬 **參考編號**，可隨時憑編號回來繼續。
- 每項可 **勾選確認** 並 **上傳檔案**（PDF／圖片／Word／Excel，單檔上限 10MB）。
- 即時 **進度條** 顯示必備項目完成度；「檢驗資料是否完成」按鈕列出尚缺項目，齊全後自動標記為已送件。

### 工具二：BOI 申請 · 時程與費用
- **工作時程表**：自討論填表至取得正式藍皮書身份，共 9 階段、約 146 個工作天。
- **費用試算表**：輸入註冊資本、外籍工作證數量、加購服務，即時估算總費用與建議付款排程（訂金 40% / 送件 35% / 取證 25%）。
- 可填聯絡資訊 **儲存試算並索取正式報價**，取得 BOI 試算參考編號。

> ⚠️ 費用為系統預設參考值（泰銖），最終以本公司正式報價單為準。

## 目錄結構

```
├── index.php / services.php / company-registration.php / boi-application.php
├── api/
│   ├── registration_save.php   # 工具一：確認/上傳/檢驗
│   └── boi_estimate.php        # 工具二：儲存試算
├── config/
│   ├── config.php              # 站台與公司設定、DB 參數
│   └── database.php            # PDO 連線
├── includes/
│   ├── header.php / footer.php # 共用版型
│   ├── functions.php           # 輔助函式（上傳、參考編號、JSON 回應）
│   └── data.php                # 檢核清單、時程表、費用參數
├── assets/css/style.css
├── assets/js/main.js
├── sql/schema.sql              # 資料庫結構
└── uploads/                    # 客戶上傳檔案（不進版控）
```

## 安裝與部署

### 1. 需求
- PHP 8.x（含 `pdo_mysql` 擴充）
- MySQL 5.7+ / MariaDB 10.x

### 2. 建立資料庫
```bash
mysql -u root -p < sql/schema.sql
```

### 3. 設定連線
可直接修改 `config/config.php`，或使用環境變數（建議正式環境採用）：

```bash
export HGM_DB_HOST=127.0.0.1
export HGM_DB_PORT=3306
export HGM_DB_NAME=hgm_thailand
export HGM_DB_USER=hgm
export HGM_DB_PASS=your_password
# 若部署於子目錄，設定 base url，例如 /hgm
export HGM_BASE_URL=
```

### 4. 本機啟動
```bash
php -S 127.0.0.1:8000
# 瀏覽 http://127.0.0.1:8000/index.php
```

### 5. 正式環境（Apache / Nginx）
- 將 DocumentRoot 指向專案根目錄。
- 確保 `uploads/` 具寫入權限：`chmod -R 775 uploads`。
- `uploads/.htaccess` 已禁止執行上傳目錄內的腳本（Apache）；Nginx 請另設定對應規則。

## 安全性
- 全部資料庫查詢採 **PDO 預備語句**，避免 SQL 注入。
- 所有輸出經 `htmlspecialchars` 跳脫。
- 上傳限制副檔名與大小，檔名重新命名並依參考編號分目錄存放。

## 資料來源
本站內容整理自 H.G.M. (Thailand) 提供之公司名片、「提供成立公司資料及信息」與「申請 BOI 時間表」文件。
