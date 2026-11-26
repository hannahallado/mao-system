<?php
/**
 * Process 6.0 report definitions. Every report returns the same shape so one
 * template (views/reports/show.php) renders, prints and exports all of them:
 *
 *   ['columns' => [[label, key, type, total?], ...],   type: text|num|dec|money|date
 *    'rows' => [...], 'needs' => [tables], 'filters' => [filter names]]
 *
 * Filters (all optional, from the query string):
 *   barangay_id, date_from, date_to, commodity, program_id, plus per-report selects.
 */
class ReportService
{
    /** Report slug => [needed tables, filters the report supports]. */
    public const DEFINITIONS = [
        'masterlist'                 => [['profiles'], ['barangay_id', 'livelihood', 'status', 'date_from', 'date_to']],
        'equipment'                  => [['equipment', 'organizations'], ['barangay_id', 'equipment_type', 'item_condition']],
        'organizations'              => [['organizations', 'organization_members'], ['barangay_id', 'org_type', 'org_status']],
        'livestock'                  => [['livestock_records'], ['barangay_id', 'category', 'species']],
        'fisheries-boat'             => [['fishery_assets'], ['barangay_id', 'asset_type']],
        'production'                 => [['production_records'], ['barangay_id', 'commodity', 'season', 'date_from', 'date_to']],
        'training'                   => [['trainings', 'training_participants'], ['barangay_id', 'activity_type', 'date_from', 'date_to']],
        'damage-pest'                => [['damage_reports'], ['barangay_id', 'commodity', 'cause_type', 'date_from', 'date_to']],
        'programs-beneficiaries'     => [['programs', 'program_beneficiaries'], ['program_id', 'barangay_id', 'beneficiary_status']],
        'assistance-history'         => [['assistance_records', 'program_beneficiaries'], ['program_id', 'barangay_id', 'q', 'date_from', 'date_to']],
        'program-accomplishment'     => [['programs', 'program_beneficiaries', 'assistance_records'], ['program_id', 'year', 'program_status']],
        'barangay-commodity-summary' => [['profiles', 'farms', 'fishery_assets', 'livestock_records', 'program_beneficiaries'], ['barangay_id', 'commodity']],
    ];

    /** Filter dropdown options. */
    public static function filterOptions(string $name): array
    {
        $res = RecordModule::module('resources')['tabs'];
        $mon = RecordModule::module('monitoring')['tabs'];
        return match ($name) {
            'barangay_id'        => Lookup::barangays(),
            'livelihood'         => Farmer::LIVELIHOODS,
            'status'             => Profile::STATUSES,
            'equipment_type'     => $res['equipment']['fields']['equipment_type']['options'],
            'item_condition'     => $res['equipment']['fields']['item_condition']['options'],
            'org_type'           => $res['organizations']['fields']['org_type']['options'],
            'org_status'         => $res['organizations']['fields']['status']['options'],
            'category'           => $res['livestock']['fields']['category']['options'],
            'species'            => $res['livestock']['fields']['species']['options'],
            'asset_type'         => $res['fishery']['fields']['asset_type']['options'],
            'commodity'          => $res['farms']['fields']['commodity']['options'],
            'season'             => $mon['production']['fields']['season']['options'],
            'activity_type'      => $mon['training']['fields']['activity_type']['options'],
            'cause_type'         => $mon['damage']['fields']['cause_type']['options'],
            'program_id'         => array_column(Model::raw('SELECT id, CONCAT(program_no, " - ", name) AS label FROM programs WHERE deleted_at IS NULL ORDER BY year DESC, name'), 'label', 'id'),
            'beneficiary_status' => ProgramBeneficiary::STATUSES,
            'program_status'     => Program::STATUSES,
            'year'               => array_combine($y = array_map('strval', range((int) date('Y'), (int) date('Y') - 5)), $y),
            default              => [],
        };
    }

    public static function filterLabel(string $name): string
    {
        return [
            'barangay_id' => 'Barangay', 'livelihood' => 'Livelihood', 'status' => 'Profile status',
            'equipment_type' => 'Equipment type', 'item_condition' => 'Condition', 'org_type' => 'Organization type',
            'org_status' => 'Status', 'category' => 'Category', 'species' => 'Species', 'asset_type' => 'Asset type',
            'commodity' => 'Commodity', 'season' => 'Season', 'activity_type' => 'Activity type', 'cause_type' => 'Cause',
            'program_id' => 'Program', 'beneficiary_status' => 'Beneficiary status', 'program_status' => 'Program status',
            'year' => 'Year', 'date_from' => 'From', 'date_to' => 'To', 'q' => 'Name',
        ][$name] ?? ucfirst($name);
    }

    /** Missing tables for a report ([] when it can run). */
    public static function missingTables(string $slug): array
    {
        return array_values(array_filter(self::DEFINITIONS[$slug][0], fn ($t) => !RecordModule::tableReady($t)));
    }

    /** Clean the filters a report supports (selects must be valid options, dates valid). */
    public static function cleanFilters(string $slug, array $input): array
    {
        $out = [];
        foreach (self::DEFINITIONS[$slug][1] as $name) {
            $v = trim((string) ($input[$name] ?? ''));
            if ($v === '') {
                $out[$name] = '';
            } elseif (in_array($name, ['date_from', 'date_to'], true)) {
                $d = DateTime::createFromFormat('Y-m-d', $v);
                $out[$name] = ($d && $d->format('Y-m-d') === $v) ? $v : '';
            } elseif ($name === 'q') {
                $out[$name] = mb_substr($v, 0, 80);
            } else {
                $out[$name] = array_key_exists($v, self::filterOptions($name)) ? $v : '';
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------ reports

    public static function run(string $slug, array $f): array
    {
        $method = 'r' . str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));
        return self::$method($f);
    }

    /** WHERE builder: [sql parts, params]. */
    private static function where(array $f, array $map): array
    {
        $parts = [];
        $params = [];
        foreach ($map as $name => $sql) {
            if (($f[$name] ?? '') === '') {
                continue;
            }
            $parts[] = $sql;
            $params[] = $name === 'q' ? '%' . $f[$name] . '%' : $f[$name];
        }
        return [$parts, $params];
    }

    private static function sql(string $base, array $fixed, array $parts, string $tail): string
    {
        $all = array_merge($fixed, $parts);
        return $base . ($all ? ' WHERE ' . implode(' AND ', $all) : '') . ' ' . $tail;
    }

    private static function rMasterlist(array $f): array
    {
        [$w, $p] = self::where($f, [
            'barangay_id' => 'p.barangay_id = ?', 'status' => 'p.status = ?',
            'date_from' => 'DATE(p.created_at) >= ?', 'date_to' => 'DATE(p.created_at) <= ?',
        ]);
        $live = match ($f['livelihood'] ?? '') {
            'farmer'     => ['fp.id IS NOT NULL AND fs.id IS NULL'],
            'fisherfolk' => ['fs.id IS NOT NULL AND fp.id IS NULL'],
            'both'       => ['fp.id IS NOT NULL AND fs.id IS NOT NULL'],
            default      => [],
        };
        $rows = Model::raw(self::sql(
            "SELECT CONCAT(p.last_name, ', ', p.first_name, IF(p.middle_name IS NULL OR p.middle_name = '', '', CONCAT(' ', LEFT(p.middle_name, 1), '.')),
                           IF(p.suffix IS NULL OR p.suffix = '', '', CONCAT(' ', p.suffix))) AS name,
                    p.sex, p.birthdate, b.name AS barangay, p.address_detail, p.contact_no,
                    CASE WHEN fp.id IS NOT NULL AND fs.id IS NOT NULL THEN 'Farmer, Fisherfolk'
                         WHEN fp.id IS NOT NULL THEN 'Farmer' WHEN fs.id IS NOT NULL THEN 'Fisherfolk' ELSE '' END AS livelihood,
                    COALESCE(fp.rsbsa_no, fs.rsbsa_no) AS rsbsa_no, fs.fishr_no, p.status, DATE(p.created_at) AS registered
             FROM profiles p
             JOIN barangays b ON b.id = p.barangay_id
             LEFT JOIN farming_profiles fp ON fp.id = p.id AND fp.deleted_at IS NULL
             LEFT JOIN fishery_profiles fs ON fs.id = p.id AND fs.deleted_at IS NULL",
            array_merge(['p.deleted_at IS NULL'], $live), $w, 'ORDER BY b.name, p.last_name, p.first_name'), $p);
        foreach ($rows as &$r) {
            $r['status'] = Profile::STATUSES[$r['status']] ?? $r['status'];
        }
        return ['rows' => $rows, 'columns' => [
            ['Name', 'name'], ['Sex', 'sex'], ['Birthdate', 'birthdate', 'date'], ['Barangay', 'barangay'],
            ['Address', 'address_detail'], ['Contact', 'contact_no'], ['Livelihood', 'livelihood'],
            ['RSBSA no.', 'rsbsa_no'], ['FishR no.', 'fishr_no'], ['Status', 'status'], ['Registered', 'registered', 'date'],
        ]];
    }

    private static function rEquipment(array $f): array
    {
        [$w, $p] = self::where($f, ['barangay_id' => 'e.barangay_id = ?', 'equipment_type' => 'e.equipment_type = ?', 'item_condition' => 'e.item_condition = ?']);
        $rows = Model::raw(self::sql(
            "SELECT e.equipment_type, e.description, e.quantity, e.item_condition, e.ownership, b.name AS barangay,
                    CASE e.ownership WHEN 'organization' THEN o.name WHEN 'lgu' THEN 'LGU / MAO' ELSE CONCAT(p.last_name, ', ', p.first_name) END AS holder,
                    e.date_acquired, e.source
             FROM equipment e JOIN barangays b ON b.id = e.barangay_id
             LEFT JOIN profiles p ON p.id = e.profile_id LEFT JOIN organizations o ON o.id = e.organization_id",
            ['e.deleted_at IS NULL'], $w, 'ORDER BY e.equipment_type, b.name'), $p);
        $fields = RecordModule::module('resources')['tabs']['equipment']['fields'];
        foreach ($rows as &$r) {
            $r['equipment_type'] = $fields['equipment_type']['options'][$r['equipment_type']] ?? $r['equipment_type'];
            $r['item_condition'] = $fields['item_condition']['options'][$r['item_condition']] ?? $r['item_condition'];
        }
        return ['rows' => $rows, 'columns' => [
            ['Equipment', 'equipment_type'], ['Description', 'description'], ['Qty', 'quantity', 'num', true],
            ['Condition', 'item_condition'], ['Held by', 'holder'], ['Barangay', 'barangay'],
            ['Date acquired', 'date_acquired', 'date'], ['Source / program', 'source'],
        ]];
    }

    private static function rOrganizations(array $f): array
    {
        [$w, $p] = self::where($f, ['barangay_id' => 'o.barangay_id = ?', 'org_type' => 'o.org_type = ?', 'org_status' => 'o.status = ?']);
        $rows = Model::raw(self::sql(
            "SELECT o.name, o.org_type, b.name AS barangay, o.registration_no, o.registering_agency, o.date_established, o.status,
                    (SELECT COUNT(*) FROM organization_members m WHERE m.organization_id = o.id) AS members,
                    (SELECT CONCAT(pp.last_name, ', ', pp.first_name) FROM organization_members m JOIN profiles pp ON pp.id = m.profile_id
                      WHERE m.organization_id = o.id AND m.position = 'President' LIMIT 1) AS president
             FROM organizations o LEFT JOIN barangays b ON b.id = o.barangay_id",
            ['o.deleted_at IS NULL'], $w, 'ORDER BY o.name'), $p);
        $fields = RecordModule::module('resources')['tabs']['organizations']['fields'];
        foreach ($rows as &$r) {
            $r['org_type'] = $fields['org_type']['options'][$r['org_type']] ?? $r['org_type'];
            $r['status'] = $fields['status']['options'][$r['status']] ?? $r['status'];
        }
        return ['rows' => $rows, 'columns' => [
            ['Organization', 'name'], ['Type', 'org_type'], ['Barangay', 'barangay'], ['Registration no.', 'registration_no'],
            ['Registered with', 'registering_agency'], ['Established', 'date_established', 'date'], ['President', 'president'],
            ['Members', 'members', 'num', true], ['Status', 'status'],
        ]];
    }

    private static function rLivestock(array $f): array
    {
        [$w, $p] = self::where($f, ['barangay_id' => 'l.barangay_id = ?', 'category' => 'l.category = ?', 'species' => 'l.species = ?']);
        $rows = Model::raw(self::sql(
            "SELECT b.name AS barangay, l.category, l.species, COUNT(DISTINCT l.profile_id) AS raisers, SUM(l.head_count) AS heads
             FROM livestock_records l JOIN barangays b ON b.id = l.barangay_id",
            ['l.deleted_at IS NULL'], $w, 'GROUP BY b.name, l.category, l.species ORDER BY b.name, l.category, l.species'), $p);
        $fields = RecordModule::module('resources')['tabs']['livestock']['fields'];
        foreach ($rows as &$r) {
            $r['category'] = $fields['category']['options'][$r['category']] ?? $r['category'];
            $r['species'] = $fields['species']['options'][$r['species']] ?? $r['species'];
        }
        return ['rows' => $rows, 'columns' => [
            ['Barangay', 'barangay'], ['Category', 'category'], ['Species', 'species'],
            ['Raisers', 'raisers', 'num', true], ['Number of heads', 'heads', 'num', true],
        ]];
    }

    private static function rFisheriesBoat(array $f): array
    {
        [$w, $p] = self::where($f, ['barangay_id' => 'a.barangay_id = ?', 'asset_type' => 'a.asset_type = ?']);
        $rows = Model::raw(self::sql(
            "SELECT a.asset_type, a.name, a.registration_no, CONCAT(p.last_name, ', ', p.first_name) AS owner, b.name AS barangay,
                    a.size_capacity, a.area_ha, a.species, a.status
             FROM fishery_assets a JOIN barangays b ON b.id = a.barangay_id JOIN profiles p ON p.id = a.profile_id",
            ['a.deleted_at IS NULL'], $w, 'ORDER BY a.asset_type, b.name, a.name'), $p);
        $fields = RecordModule::module('resources')['tabs']['fishery']['fields'];
        foreach ($rows as &$r) {
            $r['asset_type'] = $fields['asset_type']['options'][$r['asset_type']] ?? $r['asset_type'];
            $r['status'] = $fields['status']['options'][$r['status']] ?? $r['status'];
        }
        return ['rows' => $rows, 'columns' => [
            ['Type', 'asset_type'], ['Name', 'name'], ['Registration no.', 'registration_no'], ['Owner / operator', 'owner'],
            ['Barangay', 'barangay'], ['Size / capacity', 'size_capacity'], ['Area (ha)', 'area_ha', 'dec', true],
            ['Species', 'species'], ['Status', 'status'],
        ]];
    }

    private static function rProduction(array $f): array
    {
        [$w, $p] = self::where($f, [
            'barangay_id' => 'r.barangay_id = ?', 'commodity' => 'r.commodity = ?', 'season' => 'r.season = ?',
            'date_from' => 'r.date_monitored >= ?', 'date_to' => 'r.date_monitored <= ?',
        ]);
        $rows = Model::raw(self::sql(
            "SELECT r.period_month, r.period_half, CONCAT(p.last_name, ', ', p.first_name) AS farmer, b.name AS barangay,
                    r.commodity, r.season, r.area_planted_ha, r.area_harvested_ha, r.volume, r.unit, r.farm_gate_price,
                    IF(r.area_harvested_ha > 0 AND r.volume IS NOT NULL, r.volume / r.area_harvested_ha, NULL) AS yield
             FROM production_records r JOIN barangays b ON b.id = r.barangay_id JOIN profiles p ON p.id = r.profile_id",
            ['r.deleted_at IS NULL'], $w, 'ORDER BY r.period_month DESC, r.period_half DESC, b.name'), $p);
        $fields = RecordModule::module('monitoring')['tabs']['production']['fields'];
        foreach ($rows as &$r) {
            $r['period'] = date('M Y', strtotime($r['period_month'] . '-01')) . ((int) $r['period_half'] === 1 ? ' (1st half)' : ' (2nd half)');
            $r['commodity'] = $fields['commodity']['options'][$r['commodity']] ?? $r['commodity'];
            $r['season'] = $fields['season']['options'][$r['season']] ?? $r['season'];
            $r['unit'] = $fields['unit']['options'][$r['unit'] ?? ''] ?? $r['unit'];
        }
        return ['rows' => $rows, 'columns' => [
            ['Period', 'period'], ['Farmer', 'farmer'], ['Barangay', 'barangay'], ['Commodity', 'commodity'], ['Season', 'season'],
            ['Area planted (ha)', 'area_planted_ha', 'dec', true], ['Area harvested (ha)', 'area_harvested_ha', 'dec', true],
            ['Volume', 'volume', 'dec'], ['Unit', 'unit'], ['Yield per ha', 'yield', 'dec'], ['Farm gate price', 'farm_gate_price', 'money'],
        ]];
    }

    private static function rTraining(array $f): array
    {
        [$w, $p] = self::where($f, [
            'barangay_id' => 't.barangay_id = ?', 'activity_type' => 't.activity_type = ?',
            'date_from' => 't.date_held >= ?', 'date_to' => 't.date_held <= ?',
        ]);
        $rows = Model::raw(self::sql(
            "SELECT t.date_held, t.title, t.activity_type, t.topic, t.venue, b.name AS barangay, t.resource_person,
                    (SELECT COUNT(*) FROM training_participants tp WHERE tp.training_id = t.id) AS participants,
                    (SELECT COUNT(*) FROM training_participants tp JOIN profiles pp ON pp.id = tp.profile_id WHERE tp.training_id = t.id AND pp.sex = 'Female') AS female
             FROM trainings t LEFT JOIN barangays b ON b.id = t.barangay_id",
            ['t.deleted_at IS NULL'], $w, 'ORDER BY t.date_held DESC'), $p);
        $fields = RecordModule::module('monitoring')['tabs']['training']['fields'];
        foreach ($rows as &$r) {
            $r['activity_type'] = $fields['activity_type']['options'][$r['activity_type']] ?? $r['activity_type'];
            $r['male'] = (int) $r['participants'] - (int) $r['female'];
        }
        return ['rows' => $rows, 'columns' => [
            ['Date', 'date_held', 'date'], ['Title', 'title'], ['Type', 'activity_type'], ['Topic', 'topic'], ['Venue', 'venue'],
            ['Barangay', 'barangay'], ['Resource person', 'resource_person'],
            ['Male', 'male', 'num', true], ['Female', 'female', 'num', true], ['Participants', 'participants', 'num', true],
        ]];
    }

    private static function rDamagePest(array $f): array
    {
        [$w, $p] = self::where($f, [
            'barangay_id' => 'd.barangay_id = ?', 'commodity' => 'd.commodity = ?', 'cause_type' => 'd.cause_type = ?',
            'date_from' => 'd.date_occurred >= ?', 'date_to' => 'd.date_occurred <= ?',
        ]);
        $rows = Model::raw(self::sql(
            "SELECT d.date_occurred, d.cause_type, d.calamity_name, b.name AS barangay, d.commodity,
                    CONCAT(p.last_name, ', ', p.first_name) AS farmer, d.affected_area_ha, d.severity, d.estimated_loss, d.action_taken
             FROM damage_reports d JOIN barangays b ON b.id = d.barangay_id LEFT JOIN profiles p ON p.id = d.profile_id",
            ['d.deleted_at IS NULL'], $w, 'ORDER BY d.date_occurred DESC'), $p);
        $fields = RecordModule::module('monitoring')['tabs']['damage']['fields'];
        foreach ($rows as &$r) {
            $r['cause_type'] = $fields['cause_type']['options'][$r['cause_type']] ?? $r['cause_type'];
            $r['commodity'] = $fields['commodity']['options'][$r['commodity']] ?? $r['commodity'];
            $r['severity'] = $fields['severity']['options'][$r['severity']] ?? $r['severity'];
            $r['farmer'] = $r['farmer'] ?? 'Area-wide';
        }
        return ['rows' => $rows, 'columns' => [
            ['Date', 'date_occurred', 'date'], ['Cause', 'cause_type'], ['Pest / calamity', 'calamity_name'], ['Barangay', 'barangay'],
            ['Commodity', 'commodity'], ['Affected farmer', 'farmer'], ['Affected area (ha)', 'affected_area_ha', 'dec', true],
            ['Severity', 'severity'], ['Estimated loss (PHP)', 'estimated_loss', 'money', true], ['Action taken', 'action_taken'],
        ]];
    }

    private static function rProgramsBeneficiaries(array $f): array
    {
        [$w, $p] = self::where($f, ['program_id' => 'pb.program_id = ?', 'barangay_id' => 'p.barangay_id = ?', 'beneficiary_status' => 'pb.status = ?']);
        $rows = Model::raw(self::sql(
            "SELECT pr.program_no, pr.name AS program, CONCAT(p.last_name, ', ', p.first_name) AS beneficiary, b.name AS barangay,
                    pb.status, DATE(pb.created_at) AS added,
                    (SELECT COUNT(*) FROM assistance_records ar WHERE ar.program_beneficiary_id = pb.id) AS distributions
             FROM program_beneficiaries pb
             JOIN programs pr ON pr.id = pb.program_id AND pr.deleted_at IS NULL
             JOIN profiles p ON p.id = pb.profile_id JOIN barangays b ON b.id = p.barangay_id",
            [], $w, 'ORDER BY pr.year DESC, pr.name, p.last_name'), $p);
        foreach ($rows as &$r) {
            $r['status'] = ProgramBeneficiary::STATUSES[$r['status']] ?? $r['status'];
        }
        return ['rows' => $rows, 'columns' => [
            ['Program ID', 'program_no'], ['Program', 'program'], ['Beneficiary', 'beneficiary'], ['Barangay', 'barangay'],
            ['Status', 'status'], ['Added', 'added', 'date'], ['Distributions', 'distributions', 'num', true],
        ]];
    }

    private static function rAssistanceHistory(array $f): array
    {
        [$w, $p] = self::where($f, [
            'program_id' => 'pb.program_id = ?', 'barangay_id' => 'p.barangay_id = ?',
            'q' => "CONCAT(p.last_name, ' ', p.first_name, ' ', p.last_name) LIKE ?",
            'date_from' => 'ar.date_distributed >= ?', 'date_to' => 'ar.date_distributed <= ?',
        ]);
        $rows = Model::raw(self::sql(
            "SELECT CONCAT(p.last_name, ', ', p.first_name) AS beneficiary, b.name AS barangay, ar.date_distributed, ar.assistance_no,
                    pr.name AS program, COALESCE(t.name, 'Assistance') AS assistance, ar.quantity, ar.unit, ar.total_value,
                    ar.ack_receipt_no, IF(ar.duplicate_flag = 1, 'Yes', '') AS override
             FROM assistance_records ar
             JOIN program_beneficiaries pb ON pb.id = ar.program_beneficiary_id
             JOIN programs pr ON pr.id = pb.program_id
             JOIN profiles p ON p.id = pb.profile_id JOIN barangays b ON b.id = p.barangay_id
             LEFT JOIN assistance_types t ON t.id = ar.assistance_type_id",
            [], $w, 'ORDER BY p.last_name, p.first_name, ar.date_distributed DESC'), $p);
        return ['rows' => $rows, 'columns' => [
            ['Beneficiary', 'beneficiary'], ['Barangay', 'barangay'], ['Date', 'date_distributed', 'date'], ['Assistance ID', 'assistance_no'],
            ['Program', 'program'], ['Assistance', 'assistance'], ['Qty', 'quantity', 'dec'], ['Unit', 'unit'],
            ['Value (PHP)', 'total_value', 'money', true], ['Receipt no.', 'ack_receipt_no'], ['Duplicate override', 'override'],
        ]];
    }

    private static function rProgramAccomplishment(array $f): array
    {
        [$w, $p] = self::where($f, ['program_id' => 'pr.id = ?', 'year' => 'pr.year = ?', 'program_status' => 'pr.status = ?']);
        $rows = Model::raw(self::sql(
            "SELECT pr.program_no, pr.name, pr.source_agency, pr.year, pr.status, pr.target_beneficiaries, pr.allocation_amount,
                    (SELECT COUNT(*) FROM program_beneficiaries pb WHERE pb.program_id = pr.id AND pb.status <> 'removed') AS beneficiaries,
                    (SELECT COUNT(*) FROM program_beneficiaries pb WHERE pb.program_id = pr.id AND pb.status = 'served') AS served,
                    (SELECT COUNT(*) FROM assistance_records ar JOIN program_beneficiaries pb ON pb.id = ar.program_beneficiary_id WHERE pb.program_id = pr.id) AS distributions,
                    (SELECT COALESCE(SUM(ar.total_value), 0) FROM assistance_records ar JOIN program_beneficiaries pb ON pb.id = ar.program_beneficiary_id WHERE pb.program_id = pr.id) AS distributed_value
             FROM programs pr",
            ['pr.deleted_at IS NULL'], $w, 'ORDER BY pr.year DESC, pr.name'), $p);
        foreach ($rows as &$r) {
            $r['status'] = Program::STATUSES[$r['status']] ?? $r['status'];
            $r['percent'] = $r['target_beneficiaries'] ? round(100 * $r['served'] / $r['target_beneficiaries'], 1) . '%' : '';
        }
        return ['rows' => $rows, 'columns' => [
            ['Program ID', 'program_no'], ['Program', 'name'], ['Source', 'source_agency'], ['Year', 'year'], ['Status', 'status'],
            ['Target', 'target_beneficiaries', 'num', true], ['Beneficiaries', 'beneficiaries', 'num', true], ['Served', 'served', 'num', true],
            ['% of target served', 'percent'], ['Distributions', 'distributions', 'num', true],
            ['Allocation (PHP)', 'allocation_amount', 'money', true], ['Distributed value (PHP)', 'distributed_value', 'money', true],
        ]];
    }

    private static function rBarangayCommoditySummary(array $f): array
    {
        $params = [];
        $farmCond = 'f.deleted_at IS NULL';
        if (($f['commodity'] ?? '') !== '') {
            $farmCond .= ' AND f.commodity = ?';
            $params[] = $f['commodity'];
        }
        $where = '';
        $tail  = [];
        if (($f['barangay_id'] ?? '') !== '') {
            $where = ' WHERE b.id = ?';
            $tail[] = $f['barangay_id'];
        }
        $rows = Model::raw(
            "SELECT b.name AS barangay,
                (SELECT COUNT(*) FROM profiles p JOIN farming_profiles fp ON fp.id = p.id AND fp.deleted_at IS NULL WHERE p.barangay_id = b.id AND p.deleted_at IS NULL) AS farmers,
                (SELECT COUNT(*) FROM profiles p JOIN fishery_profiles fs ON fs.id = p.id AND fs.deleted_at IS NULL WHERE p.barangay_id = b.id AND p.deleted_at IS NULL) AS fisherfolk,
                (SELECT COUNT(*) FROM farms f WHERE f.barangay_id = b.id AND $farmCond) AS farms,
                (SELECT COALESCE(SUM(f.area_ha), 0) FROM farms f WHERE f.barangay_id = b.id AND $farmCond) AS farm_area,
                (SELECT COUNT(*) FROM fishery_assets a WHERE a.barangay_id = b.id AND a.deleted_at IS NULL) AS fishery_assets,
                (SELECT COALESCE(SUM(l.head_count), 0) FROM livestock_records l WHERE l.barangay_id = b.id AND l.deleted_at IS NULL) AS livestock_heads,
                (SELECT COUNT(DISTINCT pb.profile_id) FROM program_beneficiaries pb JOIN profiles p ON p.id = pb.profile_id WHERE p.barangay_id = b.id AND pb.status = 'served') AS served
             FROM barangays b" . $where . ' ORDER BY b.name',
            array_merge($params, $params, $tail));
        return ['rows' => $rows, 'columns' => [
            ['Barangay', 'barangay'], ['Farmers', 'farmers', 'num', true], ['Fisherfolk', 'fisherfolk', 'num', true],
            ['Farms', 'farms', 'num', true], ['Farm area (ha)', 'farm_area', 'dec', true], ['Fishery assets', 'fishery_assets', 'num', true],
            ['Livestock / poultry heads', 'livestock_heads', 'num', true], ['Beneficiaries served', 'served', 'num', true],
        ]];
    }
}
