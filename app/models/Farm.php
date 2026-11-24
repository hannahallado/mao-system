<?php
/** D2 farm parcels. */
class Farm extends Model
{
    protected static string $table = 'farm_parcels';
    protected static bool $softDeletes = true;
    protected static bool $trackCreator = true;
    protected static array $fillable = [
        'profile_id', 'barangay_id', 'commodity_id', 'parcel_name', 'area_ha', 'ecosystem', 'tenure',
        'irrigation_source', 'latitude', 'longitude', 'boundary_geojson', 'status',
    ];
    public const ECOSYSTEMS = ['irrigated' => 'Irrigated', 'rainfed' => 'Rainfed', 'upland' => 'Upland'];
    public const TENURES = ['owner' => 'Owner', 'tenant' => 'Tenant', 'lessee' => 'Lessee', 'other' => 'Other'];

    public static function forProfile(int $profileId): array
    {
        return self::raw(
            'SELECT f.*, b.name AS barangay_name, c.name AS commodity_name FROM farm_parcels f
             LEFT JOIN barangays b ON b.id = f.barangay_id LEFT JOIN commodities c ON c.id = f.commodity_id
             WHERE f.profile_id = ? AND f.deleted_at IS NULL ORDER BY f.parcel_name', [$profileId]);
    }
}
