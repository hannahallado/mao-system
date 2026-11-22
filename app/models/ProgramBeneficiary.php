<?php
/** Beneficiary master list: profiles assigned to a program. */
class ProgramBeneficiary extends Model
{
    protected static string $table = 'program_beneficiaries';
    protected static bool $trackCreator = true;
    protected static array $fillable = ['program_id', 'profile_id', 'status', 'remarks'];

    public const STATUSES = ['identified' => 'Identified', 'validated' => 'Validated', 'approved' => 'Approved', 'served' => 'Served', 'removed' => 'Removed'];
    /** Statuses a user may set by hand ('served' is set automatically when assistance is recorded). */
    public const MANUAL_STATUSES = ['identified' => 'Identified', 'validated' => 'Validated', 'approved' => 'Approved'];

    /** SQL fragment: profile + barangay + livelihood columns used by every person list in 5.0. */
    private const PERSON = 'p.last_name, p.first_name, p.middle_name, p.suffix, p.status AS profile_status, b.name AS barangay_name,
        fp.id IS NOT NULL AS has_farming, fsp.id IS NOT NULL AS has_fishery, fp.rsbsa_no AS farming_rsbsa, fsp.fishr_no';
    private const PERSON_JOINS = 'JOIN profiles p ON p.id = pb.profile_id
        LEFT JOIN barangays b ON b.id = p.barangay_id
        LEFT JOIN farming_profiles fp ON fp.id = p.id AND fp.deleted_at IS NULL
        LEFT JOIN fishery_profiles fsp ON fsp.id = p.id AND fsp.deleted_at IS NULL';

    /** Master list for one program (removed rows excluded), with how many times each person was assisted. */
    public static function forProgram(int $programId): array
    {
        return self::raw(
            'SELECT pb.*, ' . self::PERSON . ',
                    (SELECT COUNT(*) FROM assistance_records ar WHERE ar.program_beneficiary_id = pb.id) AS assistance_count
             FROM program_beneficiaries pb ' . self::PERSON_JOINS . '
             WHERE pb.program_id = ? AND pb.status <> "removed"
             ORDER BY p.last_name, p.first_name, pb.id',
            [$programId]
        );
    }

    /** One beneficiary row with program and person (used when recording a distribution). */
    public static function detail(int|string $id): ?array
    {
        return self::rawOne(
            'SELECT pb.*, pr.name AS program_name, pr.program_no, pr.status AS program_status, ' . self::PERSON . '
             FROM program_beneficiaries pb JOIN programs pr ON pr.id = pb.program_id ' . self::PERSON_JOINS . '
             WHERE pb.id = ? LIMIT 1',
            [$id]
        );
    }

    /** Active registered profiles NOT already on the program, matching a search text (the "add from profiles" picker). */
    public static function searchEligible(int $programId, string $q, int $limit = 10): array
    {
        $like = '%' . addcslashes($q, '%_\\') . '%';
        return self::raw(
            'SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, b.name AS barangay_name,
                    fp.id IS NOT NULL AS has_farming, fsp.id IS NOT NULL AS has_fishery, fp.rsbsa_no AS farming_rsbsa, fsp.fishr_no
             FROM profiles p
             LEFT JOIN barangays b ON b.id = p.barangay_id
             LEFT JOIN farming_profiles fp ON fp.id = p.id AND fp.deleted_at IS NULL
             LEFT JOIN fishery_profiles fsp ON fsp.id = p.id AND fsp.deleted_at IS NULL
             WHERE p.deleted_at IS NULL AND p.status = "active"
               AND NOT EXISTS (SELECT 1 FROM program_beneficiaries pb WHERE pb.program_id = ? AND pb.profile_id = p.id AND pb.status <> "removed")
               AND (p.last_name LIKE ? OR p.first_name LIKE ? OR CONCAT(p.first_name, " ", p.last_name) LIKE ?
                    OR CONCAT(p.last_name, ", ", p.first_name) LIKE ? OR fp.rsbsa_no LIKE ? OR fsp.fishr_no LIKE ?)
             ORDER BY p.last_name, p.first_name LIMIT ' . (int) $limit,
            [$programId, $like, $like, $like, $like, $like, $like]
        );
    }

    public static function findInProgram(int $programId, int $profileId): ?array
    {
        return self::rawOne('SELECT * FROM program_beneficiaries WHERE program_id = ? AND profile_id = ? LIMIT 1', [$programId, $profileId]);
    }

    /** Plain-text registry numbers for a person row: "RSBSA 06-45-... / FishR FR-...". */
    public static function registryLabel(array $row): string
    {
        $bits = [];
        if (!empty($row['farming_rsbsa'])) {
            $bits[] = 'RSBSA ' . $row['farming_rsbsa'];
        }
        if (!empty($row['fishr_no'])) {
            $bits[] = 'FishR ' . $row['fishr_no'];
        }
        return implode(' / ', $bits);
    }

    /** Farmer / Fisherfolk badges (HTML-safe). */
    public static function livelihoodBadges(array $row): string
    {
        $badges = [];
        if (!empty($row['has_farming'])) {
            $badges[] = Ui::badge('Farmer', 'success');
        }
        if (!empty($row['has_fishery'])) {
            $badges[] = Ui::badge('Fisherfolk', 'info');
        }
        return implode(' ', $badges) ?: '<span class="text-muted">&mdash;</span>';
    }
}
