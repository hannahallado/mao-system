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

    /** [id => name] of active commodities, optionally limited to categories (e.g. ['livestock','poultry']). */
    public static function commodities(array $categories = []): array
    {
        $key = 'commodities:' . implode(',', $categories);
        if (!isset(self::$cache[$key])) {
            $sql = 'SELECT id, name FROM commodities WHERE is_active = 1';
            if ($categories) {
                $sql .= ' AND category IN (' . implode(',', array_fill(0, count($categories), '?')) . ')';
            }
            self::$cache[$key] = array_column(Model::raw($sql . ' ORDER BY name', $categories), 'name', 'id');
        }
        return self::$cache[$key];
    }

    public static function equipmentTypes(): array
    {
        return self::$cache['equipment_types'] ??= array_column(
            Model::raw('SELECT id, name FROM equipment_types ORDER BY name'), 'name', 'id'
        );
    }

    /** [id => "Dela Cruz, Juan P. - Barangay"] active registered people. */
    public static function profiles(): array
    {
        if (!isset(self::$cache['profiles'])) {
            $out = [];
            foreach (Model::raw('SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, b.name AS bn
                                 FROM profiles p LEFT JOIN barangays b ON b.id = p.barangay_id
                                 WHERE p.deleted_at IS NULL AND p.status = "active" ORDER BY p.last_name, p.first_name') as $r) {
                $out[$r['id']] = Profile::fullName($r) . ($r['bn'] ? ' - ' . $r['bn'] : '');
            }
            self::$cache['profiles'] = $out;
        }
        return self::$cache['profiles'];
    }

    public static function organizations(): array
    {
        return self::$cache['organizations'] ??= array_column(
            Model::raw('SELECT id, name FROM organizations WHERE deleted_at IS NULL AND status = "active" ORDER BY name'), 'name', 'id'
        );
    }

    public static function profileStatuses(): array { return Profile::STATUSES; }
    public static function farmingStatuses(): array { return FarmingProfile::STATUSES; }
    public static function fisheryStatuses(): array { return FisheryProfile::STATUSES; }
}
