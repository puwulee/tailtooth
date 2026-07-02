<?php
/**
 * 靜態資料定義：公司註冊檢核清單、BOI 時間表、費用試算參數。
 * 這些內容整理自 H.G.M.（泰國）提供的「成立公司資料」與「申請 BOI 時間表」文件。
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// 工具一：泰國成立公司所需資料（10 項檢核清單）
// require = 是否為送件必備項目；upload = 是否需上傳檔案
// ---------------------------------------------------------------------------
function registration_checklist(): array
{
    return [
        ['key' => 'company_name',   'no' => 1,  'require' => true,  'upload' => false,
            'title' => '公司訂名（中／英／泰）',
            'desc'  => '請提供中文、英文、泰文三種公司名稱，供泰國商業部核名。'],
        ['key' => 'capital',        'no' => 2,  'require' => true,  'upload' => false,
            'title' => '註冊資本金',
            'desc'  => '設定公司登記資本額（泰銖），影響規費與外籍工作證配額。'],
        ['key' => 'business_scope', 'no' => 3,  'require' => true,  'upload' => false,
            'title' => '營業項目',
            'desc'  => '公司主要經營業務範圍描述。'],
        ['key' => 'shareholders',   'no' => 4,  'require' => true,  'upload' => true,
            'title' => '股東持有者及持股比例（含身份證）',
            'desc'  => '發起人至少兩位自然人；第三位起可由法人（公司）持股。請附上各股東身份證。'],
        ['key' => 'passport_copy',  'no' => 5,  'require' => true,  'upload' => true,
            'title' => '股東護照複印件（加簽名）',
            'desc'  => '每位股東護照影本並親筆簽名，簽名後等同正本使用。'],
        ['key' => 'thai_director',  'no' => 6,  'require' => true,  'upload' => false,
            'title' => '泰國公司董事長',
            'desc'  => '指定泰國公司董事長人選。'],
        ['key' => 'signatory',      'no' => 7,  'require' => true,  'upload' => false,
            'title' => '有權簽名人（可多位）',
            'desc'  => '指定公司有權簽名人，可設定一位或多位及簽名條件。'],
        ['key' => 'company_seal',   'no' => 8,  'require' => false, 'upload' => true,
            'title' => '公司印章',
            'desc'  => '提供公司印章樣式，或委由本公司代刻。'],
        ['key' => 'lease',          'no' => 9,  'require' => true,  'upload' => true,
            'title' => '房屋租賃協議或房屋使用同意書',
            'desc'  => '公司登記地址之租賃合約，或屋主出具之房屋使用同意書。'],
        ['key' => 'household_reg',  'no' => 10, 'require' => true,  'upload' => true,
            'title' => '戶籍註冊資訊頁面',
            'desc'  => '登記地址之戶籍（藍皮戶口本）資訊頁影本。'],
    ];
}

// ---------------------------------------------------------------------------
// 工具二：BOI 申請時間表（依 H.G.M. 文件，共計約 135 個工作天）
// ---------------------------------------------------------------------------
function boi_timeline(): array
{
    return [
        ['no' => 1, 'title' => '討論・填寫申請表', 'en' => 'Discuss & prepare application',      'days' => 8],
        ['no' => 2, 'title' => '整理送件',         'en' => 'Organize documents',                'days' => 3],
        ['no' => 3, 'title' => '正式送件',         'en' => 'Official submission',               'days' => 0],
        ['no' => 4, 'title' => '增補件',           'en' => 'Supplementary documents',           'days' => 15],
        ['no' => 5, 'title' => '正式核送件・編碼', 'en' => 'Review & case coding',              'days' => 30],
        ['no' => 6, 'title' => '面試・增補件',     'en' => 'Interview & supplementary',         'days' => 15],
        ['no' => 7, 'title' => '進入審批議程',     'en' => 'Enter approval agenda',             'days' => 30],
        ['no' => 8, 'title' => '核准函通知書',     'en' => 'Approval letter notification',      'days' => 45],
        ['no' => 9, 'title' => '正式藍皮書身份',   'en' => 'Official BOI certificate (blue book)','days' => 0],
    ];
}

// ---------------------------------------------------------------------------
// 工具二：費用試算參數（單位：泰銖 THB）
// 註：以下為系統預設參考金額，實際報價以本公司正式報價單為準。
// ---------------------------------------------------------------------------
function boi_pricing(): array
{
    return [
        'currency' => 'THB',
        // 基本服務費
        'base' => [
            'company_registration' => ['label' => '泰國公司註冊服務費', 'fee' => 35000],
            'boi_application'      => ['label' => 'BOI 申請代辦服務費', 'fee' => 150000],
        ],
        // 政府規費：依註冊資本金比例（每 THB 之政府登記規費，示意值）
        'govt_fee_rate' => 0.0055, // 約每百萬資本 5,500 泰銖
        'govt_fee_min'  => 5500,
        'govt_fee_max'  => 275000,
        // 外籍工作證＋簽證（每人）
        'work_permit_per_person' => 25000,
        // 加購服務
        'addons' => [
            'accounting'  => ['label' => '會計帳務・每月申報（年約）', 'fee' => 60000],
            'trademark'   => ['label' => '商標註冊',                   'fee' => 18000],
            'office_addr' => ['label' => '虛擬辦公室登記地址（年）',   'fee' => 24000],
            'audit'       => ['label' => '年度財報審計',               'fee' => 30000],
        ],
        // 付款階段（百分比）
        'payment_schedule' => [
            ['label' => '簽約訂金',     'pct' => 40],
            ['label' => '正式送件',     'pct' => 35],
            ['label' => '核准取證',     'pct' => 25],
        ],
    ];
}
