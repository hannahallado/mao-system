-- Process 2.0 Agricultural Resources (D2). Run after schema_base / schema_farmers / schema_programs.
USE db_mao_system;

CREATE TABLE IF NOT EXISTS commodities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    category ENUM('rice','corn','sugarcane','high_value_crop','livestock','poultry','fishery') NOT NULL,
    unit VARCHAR(20) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS equipment_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS farm_parcels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    barangay_id INT UNSIGNED NOT NULL,
    commodity_id INT UNSIGNED NOT NULL,
    parcel_name VARCHAR(100) NOT NULL,
    area_ha DECIMAL(10,2) NOT NULL,
    ecosystem ENUM('irrigated','rainfed','upland') NOT NULL,
    tenure ENUM('owner','tenant','lessee','other') NOT NULL,
    irrigation_source VARCHAR(100) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    boundary_geojson LONGTEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by INT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL,
    INDEX idx_farm_profile (profile_id),
    INDEX idx_farm_brgy_com (barangay_id, commodity_id),
    CONSTRAINT fk_farm_profile FOREIGN KEY (profile_id) REFERENCES profiles(id),
    CONSTRAINT fk_farm_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id),
    CONSTRAINT fk_farm_commodity FOREIGN KEY (commodity_id) REFERENCES commodities(id),
    CONSTRAINT fk_farm_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fishery_assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    barangay_id INT UNSIGNED NOT NULL,
    asset_type ENUM('boat','gear','fishpond','aquaculture_unit') NOT NULL,
    description VARCHAR(150) NOT NULL,
    registration_no VARCHAR(40) NULL UNIQUE,
    boat_type VARCHAR(60) NULL,
    gear_type VARCHAR(60) NULL,
    area_sqm DECIMAL(12,2) NULL,
    commodity_id INT UNSIGNED NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    boundary_geojson LONGTEXT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_by INT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL,
    INDEX idx_fa_profile (profile_id),
    INDEX idx_fa_type (asset_type, barangay_id),
    CONSTRAINT fk_fa_profile FOREIGN KEY (profile_id) REFERENCES profiles(id),
    CONSTRAINT fk_fa_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id),
    CONSTRAINT fk_fa_commodity FOREIGN KEY (commodity_id) REFERENCES commodities(id),
    CONSTRAINT fk_fa_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS livestock_poultry_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_id INT UNSIGNED NOT NULL,
    commodity_id INT UNSIGNED NOT NULL,
    barangay_id INT UNSIGNED NOT NULL,
    animal_count INT UNSIGNED NOT NULL,
    purpose VARCHAR(40) NULL,
    as_of_date DATE NOT NULL,
    created_by INT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL,
    INDEX idx_lp_profile (profile_id),
    INDEX idx_lp_species (commodity_id, barangay_id),
    CONSTRAINT fk_lp_profile FOREIGN KEY (profile_id) REFERENCES profiles(id),
    CONSTRAINT fk_lp_commodity FOREIGN KEY (commodity_id) REFERENCES commodities(id),
    CONSTRAINT fk_lp_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id),
    CONSTRAINT fk_lp_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS organizations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    org_type ENUM('farmers_assoc','fisherfolk_assoc','cooperative','other') NOT NULL,
    registration_no VARCHAR(40) NULL,
    registering_agency VARCHAR(80) NULL,
    date_registered DATE NULL,
    barangay_id INT UNSIGNED NOT NULL,
    status ENUM('active','inactive','dissolved') NOT NULL DEFAULT 'active',
    created_by INT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL,
    CONSTRAINT fk_org_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id),
    CONSTRAINT fk_org_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS organization_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    organization_id INT UNSIGNED NOT NULL,
    profile_id INT UNSIGNED NOT NULL,
    position VARCHAR(60) NOT NULL DEFAULT 'Member',
    date_joined DATE NULL,
    created_by INT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL,
    UNIQUE KEY uq_org_member (organization_id, profile_id),
    CONSTRAINT fk_om_org FOREIGN KEY (organization_id) REFERENCES organizations(id),
    CONSTRAINT fk_om_profile FOREIGN KEY (profile_id) REFERENCES profiles(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS equipment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_type_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    barangay_id INT UNSIGNED NOT NULL,
    organization_id INT UNSIGNED NULL,
    profile_id INT UNSIGNED NULL,
    condition_status ENUM('good','fair','needs_repair','unserviceable') NOT NULL DEFAULT 'good',
    status ENUM('in_use','idle') NOT NULL DEFAULT 'in_use',
    provided_by_agency VARCHAR(80) NULL,
    year_granted SMALLINT UNSIGNED NULL,
    created_by INT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL, deleted_at DATETIME NULL,
    CONSTRAINT chk_equip_one_recipient CHECK (organization_id IS NULL OR profile_id IS NULL),
    CONSTRAINT fk_eq_type FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(id),
    CONSTRAINT fk_eq_barangay FOREIGN KEY (barangay_id) REFERENCES barangays(id),
    CONSTRAINT fk_eq_org FOREIGN KEY (organization_id) REFERENCES organizations(id),
    CONSTRAINT fk_eq_profile FOREIGN KEY (profile_id) REFERENCES profiles(id),
    CONSTRAINT fk_eq_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Views the GIS Map screen (MapController) already looks for: farm_records and fishery_records.
CREATE OR REPLACE VIEW farm_records AS
SELECT f.id, f.parcel_name AS farm_name, b.name AS barangay, c.name AS commodity, f.ecosystem AS category,
       f.latitude, f.longitude, f.boundary_geojson, f.profile_id AS farmer_id, f.deleted_at
FROM farm_parcels f JOIN barangays b ON b.id = f.barangay_id JOIN commodities c ON c.id = f.commodity_id;

CREATE OR REPLACE VIEW fishery_records AS
SELECT a.id, a.description AS fishery_name, b.name AS barangay, COALESCE(c.name, 'Not specified') AS commodity,
       a.asset_type AS category, a.latitude, a.longitude, a.boundary_geojson, a.profile_id AS farmer_id, a.deleted_at
FROM fishery_assets a JOIN barangays b ON b.id = a.barangay_id LEFT JOIN commodities c ON c.id = a.commodity_id;

INSERT IGNORE INTO commodities (name, category, unit) VALUES
 ('Rice','rice','kg'),('Corn','corn','kg'),('Sugarcane','sugarcane','ton'),
 ('Eggplant','high_value_crop','kg'),('Tomato','high_value_crop','kg'),('Banana','high_value_crop','kg'),('Mixed Vegetables','high_value_crop','kg'),
 ('Swine','livestock','head'),('Carabao','livestock','head'),('Cattle','livestock','head'),('Goat','livestock','head'),
 ('Chicken','poultry','head'),('Duck','poultry','head'),
 ('Tilapia','fishery','kg'),('Milkfish (Bangus)','fishery','kg'),('Shrimp','fishery','kg');

INSERT IGNORE INTO equipment_types (name) VALUES
 ('Four-wheel tractor'),('Hand tractor'),('Rice thresher'),('Rice mill'),('Corn sheller'),('Water pump'),('Sprayer'),('Hauling truck');
