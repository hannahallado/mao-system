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
