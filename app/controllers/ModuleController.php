<?php
/**
 * Tabbed record modules (config/modules.php):
 *   /resources   2.0 Record Agricultural Resources (D2 + D3 locations)
 *   /monitoring  4.0 Monitor Agricultural Activities (D4)
 * Add / View / Edit / Delete happen in modals on the list, like the Registry.
 */
class ModuleController extends Controller
{
    // ------------------------------------------------------------------ routes

    public function resources(): void            { $this->index('resources'); }
    public function resourcesStore(string $tab): void                { $this->save('resources', $tab, null); }
    public function resourcesUpdate(string $tab, string $id): void   { $this->save('resources', $tab, (int) $id); }
    public function resourcesDelete(string $tab, string $id): void   { $this->delete('resources', $tab, (int) $id); }

    public function monitoring(): void           { $this->index('monitoring'); }
    public function monitoringStore(string $tab): void               { $this->save('monitoring', $tab, null); }
    public function monitoringUpdate(string $tab, string $id): void  { $this->save('monitoring', $tab, (int) $id); }
    public function monitoringDelete(string $tab, string $id): void  { $this->delete('monitoring', $tab, (int) $id); }

    /** Old one-page-per-record URLs (/farms, /monitoring/production, ...) open the matching tab. */
    public function legacy(string ...$ignored): void
    {
        $map = [
            '/farms' => '/resources?tab=farms', '/fishery' => '/resources?tab=fishery',
            '/livestock' => '/resources?tab=livestock', '/equipment' => '/resources?tab=equipment',
            '/organizations' => '/resources?tab=organizations',
            '/monitoring/production' => '/monitoring?tab=production', '/monitoring/damage' => '/monitoring?tab=damage',
            '/monitoring/training' => '/monitoring?tab=training',
        ];
        $path = current_path();
        foreach ($map as $prefix => $target) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                $this->redirect($target);
            }
        }
        $this->redirect('/home');
    }

    // ------------------------------------------------------------------ list page

    private function index(string $moduleKey): void
    {
        $module  = RecordModule::module($moduleKey);
        $tabKeys = array_keys($module['tabs']);
        $default = !empty($module['overview']) ? 'overview' : $tabKeys[0];
        $tabKey  = (string) $this->input('tab', $default);
        if ($tabKey !== 'overview' && !isset($module['tabs'][$tabKey])) {
            $tabKey = $default;
        }

        // Record count per tab, shown next to each tab label
        $tabCounts = [];
        foreach ($module['tabs'] as $key => $t) {
            $tabCounts[$key] = RecordModule::tabReady($t)
                ? (int) Model::query('SELECT COUNT(*) FROM `' . $t['table'] . '` WHERE deleted_at IS NULL')->fetchColumn()
                : null;
        }

        $data = [
            'title'       => $module['title'],
            'module'      => $module,
            'moduleKey'   => $moduleKey,
            'tabKey'      => $tabKey,
            'tabCounts'   => $tabCounts,
            'breadcrumbs' => [['Home', '/home'], [$module['title'], null]],
            'scripts'     => ['js/modules.js'],
        ];

        if ($tabKey === 'overview') {
            $data['overview'] = $this->overview($module);
            $this->view('modules/index', $data);
            return;
        }

        $tab   = $module['tabs'][$tabKey];
        $ready = RecordModule::tabReady($tab);
        $filters = ['q' => trim((string) $this->input('q', ''))];
        foreach ($tab['filters'] ?? [] as $f) {
            $filters[$f] = (string) $this->input($f, '');
        }

        // Reopen the form after a failed save (?form=add | ?form=edit&id=)
        $form   = (string) $this->input('form', '');
        $editId = $form === 'edit' ? (int) $this->input('id', 0) : 0;
        $editRow = ($ready && $editId) ? RecordModule::find($tab, $editId) : null;

        $data['breadcrumbs'] = [['Home', '/home'], [$module['title'], $module['path']], [$tab['label'], null]];
        $this->view('modules/index', $data + [
            'tab'       => $tab,
            'ready'     => $ready,
            'filters'   => $filters,
            'result'    => $ready ? RecordModule::list($tab, $filters, (int) $this->input('page', 1))
                                  : ['data' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => 15],
            'canWrite'  => $this->canWrite($tab),
            'canDelete' => Auth::isAgriculturist(),
            'openForm'  => $ready && ($form === 'add' || $editRow !== null),
            'editRow'   => $editRow,
        ]);
    }

    /** Monitoring overview: counts, this year's damage, recent entries across the three record types. */
    private function overview(array $module): array
    {
        $year = date('Y');
        $out  = ['cards' => [], 'recent' => [], 'missing' => []];
        foreach ($module['tabs'] as $key => $tab) {
            if (!RecordModule::tabReady($tab)) {
                $out['missing'][] = $tab['label'];
                continue;
            }
            $count = (int) Model::query('SELECT COUNT(*) FROM `' . $tab['table'] . '` WHERE deleted_at IS NULL')->fetchColumn();
            $card  = ['key' => $key, 'label' => $tab['label'], 'icon' => $tab['icon'], 'count' => $count, 'note' => '',
                      'tone' => ['production' => 'amber', 'damage' => 'red', 'training' => 'blue'][$key] ?? 'green'];
            if ($key === 'production') {
                $r = Model::rawOne("SELECT COALESCE(SUM(area_harvested_ha), 0) a FROM production_records WHERE deleted_at IS NULL AND period_month LIKE ?", [$year . '-%']);
                $card['note'] = rtrim(rtrim(number_format((float) $r['a'], 2), '0'), '.') . ' ha harvested in ' . $year;
            } elseif ($key === 'damage') {
                $r = Model::rawOne('SELECT COALESCE(SUM(affected_area_ha), 0) a, COALESCE(SUM(estimated_loss), 0) l FROM damage_reports WHERE deleted_at IS NULL AND YEAR(date_occurred) = ?', [$year]);
                $card['note'] = rtrim(rtrim(number_format((float) $r['a'], 2), '0'), '.') . ' ha affected, PHP ' . number_format((float) $r['l'], 2) . ' est. loss in ' . $year;
            } elseif ($key === 'training') {
                $r = Model::rawOne('SELECT COUNT(*) n FROM training_participants tp JOIN trainings t ON t.id = tp.training_id WHERE t.deleted_at IS NULL AND YEAR(t.date_held) = ?', [$year]);
                $card['note'] = count_label((int) $r['n'], 'participant') . ' in ' . $year;
            }
            $out['cards'][] = $card;
        }

        if (!$out['missing']) {
            // Codes (rice, pest, 2026-11) are turned into labels below
            $rows = Model::raw(
                "SELECT * FROM (
                    SELECT 'production' tab, pr.date_monitored d, pr.commodity a, pr.period_month b, pr.period_half c, bg.name barangay
                    FROM production_records pr JOIN barangays bg ON bg.id = pr.barangay_id WHERE pr.deleted_at IS NULL
                    UNION ALL
                    SELECT 'damage', dr.date_occurred, dr.commodity, dr.calamity_name, dr.cause_type, bg.name
                    FROM damage_reports dr JOIN barangays bg ON bg.id = dr.barangay_id WHERE dr.deleted_at IS NULL
                    UNION ALL
                    SELECT 'training', t.date_held, t.title, NULL, NULL, bg.name
                    FROM trainings t LEFT JOIN barangays bg ON bg.id = t.barangay_id WHERE t.deleted_at IS NULL
                ) x ORDER BY d DESC LIMIT 10");
            $pf = $module['tabs']['production']['fields'];
            $df = $module['tabs']['damage']['fields'];
            foreach ($rows as $r) {
                $r['title'] = match ($r['tab']) {
                    'production' => 'Production: ' . ($pf['commodity']['options'][$r['a']] ?? $r['a']) . ', '
                        . date('M Y', strtotime($r['b'] . '-01')) . ((int) $r['c'] === 1 ? ' (1st half)' : ' (2nd half)'),
                    'damage' => 'Damage: ' . ($r['b'] ?: ($df['cause_type']['options'][$r['c']] ?? $r['c']))
                        . ' on ' . strtolower($df['commodity']['options'][$r['a']] ?? $r['a']),
                    default => 'Training: ' . $r['a'],
                };
                $out['recent'][] = $r;
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ save / delete

    private function save(string $moduleKey, string $tabKey, ?int $id): void
    {
        [$module, $tab] = $this->resolve($moduleKey, $tabKey);
        $back = $module['path'] . '?tab=' . $tabKey;

        if (!RecordModule::tabReady($tab)) {
            flash('warning', 'This section is not set up yet. Run database/schema_modules.sql first.');
            $this->redirect($back);
        }
        if (!$this->canWrite($tab)) {
            abort(403, $this->sectorMessage($tab));
        }
        $old = null;
        if ($id !== null) {
            $old = RecordModule::find($tab, $id) ?? abort(404, $tab['singular'] . ' not found.');
        }

        $input = [];
        foreach ($tab['fields'] as $name => $f) {
            $input[$name] = $f['type'] === 'members' ? (array) ($_POST[$name] ?? []) : $this->input($name);
        }
        [$clean, $members, $errors] = RecordModule::validate($tab, $input);

        if ($errors) {
            flash_errors($errors);
            flash_old($input);
            $this->redirect($back . ($id ? '&form=edit&id=' . $id : '&form=add'));
        }

        if ($old) {
            RecordModule::update($tab, $old, $clean, $members);
            flash('success', $tab['singular'] . ' updated.');
        } else {
            RecordModule::insert($tab, $clean, $members);
            flash('success', $tab['singular'] . ' added.');
        }
        $this->redirect($back);
    }

    private function delete(string $moduleKey, string $tabKey, int $id): void
    {
        [$module, $tab] = $this->resolve($moduleKey, $tabKey);
        if (!Auth::isAgriculturist()) {
            abort(403, 'Only the Municipal Agriculturist can delete records.');
        }
        if (!RecordModule::tabReady($tab) || !RecordModule::find($tab, $id)) {
            abort(404, $tab['singular'] . ' not found.');
        }
        RecordModule::archive($tab, $id);
        flash('success', $tab['singular'] . ' deleted.');
        $this->redirect($module['path'] . '?tab=' . $tabKey);
    }

    // ------------------------------------------------------------------ helpers

    private function resolve(string $moduleKey, string $tabKey): array
    {
        $module = RecordModule::module($moduleKey);
        if (!isset($module['tabs'][$tabKey])) {
            abort(404);
        }
        return [$module, $module['tabs'][$tabKey]];
    }

    private function canWrite(array $tab): bool
    {
        return empty($tab['sector']) || Auth::canEditSector($tab['sector']);
    }

    private function sectorMessage(array $tab): string
    {
        $names = ['crops' => 'Crops', 'livestock_poultry' => 'Livestock and Poultry', 'fisheries' => 'Fisheries', 'all' => 'All sectors'];
        $mine  = $names[Auth::user()['sector'] ?? ''] ?? 'none';
        return 'Your sector: ' . $mine . '. You cannot add or edit ' . strtolower($tab['label']) . ' records.';
    }
}
