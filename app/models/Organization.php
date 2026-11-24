<?php
/** D2 farmers' and fisherfolk organizations. */
class Organization extends Model
{
    protected static string $table = 'organizations';
    protected static bool $softDeletes = true;
    protected static bool $trackCreator = true;
    protected static array $fillable = ['name', 'org_type', 'registration_no', 'registering_agency', 'date_registered', 'barangay_id', 'status'];
    public const TYPES = ['farmers_assoc' => "Farmers' association", 'fisherfolk_assoc' => 'Fisherfolk association', 'cooperative' => 'Cooperative', 'other' => 'Other'];
    public const STATUSES = ['active' => 'Active', 'inactive' => 'Inactive', 'dissolved' => 'Dissolved'];
}
