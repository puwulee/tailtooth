<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? SITE_NAME;
$activeNav = $activeNav ?? 'home';
$nav = [
    'home'         => ['首頁', BASE_URL . '/index.php'],
    'services'     => ['服務項目', BASE_URL . '/services.php'],
    'registration' => ['公司註冊申辦', BASE_URL . '/company-registration.php'],
    'boi'          => ['BOI 申請試算', BASE_URL . '/boi-application.php'],
    'contact'      => ['聯絡我們', BASE_URL . '/index.php#contact'],
];
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · <?= e(SITE_NAME) ?></title>
    <meta name="description" content="H.G.M. (Thailand) 一站式商務服務：泰國公司註冊、BOI 投資優惠申請、工作證與簽證、會計稅務。">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>window.HGM_BASE = <?= json_encode(BASE_URL) ?>;</script>
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= BASE_URL ?>/index.php">
            <span class="brand-mark">HGM</span>
            <span class="brand-text">
                <strong>H.G.M. (Thailand)</strong>
                <small>一站式商務服務 · One Stop Service</small>
            </span>
        </a>
        <button class="nav-toggle" aria-label="選單" onclick="document.body.classList.toggle('nav-open')">☰</button>
        <nav class="site-nav">
            <?php foreach ($nav as $key => [$label, $url]): ?>
                <a href="<?= e($url) ?>"<?= $activeNav === $key ? ' class="active"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>
<main>
