<?php
/** Small reference lists used by dropdowns. Cached per request. */
class Lookup
{
    private static array $cache = [];

    /** [id => name] */
    public static function barangays(): array
    {
        return self::$cache['barangays'] ??= array_column(
            Model::raw('SELECT id, name FROM barangays ORDER BY name'), 'name', 'id'
        );
    }

    /** Active system personnel available in personnel dropdowns. */
    public static function users(): array
    {
        return self::$cache['users'] ??= array_column(
            Model::raw('SELECT full_name, full_name AS label FROM users WHERE is_active = 1 ORDER BY full_name'),
            'label',
            'full_name'
        );
    }

    /** Get profile statuses */
    public static function profileStatuses(): array
    {
        return Profile::STATUSES;
    }

    /** Get farming profile statuses */
    public static function farmingStatuses(): array
    {
        return FarmingProfile::STATUSES;
    }

    /** Get fishery profile statuses */
    public static function fisheryStatuses(): array
    {
        return FisheryProfile::STATUSES;
    }
}
