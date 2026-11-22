-- Process 5.0 tables (D5). Run after schema_base.sql and schema_farmers.sql.
-- Beneficiaries and assistance records point at profiles.id (one profile per person),
-- so the duplicate-assistance check works whether the person farms, fishes, or both.
USE db_mao_system;

CREATE TABLE IF NOT EXISTS assistance_types (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                    VARCHAR(80)  NOT NULL UNIQUE,
    category                ENUM('seeds','fertilizer','machinery','livestock','boat','fishing_gear','cash','insurance','livelihood','training','other') NOT NULL,
    unit                    VARCHAR(20)  NULL,
    duplicate_window_months SMALLINT UNSIGNED NULL,          -- NULL = any earlier record counts as a duplicate
    is_active               TINYINT(1) NOT NULL DEFAULT 1,
    created_at              DATETIME NULL,
    updated_at              DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS programs (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_no           VARCHAR(20)  NOT NULL UNIQUE,        -- PRG-2026-001 (IdGenerator)
    name                 VARCHAR(150) NOT NULL,
    description          TEXT NULL,
    objectives           TEXT NULL,
    source_agency        VARCHAR(60)  NOT NULL,
    year                 SMALLINT UNSIGNED NOT NULL,
    allocation_amount    DECIMAL(14,2) NULL,
    target_beneficiaries INT UNSIGNED NULL,
    start_date           DATE NULL,
    end_date             DATE NULL,
    status               ENUM('planned','ongoing','completed') NOT NULL DEFAULT 'planned',
    created_by           INT UNSIGNED NULL,
    created_at           DATETIME NULL,
    updated_at           DATETIME NULL,
    deleted_at           DATETIME NULL,
    INDEX idx_programs_status (status),
    INDEX idx_programs_year (year),
    CONSTRAINT fk_programs_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Beneficiary master list: one row per (program, profile)
CREATE TABLE IF NOT EXISTS program_beneficiaries (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id  INT UNSIGNED NOT NULL,
    profile_id  INT UNSIGNED NOT NULL,
    status      ENUM('identified','validated','approved','served','removed') NOT NULL DEFAULT 'identified',
    remarks     VARCHAR(255) NULL,
    created_by  INT UNSIGNED NULL,
    created_at  DATETIME NULL,
    updated_at  DATETIME NULL,
    UNIQUE KEY uq_program_profile (program_id, profile_id),
    INDEX idx_pb_profile (profile_id),
    CONSTRAINT fk_pb_program FOREIGN KEY (program_id) REFERENCES programs(id),
    CONSTRAINT fk_pb_profile FOREIGN KEY (profile_id) REFERENCES profiles(id),
    CONSTRAINT fk_pb_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assistance_records (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assistance_no          VARCHAR(20)  NOT NULL UNIQUE,      -- AST-2026-00001 (IdGenerator)
    program_beneficiary_id INT UNSIGNED NOT NULL,
    assistance_type_id     INT UNSIGNED NOT NULL,
    quantity               DECIMAL(12,2) NOT NULL,
    unit                   VARCHAR(20)  NULL,
    unit_value             DECIMAL(12,2) NULL,
    total_value            DECIMAL(14,2) NULL,
    date_distributed       DATE NOT NULL,
    ack_receipt_no         VARCHAR(40)  NULL,
    released_by            VARCHAR(120) NULL,                 -- releasing personnel
    duplicate_flag         TINYINT(1) NOT NULL DEFAULT 0,     -- 1 = saved after a duplicate warning
    override_reason        VARCHAR(255) NULL,
    recorded_by            INT UNSIGNED NULL,
    created_at             DATETIME NULL,
    updated_at             DATETIME NULL,
    INDEX idx_ar_beneficiary_type (program_beneficiary_id, assistance_type_id, date_distributed),
    INDEX idx_ar_date (date_distributed),
    INDEX idx_ar_flag (duplicate_flag),
    CONSTRAINT fk_ar_beneficiary FOREIGN KEY (program_beneficiary_id) REFERENCES program_beneficiaries(id),
    CONSTRAINT fk_ar_type FOREIGN KEY (assistance_type_id) REFERENCES assistance_types(id),
    CONSTRAINT fk_ar_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Starter assistance types. duplicate_window_months is the rule to CONFIRM WITH THE MAO
-- (same type within N months = duplicate; NULL = any earlier record). Edit freely.
INSERT IGNORE INTO assistance_types (name, category, unit, duplicate_window_months, created_at, updated_at) VALUES
 ('Certified Rice Seeds',   'seeds',        'bag',   6,    NOW(), NOW()),
 ('Vegetable Seeds',        'seeds',        'pack',  6,    NOW(), NOW()),
 ('Complete Fertilizer',    'fertilizer',   'bag',   6,    NOW(), NOW()),
 ('Urea Fertilizer',        'fertilizer',   'bag',   6,    NOW(), NOW()),
 ('Farm Machinery',         'machinery',    'unit',  NULL, NOW(), NOW()),
 ('Livestock (Swine/Goat)', 'livestock',    'head',  12,   NOW(), NOW()),
 ('Fishing Boat',           'boat',         'unit',  NULL, NOW(), NOW()),
 ('Fishing Gear',           'fishing_gear', 'set',   12,   NOW(), NOW()),
 ('Cash Assistance',        'cash',         'peso',  12,   NOW(), NOW()),
 ('Crop Insurance',         'insurance',    'policy',12,   NOW(), NOW()),
 ('Livelihood Project',     'livelihood',   'project',12,  NOW(), NOW());
