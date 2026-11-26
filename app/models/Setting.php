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
    ];

    private const DEFAULTS = [
        'office_name' => 'Municipal Agriculture Office', 'municipality' => 'Pulupandan',
        'province' => 'Negros Occidental', 'approved_title' => 'Municipal Agriculturist',
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
