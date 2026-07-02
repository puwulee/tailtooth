<?php
/**
 * 全站設定與公司基本資料
 * Global configuration and company profile for the H.G.M. (Thailand) website.
 *
 * 資料庫連線資訊建議透過環境變數覆寫，避免將密碼寫死在程式碼中。
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// 站台設定 Site settings
// ---------------------------------------------------------------------------
define('SITE_NAME', 'H.G.M. (Thailand) Co., Ltd.');
define('SITE_NAME_ZH', '泰國宏冠一站式商務服務');
define('SITE_TAGLINE', 'One Stop Service · 泰國公司註冊 · BOI 申請 · 工作證簽證');

// 網站根目錄 URL 路徑（部署於子目錄時可調整，例如 "/hgm"）
define('BASE_URL', rtrim(getenv('HGM_BASE_URL') ?: '', '/'));

// 檔案上傳目錄（相對於專案根目錄）
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_MAX_BYTES', 10 * 1024 * 1024); // 單檔上限 10MB
const ALLOWED_UPLOAD_EXT = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];

// ---------------------------------------------------------------------------
// 資料庫設定 Database settings（可用環境變數覆寫）
// ---------------------------------------------------------------------------
define('DB_HOST', getenv('HGM_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('HGM_DB_PORT') ?: '3306');
define('DB_NAME', getenv('HGM_DB_NAME') ?: 'hgm_thailand');
define('DB_USER', getenv('HGM_DB_USER') ?: 'root');
define('DB_PASS', getenv('HGM_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------------------
// 公司聯絡資料 Company profile（取自名片與公司文件）
// ---------------------------------------------------------------------------
$GLOBALS['COMPANY'] = [
    'name_en'   => 'H.G.M. (Thailand) Co., Ltd.',
    'name_th'   => 'บริษัท เอช.จี.เอ็ม.(ไทยแลนด์) จำกัด',
    'name_zh'   => '宏冠（泰國）有限公司',
    'director'  => 'Mr. Wang Wax（王維）',
    'tax_id'    => '0145558000618',
    'address'   => '252/92 Unit (D) 16th Floor, Muang Thai-Phatra Complex Tower 2, Rachadaphisek Road, Huai Khwang, Bangkok 10310',
    'phones'    => [
        '辦公室 Office' => '+66 (0)2-019-8421 / +66 98-263-6441',
        '泰國 Thailand' => '+66 90-928-9090',
        '台灣 Taiwan'   => '+886 (0)933-630201',
        '客服 Service'  => '065-554-3158 / 063-082-423',
    ],
    'emails'    => ['wangwax@hgm.co.th', 'wangwax@hotmail.com'],
];

// 一站式服務項目 One Stop Service
$GLOBALS['SERVICES'] = [
    ['icon' => '⚖️', 'title' => '法律・財務・稅務諮詢', 'en' => 'Financial Advisor, Tax Consultant, Legal Counsel', 'desc' => '專任顧問提供法律、財務及稅務全方位諮詢。'],
    ['icon' => '🏢', 'title' => '公司註冊・BOI・工作證', 'en' => 'Company Registration, BOI & Work Permit', 'desc' => '專業辦理國內外公司註冊、BOI 投資優惠及工作證申辦。'],
    ['icon' => '™️', 'title' => '商標・專利・產品認證', 'en' => 'Trademark & Patent Registration', 'desc' => '協助辦理商標、專利、電器產品認證及通訊頻率申請。'],
    ['icon' => '🛂', 'title' => '工作證與簽證', 'en' => 'Work Permit & Visa', 'desc' => '辦理工作證、簽證及衛生署許可證申請。'],
    ['icon' => '📊', 'title' => '會計帳申報', 'en' => 'Accounting Services', 'desc' => '每月會計帳務處理與稅務申報。'],
];

date_default_timezone_set('Asia/Bangkok');
