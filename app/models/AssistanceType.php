<?php
class AssistanceType extends Model
{
    protected static string $table = 'assistance_types';
    protected static array $fillable = ['name', 'category', 'unit', 'duplicate_window_months', 'is_active'];

    public const CATEGORIES = [
        'seeds' => 'Seeds', 'fertilizer' => 'Fertilizer', 'machinery' => 'Machinery', 'livestock' => 'Livestock',
        'boat' => 'Boat', 'fishing_gear' => 'Fishing gear', 'cash' => 'Cash', 'insurance' => 'Insurance',
        'livelihood' => 'Livelihood', 'training' => 'Training', 'other' => 'Other',
    ];

    /** Active types, ordered by name. */
    public static function active(): array
    {
        return self::raw('SELECT * FROM assistance_types WHERE is_active = 1 ORDER BY name');
    }

    /** [id => name] for dropdowns. */
    public static function options(): array
    {
        return array_column(self::active(), 'name', 'id');
    }
}
