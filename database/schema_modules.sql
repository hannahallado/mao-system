-- ============================================================================
-- MAO Information System: Resources (D2), GIS (D3) and Monitoring (D4) tables
-- ----------------------------------------------------------------------------
-- Run this once on db_mao_system (phpMyAdmin > db_mao_system > SQL > paste > Go).
-- Safe to re-run: tables use IF NOT EXISTS, and the sample rows are only added
-- when their table is empty.
-- Requires the existing tables: profiles, barangays, users.
--
-- D2 Agricultural Resource Records: farms, fishery_assets, livestock_records,
--                                   equipment, organizations (+ organization_members)
-- D3 Farm/Fishery Location (GIS):   latitude / longitude / boundary_geojson on
--                                   farms and fishery_assets
-- D4 Agricultural Activity Records: production_records, damage_reports,
--                                   trainings (+ training_participants)
-- ============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- D2: Organizations (first, because equipment can belong to one)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `organizations` (
  `id`                 int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`               varchar(150) NOT NULL,
  `org_type`           varchar(40)  NOT NULL,            -- association, cooperative, irrigators, fisherfolk, rural_women, youth
  `barangay_id`        int(10) UNSIGNED DEFAULT NULL,
  `registration_no`    varchar(60)  DEFAULT NULL,
  `registering_agency` varchar(40)  DEFAULT NULL,        -- CDA, DOLE, SEC, LGU, DA
  `date_established`   date         DEFAULT NULL,
  `contact_no`         varchar(20)  DEFAULT NULL,
  `status`             varchar(20)  NOT NULL DEFAULT 'active',
  `remarks`            text         DEFAULT NULL,
  `created_by`         int(10) UNSIGNED DEFAULT NULL,
  `created_at`         datetime DEFAULT NULL,
  `updated_at`         datetime DEFAULT NULL,
  `deleted_at`         datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_org_barangay` (`barangay_id`),
  KEY `idx_org_type` (`org_type`),
  CONSTRAINT `fk_org_barangay`   FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`),
  CONSTRAINT `fk_org_created_by` FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `organization_members` (
  `id`              int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `organization_id` int(10) UNSIGNED NOT NULL,
  `profile_id`      int(10) UNSIGNED NOT NULL,
  `position`        varchar(40) NOT NULL DEFAULT 'Member',
  `created_at`      datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_org_member` (`organization_id`, `profile_id`),
  KEY `idx_org_member_profile` (`profile_id`),
  CONSTRAINT `fk_orgmem_org`     FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orgmem_profile` FOREIGN KEY (`profile_id`)      REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- D2 + D3: Farms / parcels (with map location)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `farms` (
  `id`               int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id`       int(10) UNSIGNED NOT NULL,          -- owner/operator (shared Farmer ID, D1)
  `farm_name`        varchar(120) NOT NULL,
  `barangay_id`      int(10) UNSIGNED NOT NULL,
  `area_ha`          decimal(10,4) NOT NULL,
  `commodity`        varchar(40)  NOT NULL,              -- rice, corn, sugarcane, vegetables, ...
  `water_source`     varchar(20)  DEFAULT NULL,          -- irrigated, rainfed, upland
  `tenure`           varchar(20)  DEFAULT NULL,          -- owner, tenant, lessee, others
  `latitude`         decimal(10,7) DEFAULT NULL,
  `longitude`        decimal(10,7) DEFAULT NULL,
  `boundary_geojson` longtext     DEFAULT NULL,
  `remarks`          text         DEFAULT NULL,
  `created_by`       int(10) UNSIGNED DEFAULT NULL,
  `created_at`       datetime DEFAULT NULL,
  `updated_at`       datetime DEFAULT NULL,
  `deleted_at`       datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_farm_profile` (`profile_id`),
  KEY `idx_farm_barangay` (`barangay_id`),
  KEY `idx_farm_commodity` (`commodity`),
  CONSTRAINT `fk_farm_profile`    FOREIGN KEY (`profile_id`)  REFERENCES `profiles` (`id`),
  CONSTRAINT `fk_farm_barangay`   FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`),
  CONSTRAINT `fk_farm_created_by` FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- D2 + D3: Fishery assets (boat, gear, fishpond, aquaculture unit)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fishery_assets` (
  `id`              int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id`      int(10) UNSIGNED NOT NULL,
  `asset_type`      varchar(20)  NOT NULL,               -- boat, gear, fishpond, aquaculture
  `name`            varchar(120) NOT NULL,               -- boat name / gear / pond name
  `registration_no` varchar(40)  DEFAULT NULL,           -- boat/BFAR registration
  `barangay_id`     int(10) UNSIGNED NOT NULL,
  `size_capacity`   varchar(60)  DEFAULT NULL,           -- e.g. 3 GT, 25 ft, 1.5 ha
  `area_ha`         decimal(10,4) DEFAULT NULL,          -- fishponds / aquaculture
  `species`         varchar(60)  DEFAULT NULL,           -- bangus, tilapia, shrimp, ...
  `status`          varchar(20)  NOT NULL DEFAULT 'operational',
  `latitude`        decimal(10,7) DEFAULT NULL,
  `longitude`       decimal(10,7) DEFAULT NULL,
  `remarks`         text         DEFAULT NULL,
  `created_by`      int(10) UNSIGNED DEFAULT NULL,
  `created_at`      datetime DEFAULT NULL,
  `updated_at`      datetime DEFAULT NULL,
  `deleted_at`      datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fa_profile` (`profile_id`),
  KEY `idx_fa_barangay` (`barangay_id`),
  KEY `idx_fa_type` (`asset_type`),
  CONSTRAINT `fk_fa_profile`    FOREIGN KEY (`profile_id`)  REFERENCES `profiles` (`id`),
  CONSTRAINT `fk_fa_barangay`   FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`),
  CONSTRAINT `fk_fa_created_by` FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- D2: Livestock and poultry
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `livestock_records` (
  `id`          int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id`  int(10) UNSIGNED NOT NULL,               -- raiser
  `barangay_id` int(10) UNSIGNED NOT NULL,
  `category`    varchar(20) NOT NULL,                    -- livestock, poultry
  `species`     varchar(30) NOT NULL,                    -- carabao, cattle, goat, swine, chicken, duck, ...
  `head_count`  int(10) UNSIGNED NOT NULL,
  `purpose`     varchar(30) DEFAULT NULL,                -- breeding, fattening, draft, layer, broiler, ...
  `remarks`     text DEFAULT NULL,
  `created_by`  int(10) UNSIGNED DEFAULT NULL,
  `created_at`  datetime DEFAULT NULL,
  `updated_at`  datetime DEFAULT NULL,
  `deleted_at`  datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ls_profile` (`profile_id`),
  KEY `idx_ls_barangay` (`barangay_id`),
  KEY `idx_ls_species` (`species`),
  CONSTRAINT `fk_ls_profile`    FOREIGN KEY (`profile_id`)  REFERENCES `profiles` (`id`),
  CONSTRAINT `fk_ls_barangay`   FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`),
  CONSTRAINT `fk_ls_created_by` FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- D2: Machinery and equipment inventory (recipient: farmer OR organization)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `equipment` (
  `id`              int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `equipment_type`  varchar(40)  NOT NULL,               -- hand_tractor, four_wheel_tractor, thresher, ...
  `description`     varchar(150) DEFAULT NULL,           -- brand / model / serial
  `quantity`        int(10) UNSIGNED NOT NULL DEFAULT 1,
  `item_condition`  varchar(20)  NOT NULL DEFAULT 'good',-- good, fair, needs_repair, unserviceable
  `ownership`       varchar(20)  NOT NULL,               -- individual, organization, lgu
  `profile_id`      int(10) UNSIGNED DEFAULT NULL,
  `organization_id` int(10) UNSIGNED DEFAULT NULL,
  `barangay_id`     int(10) UNSIGNED NOT NULL,
  `date_acquired`   date         DEFAULT NULL,
  `source`          varchar(120) DEFAULT NULL,           -- program / agency that provided it
  `remarks`         text         DEFAULT NULL,
  `created_by`      int(10) UNSIGNED DEFAULT NULL,
  `created_at`      datetime DEFAULT NULL,
  `updated_at`      datetime DEFAULT NULL,
  `deleted_at`      datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_eq_type` (`equipment_type`),
  KEY `idx_eq_profile` (`profile_id`),
  KEY `idx_eq_org` (`organization_id`),
  KEY `idx_eq_barangay` (`barangay_id`),
  CONSTRAINT `fk_eq_profile`    FOREIGN KEY (`profile_id`)      REFERENCES `profiles` (`id`),
  CONSTRAINT `fk_eq_org`        FOREIGN KEY (`organization_id`) REFERENCES `organizations` (`id`),
  CONSTRAINT `fk_eq_barangay`   FOREIGN KEY (`barangay_id`)     REFERENCES `barangays` (`id`),
  CONSTRAINT `fk_eq_created_by` FOREIGN KEY (`created_by`)      REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- D4: Production monitoring (twice-monthly reporting period)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `production_records` (
  `id`                int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id`        int(10) UNSIGNED NOT NULL,
  `farm_id`           int(10) UNSIGNED DEFAULT NULL,
  `barangay_id`       int(10) UNSIGNED NOT NULL,
  `commodity`         varchar(40) NOT NULL,
  `season`            varchar(10) NOT NULL,              -- wet, dry
  `period_month`      char(7)     NOT NULL,              -- YYYY-MM
  `period_half`       tinyint(1) UNSIGNED NOT NULL,      -- 1 = 1st half, 2 = 2nd half
  `area_planted_ha`   decimal(10,4) DEFAULT NULL,
  `area_harvested_ha` decimal(10,4) DEFAULT NULL,
  `volume`            decimal(12,2) DEFAULT NULL,
  `unit`              varchar(20)  DEFAULT NULL,         -- kg, mt, cavans, sacks
  `farm_gate_price`   decimal(10,2) DEFAULT NULL,        -- PHP per unit
  `date_monitored`    date NOT NULL,
  `monitored_by`      varchar(120) DEFAULT NULL,
  `remarks`           text DEFAULT NULL,
  `created_by`        int(10) UNSIGNED DEFAULT NULL,
  `created_at`        datetime DEFAULT NULL,
  `updated_at`        datetime DEFAULT NULL,
  `deleted_at`        datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pr_profile` (`profile_id`),
  KEY `idx_pr_farm` (`farm_id`),
  KEY `idx_pr_barangay` (`barangay_id`),
  KEY `idx_pr_period` (`period_month`, `period_half`),
  KEY `idx_pr_commodity` (`commodity`),
  CONSTRAINT `fk_pr_profile`    FOREIGN KEY (`profile_id`)  REFERENCES `profiles` (`id`),
  CONSTRAINT `fk_pr_farm`       FOREIGN KEY (`farm_id`)     REFERENCES `farms` (`id`),
  CONSTRAINT `fk_pr_barangay`   FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`),
  CONSTRAINT `fk_pr_created_by` FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- D4: Damage and pest monitoring
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `damage_reports` (
  `id`               int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id`       int(10) UNSIGNED DEFAULT NULL,      -- affected farmer (optional for area-wide events)
  `farm_id`          int(10) UNSIGNED DEFAULT NULL,
  `barangay_id`      int(10) UNSIGNED NOT NULL,
  `date_occurred`    date NOT NULL,
  `cause_type`       varchar(20) NOT NULL,               -- pest, disease, typhoon, flood, drought, other
  `calamity_name`    varchar(120) DEFAULT NULL,          -- e.g. Typhoon name, pest name
  `commodity`        varchar(40) NOT NULL,
  `affected_area_ha` decimal(10,4) DEFAULT NULL,
  `estimated_loss`   decimal(14,2) DEFAULT NULL,         -- PHP
  `severity`         varchar(10) NOT NULL,               -- low, moderate, high, total
  `action_taken`     text DEFAULT NULL,
  `remarks`          text DEFAULT NULL,
  `created_by`       int(10) UNSIGNED DEFAULT NULL,
  `created_at`       datetime DEFAULT NULL,
  `updated_at`       datetime DEFAULT NULL,
  `deleted_at`       datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dm_profile` (`profile_id`),
  KEY `idx_dm_farm` (`farm_id`),
  KEY `idx_dm_barangay` (`barangay_id`),
  KEY `idx_dm_date` (`date_occurred`),
  CONSTRAINT `fk_dm_profile`    FOREIGN KEY (`profile_id`)  REFERENCES `profiles` (`id`),
  CONSTRAINT `fk_dm_farm`       FOREIGN KEY (`farm_id`)     REFERENCES `farms` (`id`),
  CONSTRAINT `fk_dm_barangay`   FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`),
  CONSTRAINT `fk_dm_created_by` FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- D4: Trainings and activities (+ participants linked to D1 profiles)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trainings` (
  `id`              int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`           varchar(150) NOT NULL,
  `activity_type`   varchar(30)  NOT NULL,               -- training, seminar, technical_assistance, field_day, meeting
  `date_held`       date NOT NULL,
  `venue`           varchar(150) DEFAULT NULL,
  `barangay_id`     int(10) UNSIGNED DEFAULT NULL,
  `topic`           varchar(150) DEFAULT NULL,
  `resource_person` varchar(120) DEFAULT NULL,
  `remarks`         text DEFAULT NULL,
  `created_by`      int(10) UNSIGNED DEFAULT NULL,
  `created_at`      datetime DEFAULT NULL,
  `updated_at`      datetime DEFAULT NULL,
  `deleted_at`      datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tr_date` (`date_held`),
  KEY `idx_tr_barangay` (`barangay_id`),
  CONSTRAINT `fk_tr_barangay`   FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`id`),
  CONSTRAINT `fk_tr_created_by` FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `training_participants` (
  `id`          int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `training_id` int(10) UNSIGNED NOT NULL,
  `profile_id`  int(10) UNSIGNED NOT NULL,
  `created_at`  datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_training_participant` (`training_id`, `profile_id`),
  KEY `idx_tp_profile` (`profile_id`),
  CONSTRAINT `fk_tp_training` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tp_profile`  FOREIGN KEY (`profile_id`)  REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- System settings (System > Settings): report header, signatories, office info
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key`   varchar(60)  NOT NULL,
  `setting_value` varchar(255) DEFAULT NULL,
  `updated_by`    int(10) UNSIGNED DEFAULT NULL,
  `updated_at`    datetime DEFAULT NULL,
  PRIMARY KEY (`setting_key`),
  CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
  ('office_name',      'Municipal Agriculture Office', NOW()),
  ('municipality',     'Pulupandan', NOW()),
  ('province',         'Negros Occidental', NOW()),
  ('contact_no',       NULL, NOW()),
  ('email',            NULL, NOW()),
  ('prepared_by',      NULL, NOW()),
  ('approved_by',      NULL, NOW()),
  ('approved_title',   'Municipal Agriculturist', NOW());

-- ---------------------------------------------------------------------------
-- Notifications (top bar bell): which notifications each user has read.
-- The notifications themselves are built from the records, so only the
-- "read" marks are stored.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notification_reads` (
  `user_id`   int(10) UNSIGNED NOT NULL,
  `notif_key` varchar(100) NOT NULL,
  `read_at`   datetime NOT NULL,
  PRIMARY KEY (`user_id`, `notif_key`),
  CONSTRAINT `fk_nr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Sample data (clearly labeled "Sample"), linked to your existing profiles and
-- barangays. Each block only runs when the table is still empty.
-- ============================================================================

SET @p1 := (SELECT id FROM profiles WHERE deleted_at IS NULL ORDER BY id LIMIT 1);
SET @p2 := COALESCE((SELECT id FROM profiles WHERE deleted_at IS NULL ORDER BY id LIMIT 1 OFFSET 1), @p1);
SET @b1 := (SELECT id FROM barangays ORDER BY id LIMIT 1);
SET @b2 := COALESCE((SELECT id FROM barangays ORDER BY id LIMIT 1 OFFSET 4), @b1);
SET @b3 := COALESCE((SELECT id FROM barangays ORDER BY id LIMIT 1 OFFSET 9), @b1);

INSERT INTO `organizations` (`name`, `org_type`, `barangay_id`, `registration_no`, `registering_agency`, `date_established`, `status`, `remarks`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT 'Sample Rice Farmers Association' AS n, 'association' AS t, @b1 AS b, 'DOLE-2019-0412' AS r, 'DOLE' AS a, '2019-03-15' AS d, 'active' AS s, 'Sample record' AS rm, NOW() AS c, NOW() AS u
  UNION ALL SELECT 'Sample Fisherfolk Cooperative', 'cooperative', @b2, 'CDA-9520-1187', 'CDA', '2016-08-02', 'active', 'Sample record', NOW(), NOW()
) x WHERE @p1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `organizations`);

INSERT INTO `organization_members` (`organization_id`, `profile_id`, `position`, `created_at`)
SELECT o.id, @p1, 'President', NOW() FROM organizations o
WHERE o.name = 'Sample Rice Farmers Association' AND @p1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `organization_members`);

INSERT INTO `farms` (`profile_id`, `farm_name`, `barangay_id`, `area_ha`, `commodity`, `water_source`, `tenure`, `latitude`, `longitude`, `remarks`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT @p1 AS p, 'Sample Rice Parcel A' AS f, @b1 AS b, 1.5000 AS a, 'rice' AS c, 'irrigated' AS w, 'owner' AS t, 10.5220000 AS la, 122.8070000 AS lo, 'Sample record' AS r, NOW() AS ca, NOW() AS ua
  UNION ALL SELECT @p2, 'Sample Corn Field', @b3, 0.7500, 'corn', 'rainfed', 'tenant', 10.5155000, 122.8190000, 'Sample record', NOW(), NOW()
) x WHERE @p1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `farms`);

INSERT INTO `fishery_assets` (`profile_id`, `asset_type`, `name`, `registration_no`, `barangay_id`, `size_capacity`, `area_ha`, `species`, `status`, `latitude`, `longitude`, `remarks`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT @p2 AS p, 'boat' AS t, 'Sample Banca "Maria"' AS n, 'BFAR-MB-0231' AS r, @b2 AS b, '3 GT, 28 ft' AS s, NULL AS a, NULL AS sp, 'operational' AS st, 10.5260000 AS la, 122.7985000 AS lo, 'Sample record' AS rm, NOW() AS c, NOW() AS u
  UNION ALL SELECT @p2, 'fishpond', 'Sample Bangus Pond', NULL, @b2, NULL, 0.8000, 'Bangus', 'operational', 10.5300000, 122.8020000, 'Sample record', NOW(), NOW()
) x WHERE @p1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `fishery_assets`);

INSERT INTO `livestock_records` (`profile_id`, `barangay_id`, `category`, `species`, `head_count`, `purpose`, `remarks`, `created_at`, `updated_at`)
SELECT * FROM (
  SELECT @p1 AS p, @b1 AS b, 'livestock' AS c, 'carabao' AS s, 2 AS h, 'draft' AS pu, 'Sample record' AS r, NOW() AS ca, NOW() AS ua
  UNION ALL SELECT @p2, @b3, 'poultry', 'chicken', 45, 'layer', 'Sample record', NOW(), NOW()
) x WHERE @p1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `livestock_records`);

INSERT INTO `equipment` (`equipment_type`, `description`, `quantity`, `item_condition`, `ownership`, `profile_id`, `organization_id`, `barangay_id`, `date_acquired`, `source`, `remarks`, `created_at`, `updated_at`)
SELECT 'hand_tractor', 'Sample hand tractor, 8 HP', 1, 'good', 'organization', NULL, o.id, @b1, '2024-06-10', 'DA RCEF Mechanization', 'Sample record', NOW(), NOW()
FROM organizations o WHERE o.name = 'Sample Rice Farmers Association' AND NOT EXISTS (SELECT 1 FROM `equipment`);

INSERT INTO `production_records` (`profile_id`, `farm_id`, `barangay_id`, `commodity`, `season`, `period_month`, `period_half`, `area_planted_ha`, `area_harvested_ha`, `volume`, `unit`, `farm_gate_price`, `date_monitored`, `monitored_by`, `remarks`, `created_at`, `updated_at`)
SELECT f.profile_id, f.id, f.barangay_id, f.commodity, 'wet', DATE_FORMAT(CURDATE(), '%Y-%m'), 1, f.area_ha, f.area_ha, 6.20, 'mt', 21.50, CURDATE(), 'Sample technician', 'Sample record', NOW(), NOW()
FROM farms f WHERE f.farm_name = 'Sample Rice Parcel A' AND NOT EXISTS (SELECT 1 FROM `production_records`);

INSERT INTO `damage_reports` (`profile_id`, `farm_id`, `barangay_id`, `date_occurred`, `cause_type`, `calamity_name`, `commodity`, `affected_area_ha`, `estimated_loss`, `severity`, `action_taken`, `remarks`, `created_at`, `updated_at`)
SELECT f.profile_id, f.id, f.barangay_id, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'pest', 'Fall armyworm', f.commodity, 0.3000, 8500.00, 'moderate', 'Recommended light trapping and field sanitation.', 'Sample record', NOW(), NOW()
FROM farms f WHERE f.farm_name = 'Sample Corn Field' AND NOT EXISTS (SELECT 1 FROM `damage_reports`);

INSERT INTO `trainings` (`title`, `activity_type`, `date_held`, `venue`, `barangay_id`, `topic`, `resource_person`, `remarks`, `created_at`, `updated_at`)
SELECT 'Sample Palay Check Training', 'training', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Barangay Hall', @b1, 'Rice crop management', 'Sample resource person', 'Sample record', NOW(), NOW()
FROM DUAL WHERE @p1 IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `trainings`);

INSERT INTO `training_participants` (`training_id`, `profile_id`, `created_at`)
SELECT t.id, p.id, NOW() FROM trainings t JOIN profiles p ON p.id IN (@p1, @p2)
WHERE t.title = 'Sample Palay Check Training' AND NOT EXISTS (SELECT 1 FROM `training_participants`);
