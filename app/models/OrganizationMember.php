<?php
/** Membership link; officers are members with a position other than "Member". */
class OrganizationMember extends Model
{
    protected static string $table = 'organization_members';
    protected static bool $trackCreator = true;
    protected static array $fillable = ['organization_id', 'profile_id', 'position', 'date_joined'];

    public static function forOrganization(int $orgId): array
    {
        return self::raw(
            'SELECT m.*, p.last_name, p.first_name, p.middle_name, p.suffix, b.name AS barangay_name
             FROM organization_members m JOIN profiles p ON p.id = m.profile_id LEFT JOIN barangays b ON b.id = p.barangay_id
             WHERE m.organization_id = ? ORDER BY (m.position = "Member"), m.position, p.last_name, p.first_name', [$orgId]);
    }

    public static function forProfile(int $profileId): array
    {
        return self::raw(
            'SELECT m.*, o.name AS org_name, o.org_type FROM organization_members m
             JOIN organizations o ON o.id = m.organization_id AND o.deleted_at IS NULL
             WHERE m.profile_id = ? ORDER BY o.name', [$profileId]);
    }
}
