-- =============================================================================
-- H.G.M. (Thailand) Co., Ltd. — 官方網站資料庫結構
-- MySQL 5.7+ / 8.0
-- 匯入方式： mysql -u root -p < sql/schema.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `hgm_thailand`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hgm_thailand`;

-- -----------------------------------------------------------------------------
-- 工具一：泰國公司註冊申辦 — 申請主表
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registration_applications` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ref_code`       VARCHAR(20)  NOT NULL COMMENT '對外參考編號',
    `company_name_zh` VARCHAR(255) DEFAULT NULL COMMENT '公司訂名（中）',
    `company_name_en` VARCHAR(255) DEFAULT NULL COMMENT '公司訂名（英）',
    `company_name_th` VARCHAR(255) DEFAULT NULL COMMENT '公司訂名（泰）',
    `capital`         VARCHAR(100) DEFAULT NULL COMMENT '註冊資本金',
    `business_scope`  TEXT         DEFAULT NULL COMMENT '營業項目',
    `contact_name`    VARCHAR(120) NOT NULL COMMENT '聯絡人',
    `contact_email`   VARCHAR(190) NOT NULL COMMENT '聯絡信箱',
    `contact_phone`   VARCHAR(60)  DEFAULT NULL COMMENT '聯絡電話',
    `notes`           TEXT         DEFAULT NULL,
    `status`          ENUM('draft','submitted','reviewing','completed') NOT NULL DEFAULT 'draft',
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ref_code` (`ref_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 工具一：資料檢核項目（每個申請 10 個項目，可勾選確認 + 上傳檔案）
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registration_documents` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id` INT UNSIGNED NOT NULL,
    `item_key`       VARCHAR(60)  NOT NULL COMMENT '項目代碼',
    `is_confirmed`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '客戶已確認',
    `file_path`      VARCHAR(255) DEFAULT NULL COMMENT '上傳檔案路徑',
    `original_name`  VARCHAR(255) DEFAULT NULL COMMENT '原始檔名',
    `note`           TEXT         DEFAULT NULL,
    `uploaded_at`    DATETIME     DEFAULT NULL,
    `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_app_item` (`application_id`, `item_key`),
    CONSTRAINT `fk_docs_app` FOREIGN KEY (`application_id`)
        REFERENCES `registration_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 工具二：BOI 申請費用試算紀錄
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `boi_estimates` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ref_code`         VARCHAR(20)  NOT NULL,
    `contact_name`     VARCHAR(120) DEFAULT NULL,
    `contact_email`    VARCHAR(190) DEFAULT NULL,
    `contact_phone`    VARCHAR(60)  DEFAULT NULL,
    `registered_capital` BIGINT     DEFAULT NULL COMMENT '註冊資本（泰銖）',
    `num_shareholders` INT          DEFAULT NULL COMMENT '股東人數',
    `num_work_permits` INT          DEFAULT NULL COMMENT '外籍工作證數量',
    `addons_json`      TEXT         DEFAULT NULL COMMENT '加購服務（JSON）',
    `total_fee`        DECIMAL(12,2) DEFAULT NULL COMMENT '估算總費用（泰銖）',
    `timeline_days`    INT          DEFAULT NULL COMMENT '估算總天數',
    `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ref` (`ref_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
