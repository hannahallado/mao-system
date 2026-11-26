<?php
/** Key/value system settings (table `settings`, see database/schema_modules.sql). */
class Setting
{
    /** Keys the Settings tab edits: key => [label, help, max length]. */
    public const FIELDS = [
        'office_name'    => ['Office name', 'Printed at the top of every report.', 120],
        'municipality'   => ['Municipality', null, 80],
        'province'       => ['Province', null, 80],
        'contact_no'     => ['Office contact number', null, 40],
        'email'          => ['Office email', null, 120],
        'prepared_by'    => ['Reports: prepared by', 'Name printed in the "Prepared by" signature line.', 120],
        'approved_by'    => ['Reports: approved by', 'Name printed in the "Approved by" signature line.', 120],
        'approved_title' => ['Approver title', 'e.g. Municipal Agriculturist.', 80],
        'report_header'    => ['Report header', 'Extra line under the office name on printed reports, e.g. "Pulupandan, Negros Occidental".', 200],
        'reporting_start'  => ['Reporting period start', null, 10],
        'reporting_end'    => ['Reporting period end', null, 10],
        'wet_season_label' => ['Wet season label', 'Shown in Production records and reports.', 60],
        'dry_season_label' => ['Dry season label', null, 60],
    ];

    /** Keys holding YYYY-MM-DD dates. */
    public const DATE_FIELDS = ['reporting_start', 'reporting_end'];

    private const DEFAULTS = [
        'office_name' => 'Municipal Agriculture Office', 'municipality' => 'Pulupandan',
        'province' => 'Negros Occidental', 'approved_title' => 'Municipal Agriculturist',
        'wet_season_label' => 'Wet season', 'dry_season_label' => 'Dry season',
    ];

    private static ?array $cache = null;

    public static function ready(): bool
    {
        return RecordModule::tableReady('settings');
    }

    public static function all(): array
    {
        if (self::$cache === null) {
            $stored = self::ready()
                ? array_column(Model::raw('SELECT setting_key, setting_value FROM settings'), 'setting_value', 'setting_key')
                : [];
            self::$cache = array_merge(array_fill_keys(array_keys(self::FIELDS), ''), self::DEFAULTS,
                array_filter($stored, fn ($v) => $v !== null && $v !== ''));
        }
        return self::$cache;
    }

    public static function get(string $key): string
    {
        return (string) (self::all()[$key] ?? '');
    }

    public static function save(array $values): void
    {
        $now = date('Y-m-d H:i:s');
        foreach (self::FIELDS as $key => $_) {
            Model::query(
                'INSERT INTO settings (setting_key, setting_value, updated_by, updated_at) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
                [$key, $values[$key] ?? null, Auth::id(), $now]
            );
        }
        self::$cache = null;
    }
}
