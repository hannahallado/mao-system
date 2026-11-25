CREATE TABLE IF NOT EXISTS system_settings (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    office_name VARCHAR(120) NOT NULL,
    municipality VARCHAR(120) NOT NULL,
    report_header VARCHAR(200) NOT NULL,
    reporting_start DATE NOT NULL,
    reporting_end DATE NOT NULL,
    wet_season_label VARCHAR(60) NOT NULL DEFAULT 'Wet season',
    dry_season_label VARCHAR(60) NOT NULL DEFAULT 'Dry season',
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_system_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT IGNORE INTO system_settings
    (id, office_name, municipality, report_header, reporting_start, reporting_end, wet_season_label, dry_season_label, updated_at)
VALUES
    (1, 'Municipal Agriculture Office', 'Pulupandan', 'Pulupandan, Negros Occidental',
     CONCAT(YEAR(CURRENT_DATE), '-01-01'), CONCAT(YEAR(CURRENT_DATE), '-12-31'),
     'Wet season', 'Dry season', CURRENT_TIMESTAMP);
