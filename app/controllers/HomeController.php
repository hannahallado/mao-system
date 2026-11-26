<?php
/**
 * 7.0 View Dashboard: totals, charts and recent activity from D1-D5.
 * Role matrix: technicians see the limited cards; the Municipal Agriculturist
 * also sees programs/assistance figures and the charts.
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $hour = (int) date('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        $full = Auth::isAgriculturist();
        $year = date('Y');
        $ready = fn (string $t) => RecordModule::tableReady($t);
        $one = fn (string $sql, array $p = []) => Model::query($sql, $p)->fetchColumn();

        // ---- cards
        $cards = [[
            'label' => 'Registered profiles', 'icon' => 'people', 'tone' => 'green', 'url' => '/farmers',
            'value' => (int) $one('SELECT COUNT(*) FROM profiles WHERE deleted_at IS NULL'),
            'note'  => count_label((int) $one('SELECT COUNT(*) FROM farming_profiles WHERE deleted_at IS NULL'), 'farmer') . ' · '
                     . number_format((int) $one('SELECT COUNT(*) FROM fishery_profiles WHERE deleted_at IS NULL')) . ' fisherfolk',
        ]];
        if ($ready('farms')) {
            $cards[] = ['label' => 'Farms', 'icon' => 'tree', 'tone' => 'teal', 'url' => '/resources?tab=farms',
                'value' => (int) $one('SELECT COUNT(*) FROM farms WHERE deleted_at IS NULL'),
                'note'  => self::num((float) $one('SELECT COALESCE(SUM(area_ha), 0) FROM farms WHERE deleted_at IS NULL')) . ' ha total area'];
        }
        if ($ready('fishery_assets')) {
            $cards[] = ['label' => 'Fishery assets', 'icon' => 'water', 'tone' => 'blue', 'url' => '/resources?tab=fishery',
                'value' => (int) $one('SELECT COUNT(*) FROM fishery_assets WHERE deleted_at IS NULL'),
                'note'  => count_label((int) $one("SELECT COUNT(*) FROM fishery_assets WHERE deleted_at IS NULL AND asset_type = 'boat'"), 'boat')];
        }
        if ($ready('production_records')) {
            $cards[] = ['label' => 'Area harvested ' . $year, 'icon' => 'graph-up-arrow', 'tone' => 'amber', 'url' => '/monitoring?tab=production', 'suffix' => 'ha',
                'value' => (float) $one('SELECT COALESCE(SUM(area_harvested_ha), 0) FROM production_records WHERE deleted_at IS NULL AND period_month LIKE ?', [$year . '-%']),
                'note'  => count_label((int) $one('SELECT COUNT(*) FROM production_records WHERE deleted_at IS NULL AND period_month LIKE ?', [$year . '-%']), 'production report')];
        }
        if ($ready('damage_reports')) {
            $cards[] = ['label' => 'Damage reports ' . $year, 'icon' => 'bug', 'tone' => 'red', 'url' => '/monitoring?tab=damage',
                'value' => (int) $one('SELECT COUNT(*) FROM damage_reports WHERE deleted_at IS NULL AND YEAR(date_occurred) = ?', [$year]),
                'note'  => self::num((float) $one('SELECT COALESCE(SUM(affected_area_ha), 0) FROM damage_reports WHERE deleted_at IS NULL AND YEAR(date_occurred) = ?', [$year])) . ' ha affected'];
        }
        if ($full) {
            $cards[] = ['label' => 'Beneficiaries served', 'icon' => 'hand-thumbs-up', 'tone' => 'purple', 'url' => '/programs',
                'value' => (int) $one("SELECT COUNT(DISTINCT profile_id) FROM program_beneficiaries WHERE status = 'served'"),
                'note'  => count_label((int) $one("SELECT COUNT(*) FROM programs WHERE deleted_at IS NULL AND status = 'ongoing'"), 'ongoing program')];
            $cards[] = ['label' => 'Assistance value ' . $year, 'icon' => 'cash-stack', 'tone' => 'orange', 'url' => '/assistance', 'prefix' => 'PHP',
                'value' => (float) $one('SELECT COALESCE(SUM(total_value), 0) FROM assistance_records WHERE YEAR(date_distributed) = ?', [$year]),
                'note'  => count_label((int) $one('SELECT COUNT(*) FROM assistance_records WHERE YEAR(date_distributed) = ?', [$year]), 'distribution'), 'money' => true];
        }

        // ---- charts (agriculturist)
        $charts = [];
        if ($full) {
            // Only barangays that have profiles (no row of empty bars)
            $byBarangay = Model::raw(
                'SELECT b.name, COUNT(p.id) n FROM barangays b JOIN profiles p ON p.barangay_id = b.id AND p.deleted_at IS NULL
                 GROUP BY b.id, b.name ORDER BY n DESC, b.name LIMIT 10');
            $charts['barangay'] = ['labels' => array_column($byBarangay, 'name'), 'values' => array_map('intval', array_column($byBarangay, 'n'))];
            if ($ready('farms')) {
                $opts = RecordModule::module('resources')['tabs']['farms']['fields']['commodity']['options'];
                $byCommodity = Model::raw('SELECT commodity, SUM(area_ha) a FROM farms WHERE deleted_at IS NULL GROUP BY commodity ORDER BY a DESC');
                $charts['commodity'] = [
                    'labels' => array_map(fn ($r) => $opts[$r['commodity']] ?? $r['commodity'], $byCommodity),
                    'values' => array_map(fn ($r) => round((float) $r['a'], 2), $byCommodity),
                ];
            }
        }

        // ---- recent activity
        $recentProfiles = Model::raw(
            "SELECT p.id, CONCAT(p.last_name, ', ', p.first_name) AS name, b.name AS barangay, p.created_at
             FROM profiles p JOIN barangays b ON b.id = p.barangay_id WHERE p.deleted_at IS NULL ORDER BY p.created_at DESC LIMIT 5");
        $recentDamage = $ready('damage_reports') ? Model::raw(
            "SELECT d.date_occurred, d.cause_type, d.calamity_name, d.commodity, d.severity, b.name AS barangay
             FROM damage_reports d JOIN barangays b ON b.id = d.barangay_id WHERE d.deleted_at IS NULL ORDER BY d.date_occurred DESC LIMIT 5") : [];

        $this->view('home/index', [
            'title'          => 'Dashboard',
            'user'           => Auth::user(),
            'greeting'       => $greeting,
            'full'           => $full,
            'cards'          => $cards,
            'charts'         => $charts,
            'recentProfiles' => $recentProfiles,
            'recentDamage'   => $recentDamage,
            'setupNeeded'    => !$ready('farms'),
            'scripts'        => $charts ? ['js/dashboard.js'] : [],
        ]);
    }

    private static function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2), '0'), '.');
    }
}
