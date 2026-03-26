CREATE DATABASE IF NOT EXISTS `job_crawler`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `job_crawler`;

CREATE TABLE IF NOT EXISTS `jobs` (
    `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `external_id`   VARCHAR(255)     NOT NULL,
    `source`        ENUM('linkedin','indeed','custom','demo') NOT NULL,
    `title`         VARCHAR(500)     NOT NULL,
    `company`       VARCHAR(255)     NOT NULL,
    `location`      VARCHAR(255)     NULL,
    `contract_type` ENUM('CLT','PJ','Remote','Hybrid') NULL,
    `description`   TEXT             NULL,
    `requirements`  TEXT             NULL,
    `salary_range`  VARCHAR(100)     NULL,
    `url`           VARCHAR(2048)    NOT NULL,
    `published_at`  DATETIME         NULL,
    `scraped_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_notified`   TINYINT(1)       NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_source_external` (`source`, `external_id`),
    INDEX `idx_source`        (`source`),
    INDEX `idx_scraped_at`    (`scraped_at`),
    INDEX `idx_is_notified`   (`is_notified`),
    FULLTEXT INDEX `ft_title_company` (`title`, `company`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crawl_logs` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source`      VARCHAR(50)  NOT NULL,
    `keyword`     VARCHAR(200) NULL,
    `location`    VARCHAR(100) NULL,
    `status`      ENUM('running','success','failed') NOT NULL DEFAULT 'running',
    `jobs_found`  INT UNSIGNED NOT NULL DEFAULT 0,
    `jobs_new`    INT UNSIGNED NOT NULL DEFAULT 0,
    `error_msg`   TEXT         NULL,
    `started_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `finished_at` DATETIME     NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_status`     (`status`),
    INDEX `idx_started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `alert_filters` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`          VARCHAR(255) NOT NULL,
    `keywords`       JSON         NOT NULL,
    `locations`      JSON         NULL,
    `contract_types` JSON         NULL,
    `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email`     (`email`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
