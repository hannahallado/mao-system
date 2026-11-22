<?php
/** Assistance actually given (distribution log). */
class AssistanceRecord extends Model
{
    protected static string $table = 'assistance_records';
    protected static array $fillable = [
        'assistance_no', 'program_beneficiary_id', 'assistance_type_id', 'quantity', 'unit', 'unit_value', 'total_value',
        'date_distributed', 'ack_receipt_no', 'released_by', 'duplicate_flag', 'override_reason', 'recorded_by',
    ];

    private const SELECT = 'ar.*, t.name AS type_name, t.category, pr.id AS program_id, pr.name AS program_name, pr.program_no,
        p.id AS profile_id, p.last_name, p.first_name, p.middle_name, p.suffix, b.name AS barangay_name, u.full_name AS recorded_by_name';
    private const FROM = 'FROM assistance_records ar
        JOIN assistance_types t ON t.id = ar.assistance_type_id
        JOIN program_beneficiaries pb ON pb.id = ar.program_beneficiary_id
        JOIN programs pr ON pr.id = pb.program_id
        JOIN profiles p ON p.id = pb.profile_id
        LEFT JOIN barangays b ON b.id = p.barangay_id
        LEFT JOIN users u ON u.id = ar.recorded_by';

    /** Filters: q, program_id, barangay_id, date_from, date_to, flagged (1). */
    public static function search(array $filters, int $page = 1, ?int $perPage = null): array
    {
        $perPage = $perPage ?? (int) config('per_page', 15);
        $where = ['1 = 1'];
        $params = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(p.last_name LIKE ? OR p.first_name LIKE ? OR CONCAT(p.first_name, " ", p.last_name) LIKE ? OR ar.assistance_no LIKE ? OR ar.ack_receipt_no LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }
        foreach (['program_id' => 'pr.id', 'barangay_id' => 'p.barangay_id'] as $key => $col) {
            if (($filters[$key] ?? '') !== '') {
                $where[] = "$col = ?";
                $params[] = $filters[$key];
            }
        }
        if (($filters['date_from'] ?? '') !== '') {
            $where[] = 'ar.date_distributed >= ?';
            $params[] = $filters['date_from'];
        }
        if (($filters['date_to'] ?? '') !== '') {
            $where[] = 'ar.date_distributed <= ?';
            $params[] = $filters['date_to'];
        }
        if (($filters['flagged'] ?? '') === '1') {
            $where[] = 'ar.duplicate_flag = 1';
        }
        $whereSql = implode(' AND ', $where);

        $total = (int) self::query('SELECT COUNT(*) ' . self::FROM . " WHERE $whereSql", $params)->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page  = min(max(1, $page), $pages);
        $rows = self::raw(
            'SELECT ' . self::SELECT . ' ' . self::FROM . " WHERE $whereSql ORDER BY ar.date_distributed DESC, ar.id DESC LIMIT "
            . (int) $perPage . ' OFFSET ' . (int) (($page - 1) * $perPage),
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** Everything one person received, newest first (the beneficiary history timeline). */
    public static function timelineForProfile(int $profileId): array
    {
        return self::raw('SELECT ' . self::SELECT . ' ' . self::FROM . ' WHERE p.id = ? ORDER BY ar.date_distributed DESC, ar.id DESC', [$profileId]);
    }

    /** Distributions of one program, newest first. */
    public static function forProgram(int $programId, int $limit = 100): array
    {
        return self::raw('SELECT ' . self::SELECT . ' ' . self::FROM . ' WHERE pr.id = ? ORDER BY ar.date_distributed DESC, ar.id DESC LIMIT ' . (int) $limit, [$programId]);
    }

    /** [profile_id => number of assistance records] for a list of profile ids. */
    public static function countsByProfile(array $profileIds): array
    {
        if (!$profileIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($profileIds), '?'));
        $rows = self::raw(
            "SELECT pb.profile_id, COUNT(ar.id) AS n FROM program_beneficiaries pb
             JOIN assistance_records ar ON ar.program_beneficiary_id = pb.id
             WHERE pb.profile_id IN ($in) GROUP BY pb.profile_id",
            array_values($profileIds)
        );
        return array_column($rows, 'n', 'profile_id');
    }

    /** "2" / "2.5" / "1,500" - quantity without trailing zeros, plus unit. */
    public static function qty(array $row): string
    {
        $n = rtrim(rtrim(number_format((float) $row['quantity'], 2, '.', ','), '0'), '.');
        return trim($n . ' ' . ($row['unit'] ?? ''));
    }
}
