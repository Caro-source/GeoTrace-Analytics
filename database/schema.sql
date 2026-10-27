-- ---------------------------------------------------------------
-- GeoTrace Analytics - database schema
--
-- How to run it (XAMPP):
--   phpMyAdmin -> "Import" tab -> choose this file -> "Import".
-- It creates the "geotrace" database and its tables.
-- Safe to run again: it only creates things that do not exist yet.
-- ---------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS geotrace
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE geotrace;

-- One row per lookup someone runs on the site.
CREATE TABLE IF NOT EXISTS lookups (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,

    -- What was searched
    query_input   VARCHAR(255)  NOT NULL,                          -- exactly what the user typed (cleaned)
    query_type    ENUM('ip', 'domain', 'self') NOT NULL,           -- self = "detect my IP"
    resolved_ip   VARCHAR(45)   NULL,                              -- 45 chars fits IPv6

    -- Result
    status        ENUM('success', 'fail') NOT NULL,
    error_message VARCHAR(255)  NULL,

    -- Location / network details from the geolocation API
    country       VARCHAR(100)  NULL,
    country_code  CHAR(2)       NULL,
    region        VARCHAR(100)  NULL,
    city          VARCHAR(100)  NULL,
    zip           VARCHAR(20)   NULL,
    latitude      DECIMAL(9,6)  NULL,
    longitude     DECIMAL(9,6)  NULL,
    timezone      VARCHAR(64)   NULL,
    isp           VARCHAR(255)  NULL,
    org           VARCHAR(255)  NULL,
    as_name       VARCHAR(255)  NULL,                              -- e.g. "AS15169 Google LLC"

    -- Who asked and when
    client_ip     VARCHAR(45)   NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_created_at   (created_at),
    INDEX idx_resolved_ip  (resolved_ip),
    INDEX idx_country_code (country_code),
    INDEX idx_query_type   (query_type)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
