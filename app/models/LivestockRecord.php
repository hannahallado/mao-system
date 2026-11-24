<?php
/** D2 livestock and poultry holdings. */
class LivestockRecord extends Model
{
    protected static string $table = 'livestock_poultry_records';
    protected static bool $softDeletes = true;
    protected static bool $trackCreator = true;
    protected static array $fillable = ['profile_id', 'commodity_id', 'barangay_id', 'animal_count', 'purpose', 'as_of_date'];
    public const PURPOSES = [
        'breeding' => 'Breeding', 'fattening' => 'Fattening', 'egg_production' => 'Egg production',
        'meat' => 'Meat', 'draft' => 'Draft / work animal', 'other' => 'Other',
    ];

    public static function forProfile(int $profileId): array
    {
        return self::raw(
            'SELECT l.*, c.name AS species FROM livestock_poultry_records l JOIN commodities c ON c.id = l.commodity_id
             WHERE l.profile_id = ? AND l.deleted_at IS NULL ORDER BY l.as_of_date DESC', [$profileId]);
    }
}
