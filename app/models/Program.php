<?php
/** D5 Programs and projects. */
class Program extends Model
{
    protected static string $table = 'programs';
    protected static bool $softDeletes = true;
    protected static bool $trackCreator = true;
    protected static array $fillable = [
        'program_no', 'name', 'description', 'objectives', 'source_agency', 'year',
        'allocation_amount', 'target_beneficiaries', 'start_date', 'end_date', 'status',
    ];

    public const STATUSES = ['planned' => 'Planned', 'ongoing' => 'Ongoing', 'completed' => 'Completed'];
    public const AGENCIES = [
        'LGU' => 'LGU (Municipal Government)', 'Department of Agriculture' => 'Department of Agriculture',
        'Provincial Government' => 'Provincial Government', 'Other' => 'Other',
    ];

    /** Subqueries every list/detail needs: beneficiaries on the list and how many were actually served. */
    private const STATS = '(SELECT COUNT(*) FROM program_beneficiaries pb WHERE pb.program_id = p.id AND pb.status <> "removed") AS beneficiary_count,
        (SELECT COUNT(DISTINCT pb.id) FROM program_beneficiaries pb JOIN assistance_records ar ON ar.program_beneficiary_id = pb.id
          WHERE pb.program_id = p.id AND pb.status <> "removed") AS served_count';

    public static function withStats(int|string $id): ?array
    {
        return self::rawOne('SELECT p.*, ' . self::STATS . ' FROM programs p WHERE p.id = ? AND p.deleted_at IS NULL LIMIT 1', [$id]);
    }

    /** Filters: q, status, year, source_agency. Same return shape as every list (see Ui::pagination). */
    public static function search(array $filters, int $page = 1, ?int $perPage = null): array
    {
        $perPage = $perPage ?? (int) config('per_page', 15);
        $where = ['p.deleted_at IS NULL'];
        $params = [];
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(p.name LIKE ? OR p.program_no LIKE ? OR p.description LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        foreach (['status', 'year', 'source_agency'] as $col) {
            if (($filters[$col] ?? '') !== '') {
                $where[] = "p.$col = ?";
                $params[] = $filters[$col];
            }
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) self::query("SELECT COUNT(*) FROM programs p WHERE $whereSql", $params)->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page  = min(max(1, $page), $pages);
        $rows = self::raw(
            'SELECT p.*, ' . self::STATS . " FROM programs p WHERE $whereSql ORDER BY p.year DESC, p.id DESC LIMIT "
            . (int) $perPage . ' OFFSET ' . (int) (($page - 1) * $perPage),
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    /** [id => "PRG-2026-001 - Name"] for dropdowns (programs that can still receive distributions first). */
    public static function options(bool $openOnly = false): array
    {
        $sql = 'SELECT id, program_no, name FROM programs WHERE deleted_at IS NULL'
             . ($openOnly ? ' AND status <> "completed"' : '') . ' ORDER BY year DESC, id DESC';
        $out = [];
        foreach (self::raw($sql) as $r) {
            $out[$r['id']] = $r['program_no'] . ' - ' . $r['name'];
        }
        return $out;
    }

    /** Distinct years that have programs (for the year filter). */
    public static function years(): array
    {
        $years = array_column(self::raw('SELECT DISTINCT year FROM programs WHERE deleted_at IS NULL ORDER BY year DESC'), 'year');
        return array_combine($years, $years) ?: [];
    }
}
