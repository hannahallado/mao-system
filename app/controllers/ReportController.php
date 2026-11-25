<?php
/** Process 6.0 - Reports Center + one reusable report template. */
class ReportController extends Controller
{
    private function catalogue(): array
    {
        return require BASE_PATH . '/config/reports.php';
    }

    public function index(): void
    {
        $this->view('reports/index', [
            'title'       => 'Reports Center',
            'groups'      => $this->catalogue(),
            'breadcrumbs' => [['Home', '/home'], ['Reports', null]],
            'menu_active' => '/reports/group/farmer-resource',
        ]);
    }

    public function group(string $group): void
    {
        $cat = $this->catalogue();
        if (!isset($cat[$group])) {
            abort(404);
        }
        $this->view('reports/group', [
            'title'       => $cat[$group]['title'],
            'group'       => $cat[$group],
            'breadcrumbs' => [['Home', '/home'], ['Reports', '/reports'], [$cat[$group]['title'], null]],
            'menu_active' => '/reports/group/' . $group,
        ]);
    }

    /** One report. Replace mockup() with the reusable report-view template. */
    public function show(string $slug): void
    {
        foreach ($this->catalogue() as $gslug => $group) {
            if (isset($group['reports'][$slug])) {
                [$title, $functional] = $group['reports'][$slug];
                if ($slug === 'masterlist') {
                    $this->masterlist($title, $group['title'], $gslug);
                } else {
                    $this->reportMockup($slug, $title, $group['title'], $gslug);
                }
                return;
            }
        }
        abort(404);
    }

    private function masterlist(string $title, string $groupTitle, string $groupSlug): void
    {
        $filters = [
            'q'               => trim((string) $this->input('q', '')),
            'barangay_id'     => (string) $this->input('barangay_id', ''),
            'livelihood_type' => (string) $this->input('livelihood_type', ''),
            'status'          => (string) $this->input('status', ''),
        ];

        $rows = $this->masterlistRows($filters);
        if ((string) $this->input('export', '') === 'csv') {
            $filename = 'farmer-fisherfolk-masterlist-' . date('Y-m-d') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Sex', 'Barangay', 'Address', 'Livelihood', 'RSBSA', 'FishR', 'Contact', 'Status']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    Profile::fullName($row),
                    $row['sex'] ?? '',
                    $row['barangay_name'] ?? '',
                    $row['address_detail'] ?? '',
                    ucfirst(str_replace('_', ' ', $row['livelihood_type'])),
                    $row['farming_rsbsa'] ?: '',
                    $row['fishr_no'] ?: '',
                    $row['contact_no'] ?: '',
                    ucfirst($row['status']),
                ]);
            }
            fclose($out);
            exit;
        }

        $this->view('reports/report', [
            'title'       => $title,
            'group'       => $groupTitle,
            'group_slug'  => $groupSlug,
            'slug'        => 'masterlist',
            'status'      => 'functional',
            'filters'     => $filters,
            'rows'        => $rows,
            'barangays'   => Lookup::barangays(),
            'livelihoods' => Farmer::LIVELIHOODS,
            'statuses'    => Profile::STATUSES,
            'generated_at'=> date('F j, Y g:i A'),
            'breadcrumbs' => [
                ['Home', '/home'],
                ['Reports', '/reports'],
                [$groupTitle, '/reports/group/' . $groupSlug],
                [$title, null],
            ],
            'menu_active' => '/reports/group/' . $groupSlug,
        ]);
    }

    private function masterlistRows(array $filters): array
    {
        $where = ['p.deleted_at IS NULL'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(p.last_name LIKE ? OR p.first_name LIKE ? OR p.middle_name LIKE ?
                         OR CONCAT(p.last_name, ", ", p.first_name) LIKE ?
                         OR fp.rsbsa_no LIKE ? OR fsp.rsbsa_no LIKE ? OR fsp.fishr_no LIKE ?
                         OR p.contact_no LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like, $like, $like);
        }
        if ($filters['barangay_id'] !== '') {
            $where[] = 'p.barangay_id = ?';
            $params[] = $filters['barangay_id'];
        }
        if ($filters['status'] !== '') {
            $where[] = 'p.status = ?';
            $params[] = $filters['status'];
        }
        if ($filters['livelihood_type'] !== '') {
            if ($filters['livelihood_type'] === 'farmer') {
                $where[] = 'fp.id IS NOT NULL';
            } elseif ($filters['livelihood_type'] === 'fisherfolk') {
                $where[] = 'fsp.id IS NOT NULL';
            } elseif ($filters['livelihood_type'] === 'both') {
                $where[] = 'fp.id IS NOT NULL AND fsp.id IS NOT NULL';
            }
        }

        return Profile::raw(
            'SELECT p.*, b.name AS barangay_name,
                    fp.id IS NOT NULL AS has_farming, fp.rsbsa_no AS farming_rsbsa,
                    fsp.id IS NOT NULL AS has_fishery, fsp.rsbsa_no AS fishery_rsbsa, fsp.fishr_no,
                    CASE
                        WHEN fp.id IS NOT NULL AND fsp.id IS NOT NULL THEN "both"
                        WHEN fsp.id IS NOT NULL THEN "fisherfolk"
                        ELSE "farmer"
                    END AS livelihood_type
             FROM profiles p
             LEFT JOIN barangays b ON b.id = p.barangay_id
             LEFT JOIN farming_profiles fp ON p.id = fp.id AND fp.deleted_at IS NULL
             LEFT JOIN fishery_profiles fsp ON p.id = fsp.id AND fsp.deleted_at IS NULL
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY b.name, p.last_name, p.first_name, p.id',
            $params
        );
    }

    private function reportMockup(string $slug, string $title, string $groupTitle, string $groupSlug): void
    {
        $definitions = [
            'equipment' => [
                'subtitle' => 'Equipment and machinery inventory',
                'filters' => ['category' => 'All categories', 'status' => 'All statuses', 'barangay' => 'All locations'],
                'columns' => ['Equipment / Machinery', 'Category', 'Quantity', 'Condition', 'Assigned To', 'Location', 'Status'],
            ],
            'organizations' => [
                'subtitle' => 'Associations, cooperatives and organizations',
                'filters' => ['type' => 'All organization types', 'status' => 'All statuses', 'barangay' => 'All barangays'],
                'columns' => ['Organization', 'Type', 'Barangay', 'Members', 'Chairperson', 'Status'],
            ],
            'livestock' => [
                'subtitle' => 'Livestock and poultry records',
                'filters' => ['species' => 'All species', 'barangay' => 'All barangays', 'status' => 'All statuses'],
                'columns' => ['Owner', 'Species', 'Breed', 'Quantity', 'Barangay', 'Status'],
            ],
            'fisheries-boat' => [
                'subtitle' => 'Fishery operations and fishing vessels',
                'filters' => ['type' => 'All fishery types', 'barangay' => 'All barangays', 'status' => 'All statuses'],
                'columns' => ['Owner', 'Fishery / Boat', 'Type', 'Capacity', 'Barangay', 'Status'],
            ],
        ];
        $config = $definitions[$slug];

        $this->view('reports/report', [
            'title'       => $title,
            'group'       => $groupTitle,
            'group_slug'  => $groupSlug,
            'slug'        => $slug,
            'status'      => 'mockup',
            'subtitle'    => $config['subtitle'],
            'filter_labels' => $config['filters'],
            'mock_columns' => $config['columns'],
            'generated_at'=> date('F j, Y g:i A'),
            'breadcrumbs' => [
                ['Home', '/home'],
                ['Reports', '/reports'],
                [$groupTitle, '/reports/group/' . $groupSlug],
                [$title, null],
            ],
            'menu_active' => '/reports/group/' . $groupSlug,
        ]);
    }
}
