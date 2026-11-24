<?php
/** D2 fishery assets: boats, gear, fishponds, aquaculture units. */
class FisheryAsset extends Model
{
    protected static string $table = 'fishery_assets';
    protected static bool $softDeletes = true;
    protected static bool $trackCreator = true;
    protected static array $fillable = [
        'profile_id', 'barangay_id', 'asset_type', 'description', 'registration_no', 'boat_type', 'gear_type',
        'area_sqm', 'commodity_id', 'latitude', 'longitude', 'boundary_geojson', 'status',
    ];
    public const TYPES = ['boat' => 'Boat', 'gear' => 'Fishing gear', 'fishpond' => 'Fishpond', 'aquaculture_unit' => 'Aquaculture unit'];

    public static function forProfile(int $profileId): array
    {
        return self::raw(
            'SELECT a.*, b.name AS barangay_name FROM fishery_assets a LEFT JOIN barangays b ON b.id = a.barangay_id
             WHERE a.profile_id = ? AND a.deleted_at IS NULL ORDER BY a.asset_type, a.description', [$profileId]);
    }
}
