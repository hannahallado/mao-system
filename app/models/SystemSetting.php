<?php
/** Singleton site-wide settings row. */
class SystemSetting extends Model
{
    protected static string $table = 'system_settings';

    public static function current(): array
    {
        $row = self::rawOne('SELECT * FROM system_settings WHERE id = 1');
        if ($row) {
            return $row;
        }

        $year = date('Y');
        return [
            'office_name' => config('app_name', 'Municipal Agriculture Office'),
            'municipality' => 'Pulupandan',
            'report_header' => 'Pulupandan, Negros Occidental',
            'reporting_start' => $year . '-01-01',
            'reporting_end' => $year . '-12-31',
            'wet_season_label' => 'Wet season',
            'dry_season_label' => 'Dry season',
        ];
    }

    public static function productionPeriods(): array
    {
        $settings = self::current();
        return [
            'wet_season' => $settings['wet_season_label'],
            'dry_season' => $settings['dry_season_label'],
        ];
    }

    public static function saveSettings(array $settings): void
    {
        self::query(
            'INSERT INTO system_settings
                (id, office_name, municipality, report_header, reporting_start, reporting_end,
                 wet_season_label, dry_season_label, updated_by, updated_at)
             VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                office_name = VALUES(office_name),
                municipality = VALUES(municipality),
                report_header = VALUES(report_header),
                reporting_start = VALUES(reporting_start),
                reporting_end = VALUES(reporting_end),
                wet_season_label = VALUES(wet_season_label),
                dry_season_label = VALUES(dry_season_label),
                updated_by = VALUES(updated_by),
                updated_at = VALUES(updated_at)',
            [
                $settings['office_name'],
                $settings['municipality'],
                $settings['report_header'],
                $settings['reporting_start'],
                $settings['reporting_end'],
                $settings['wet_season_label'],
                $settings['dry_season_label'],
                Auth::id(),
                date('Y-m-d H:i:s'),
            ]
        );
    }
}
