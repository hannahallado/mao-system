<?php
/** D2 farm machinery and equipment inventory. Recipient is an organization OR a farmer, never both. */
class Equipment extends Model
{
    protected static string $table = 'equipment';
    protected static bool $softDeletes = true;
    protected static bool $trackCreator = true;
    protected static array $fillable = [
        'equipment_type_id', 'quantity', 'barangay_id', 'organization_id', 'profile_id',
        'condition_status', 'status', 'provided_by_agency', 'year_granted',
    ];
    public const CONDITIONS = ['good' => 'Good', 'fair' => 'Fair', 'needs_repair' => 'Needs repair', 'unserviceable' => 'Unserviceable'];
    public const STATUSES = ['in_use' => 'In use', 'idle' => 'Idle'];
}
