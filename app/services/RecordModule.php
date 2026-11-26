<?php
/**
 * Generic list / save / archive for the tabbed record modules in config/modules.php
 * (Resources and Monitoring). Table and column names only ever come from that
 * config, never from the request; all values are bound parameters.
 */
class RecordModule
{
    /** Lookup field types: [table, label SQL using {a} as the joined alias]. */
    private const LOOKUPS = [
        'barangay'     => ['barangays', '{a}.name'],
        'profile'      => ['profiles', "CONCAT({a}.last_name, ', ', {a}.first_name)"],
        'farm'         => ['farms', '{a}.farm_name'],
        'organization' => ['organizations', '{a}.name'],
    ];

    private static array $tableCache = [];

    // ------------------------------------------------------------------ config

    public static function module(string $key): array
    {
        static $modules = null;
        $modules ??= require BASE_PATH . '/config/modules.php';
        if (!isset($modules[$key])) {
            throw new InvalidArgumentException("Unknown module: $key");
        }
        return $modules[$key];
    }

    /** Does the table exist yet? (The SQL in database/schema_modules.sql may not have been run.) */
    public static function tableReady(string $table): bool
    {
        if (!array_key_exists($table, self::$tableCache)) {
            $stmt = Database::connect()->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
            );
            $stmt->execute([config('db.name'), $table]);
            self::$tableCache[$table] = (bool) $stmt->fetchColumn();
        }
        return self::$tableCache[$table];
    }

    /** Tab is ready when its table and any pivot tables exist. */
    public static function tabReady(array $tab): bool
    {
        if (!self::tableReady($tab['table'])) {
            return false;
        }
        foreach ($tab['fields'] as $f) {
            if ($f['type'] === 'members' && !self::tableReady($f['pivot']['table'])) {
                return false;
            }
        }
        return true;
    }

    private static function ident(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid identifier: $name");
        }
        return '`' . $name . '`';
    }

    /** Table columns only (drops 'members' fields, which live in pivot tables). */
    private static function columns(array $tab): array
    {
        return array_keys(array_filter($tab['fields'], fn ($f) => $f['type'] !== 'members'));
    }

    // ------------------------------------------------------------------ reading

    /** SELECT list + JOINs adding "<field>__label" for lookups and "<field>__ids/__count" for members. */
    private static function selectParts(array $tab): array
    {
        $select = ['t.*'];
        $joins  = [];
        $n = 0;
        foreach ($tab['fields'] as $name => $f) {
            if (isset(self::LOOKUPS[$f['type']])) {
                [$table, $label] = self::LOOKUPS[$f['type']];
                if (!self::tableReady($table)) {
                    continue;
                }
                $a = 'j' . (++$n);
                $joins[]  = 'LEFT JOIN ' . self::ident($table) . " $a ON $a.id = t." . self::ident($name);
                $select[] = str_replace('{a}', $a, $label) . ' AS ' . self::ident($name . '__label');
            } elseif ($f['type'] === 'members') {
                $p = $f['pivot'];
                $sub = ' FROM ' . self::ident($p['table']) . ' pv WHERE pv.' . self::ident($p['fk']) . ' = t.id';
                $select[] = '(SELECT COUNT(*)' . $sub . ') AS ' . self::ident($name . '__count');
                $select[] = '(SELECT GROUP_CONCAT(pv.profile_id)' . $sub . ') AS ' . self::ident($name . '__ids');
            }
        }
        return [implode(', ', $select), implode(' ', $joins)];
    }

    /** Paginated, searched, filtered rows. */
    public static function list(array $tab, array $filters, int $page = 1): array
    {
        [$select, $joins] = self::selectParts($tab);
        $where  = ['t.deleted_at IS NULL'];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '' && !empty($tab['search'])) {
            $or = [];
            foreach ($tab['search'] as $col) {
                if (($tab['fields'][$col]['type'] ?? '') === 'profile') {
                    $or[] = "EXISTS (SELECT 1 FROM profiles sp WHERE sp.id = t." . self::ident($col)
                          . " AND CONCAT(sp.last_name, ' ', sp.first_name, ' ', sp.last_name) LIKE ?)";
                } else {
                    $or[] = 't.' . self::ident($col) . ' LIKE ?';
                }
                $params[] = '%' . $q . '%';
            }
            $where[] = '(' . implode(' OR ', $or) . ')';
        }
        foreach ($tab['filters'] ?? [] as $col) {
            $v = (string) ($filters[$col] ?? '');
            if ($v !== '') {
                $where[] = 't.' . self::ident($col) . ' = ?';
                $params[] = $v;
            }
        }

        $from  = ' FROM ' . self::ident($tab['table']) . ' t ' . $joins . ' WHERE ' . implode(' AND ', $where);
        $total = (int) Model::query('SELECT COUNT(*)' . $from, $params)->fetchColumn();

        $perPage = (int) config('per_page', 15);
        $pages   = max(1, (int) ceil($total / $perPage));
        $page    = min(max(1, $page), $pages);
        $order   = implode(', ', array_map(function ($part) {
            [$col, $dir] = array_pad(preg_split('/\s+/', trim($part)), 2, 'ASC');
            return 't.' . self::ident($col) . (strtoupper($dir) === 'DESC' ? ' DESC' : ' ASC');
        }, explode(',', $tab['order'] ?? 'id DESC')));

        $rows = Model::raw('SELECT ' . $select . $from . ' ORDER BY ' . $order
            . ' LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params);

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    public static function find(array $tab, int $id): ?array
    {
        [$select, $joins] = self::selectParts($tab);
        return Model::rawOne('SELECT ' . $select . ' FROM ' . self::ident($tab['table']) . ' t ' . $joins
            . ' WHERE t.id = ? AND t.deleted_at IS NULL', [$id]);
    }

    // ------------------------------------------------------------------ options

    /** [value => label] for a field (select options or a lookup table). */
    public static function options(array $f): array
    {
        static $cache = [];
        switch ($f['type']) {
            case 'select':
                return $f['options'];
            case 'barangay':
                return Lookup::barangays();
            case 'profile':
            case 'members':
                return $cache['profile'] ??= array_column(Model::raw(
                    "SELECT id, CONCAT(last_name, ', ', first_name, IF(middle_name IS NULL OR middle_name = '', '', CONCAT(' ', LEFT(middle_name, 1), '.'))) AS label
                     FROM profiles WHERE deleted_at IS NULL ORDER BY last_name, first_name LIMIT 2000"), 'label', 'id');
            case 'farm':
                if (!self::tableReady('farms')) {
                    return [];
                }
                return $cache['farm'] ??= array_column(Model::raw(
                    "SELECT f.id, CONCAT(f.farm_name, ' (', p.last_name, ', ', p.first_name, ')') AS label
                     FROM farms f JOIN profiles p ON p.id = f.profile_id
                     WHERE f.deleted_at IS NULL ORDER BY f.farm_name LIMIT 2000"), 'label', 'id');
            case 'organization':
                if (!self::tableReady('organizations')) {
                    return [];
                }
                return $cache['organization'] ??= array_column(Model::raw(
                    'SELECT id, name FROM organizations WHERE deleted_at IS NULL ORDER BY name LIMIT 2000'), 'name', 'id');
        }
        return [];
    }

    // ------------------------------------------------------------------ saving

    /** Validator rules + extra checks. Returns [clean data, members => [field => ids], errors]. */
    public static function validate(array $tab, array $input, ?int $id = null): array
    {
        // Collapse repeated spaces in text answers ("Juan   Dela  Cruz" -> "Juan Dela Cruz")
        foreach ($tab['fields'] as $name => $f) {
            if ($f['type'] === 'text' && is_string($input[$name] ?? null)) {
                $input[$name] = trim((string) preg_replace('/\s+/u', ' ', $input[$name]));
            }
        }

        $rules = $labels = [];
        foreach ($tab['fields'] as $name => $f) {
            if ($f['type'] === 'members') {
                continue;
            }
            $r = [!empty($f['required']) ? 'required' : 'nullable'];
            switch ($f['type']) {
                case 'text':
                case 'textarea':
                    $r[] = 'max:' . ($f['max'] ?? 2000);
                    break;
                case 'number':
                    $r[] = 'numeric';
                    break;
                case 'integer':
                    $r[] = 'integer';
                    break;
                case 'date':
                    $r[] = 'date';
                    if (!empty($f['not_future'])) {
                        $r[] = 'not_future';
                    }
                    break;
                case 'select':
                    $r[] = 'in:' . implode(',', array_map('strval', array_keys($f['options'])));
                    break;
                default:
                    if (isset(self::LOOKUPS[$f['type']])) {
                        $r[] = 'integer';
                        $r[] = 'exists:' . self::LOOKUPS[$f['type']][0] . ',id';
                    }
            }
            $rules[$name]  = implode('|', $r);
            $labels[$name] = $f['label'];
        }

        $v = Validator::make($input, $rules, $labels);
        $errors = $v->errors();
        $clean  = $v->validated();

        // Checks the Validator doesn't have: number ranges and YYYY-MM months
        foreach ($tab['fields'] as $name => $f) {
            $val = $clean[$name] ?? null;
            if ($val === null || isset($errors[$name])) {
                continue;
            }
            if (in_array($f['type'], ['number', 'integer'], true)) {
                $min = $f['min'] ?? 0;
                if ((float) $val < $min) {
                    $errors[$name][] = $f['label'] . ' must be at least ' . $min . '.';
                }
                if (isset($f['max_value']) && (float) $val > $f['max_value']) {
                    $errors[$name][] = $f['label'] . ' must not be more than ' . $f['max_value'] . '.';
                }
                if (!empty($f['positive']) && (float) $val <= 0 && !isset($errors[$name])) {
                    $errors[$name][] = $f['label'] . ' must be greater than 0.';
                }
            }
            if ($f['type'] === 'month' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $val)) {
                $errors[$name][] = $f['label'] . ' must be a month (YYYY-MM).';
            }
        }

        self::checkRules($tab, $clean, $errors, $id);

        // Many-to-many members: only existing, active profiles
        $members = [];
        foreach ($tab['fields'] as $name => $f) {
            if ($f['type'] !== 'members') {
                continue;
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($input[$name] ?? [])))));
            if ($ids) {
                $found = array_map('intval', array_column(Model::raw(
                    'SELECT id FROM profiles WHERE deleted_at IS NULL AND id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids), 'id'));
                if (count($found) !== count($ids)) {
                    $errors[$name][] = $f['label'] . ' has an invalid selection.';
                }
                $ids = $found;
            }
            $members[$name] = $ids;
        }

        return [$clean, $members, $errors];
    }

    /**
     * Cross-field rules from a tab's 'rules' entry in config/modules.php:
     *   pair         [[a, b], ...]            fill both or neither (e.g. latitude + longitude)
     *   required_if  [field => [other, [values]]]  field is required when other is one of values
     *   blank_unless [field => [other, [values]]]  field must be empty unless other is one of values
     *   unique       [field, ...]             no other active record may use the same value
     */
    private static function checkRules(array $tab, array $clean, array &$errors, ?int $id): void
    {
        $r = $tab['rules'] ?? [];
        $filled = fn (string $k) => isset($clean[$k]) && $clean[$k] !== '';
        // "Farmer (if individual)" -> "Farmer"; "Registration no." -> "Registration no"
        $f = array_map(fn ($def) => ['label' => rtrim((string) preg_replace('/\s*\(.*\)$/', '', $def['label']), '.')] + $def, $tab['fields']);
        $choice = fn (string $other) => $f[$other]['options'][(string) ($clean[$other] ?? '')] ?? (string) ($clean[$other] ?? '');

        foreach ($r['pair'] ?? [] as [$a, $b]) {
            if ($filled($a) !== $filled($b) && !isset($errors[$a]) && !isset($errors[$b])) {
                $missing = $filled($a) ? $b : $a;
                $errors[$missing][] = 'Enter both ' . strtolower($f[$a]['label']) . ' and ' . strtolower($f[$b]['label']) . ', or leave both blank.';
            }
        }
        foreach ($r['required_if'] ?? [] as $field => [$other, $values]) {
            if (in_array((string) ($clean[$other] ?? ''), $values, true) && !$filled($field) && !isset($errors[$field])) {
                $errors[$field][] = $f[$field]['label'] . ' is required when ' . $f[$other]['label'] . ' is ' . $choice($other) . '.';
            }
        }
        foreach ($r['blank_unless'] ?? [] as $field => [$other, $values]) {
            if ($filled($field) && !in_array((string) ($clean[$other] ?? ''), $values, true) && !isset($errors[$field])) {
                $errors[$field][] = 'Leave ' . strtolower($f[$field]['label']) . ' blank when ' . $f[$other]['label'] . ' is ' . $choice($other) . '.';
            }
        }
        foreach ($r['unique'] ?? [] as $field) {
            if ($filled($field) && !isset($errors[$field])) {
                $sql = 'SELECT COUNT(*) FROM ' . self::ident($tab['table']) . ' WHERE ' . self::ident($field) . ' = ? AND deleted_at IS NULL';
                $params = [$clean[$field]];
                if ($id !== null) {
                    $sql .= ' AND id <> ?';
                    $params[] = $id;
                }
                if ((int) Model::query($sql, $params)->fetchColumn() > 0) {
                    $errors[$field][] = 'Another record already uses this ' . strtolower($f[$field]['label']) . '.';
                }
            }
        }
    }

    public static function insert(array $tab, array $clean, array $members): int
    {
        $data = array_intersect_key($clean, array_flip(self::columns($tab)));
        $now  = date('Y-m-d H:i:s');
        $data += ['created_by' => Auth::id(), 'created_at' => $now, 'updated_at' => $now];

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            Model::query('INSERT INTO ' . self::ident($tab['table'])
                . ' (' . implode(', ', array_map([self::class, 'ident'], array_keys($data))) . ')'
                . ' VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')', array_values($data));
            $id = (int) $pdo->lastInsertId();
            self::syncMembers($tab, $id, $members);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        AuditService::log('create', $tab['table'], $id, null, $data + self::memberAudit($members));
        return $id;
    }

    public static function update(array $tab, array $old, array $clean, array $members): void
    {
        $cols = self::columns($tab);
        $data = array_intersect_key($clean, array_flip($cols));
        $data['updated_at'] = date('Y-m-d H:i:s');

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $sets = implode(', ', array_map(fn ($c) => self::ident($c) . ' = ?', array_keys($data)));
            Model::query('UPDATE ' . self::ident($tab['table']) . " SET $sets WHERE id = ?", [...array_values($data), (int) $old['id']]);
            self::syncMembers($tab, (int) $old['id'], $members);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        [$o, $n] = AuditService::diff($old, $data, $cols);
        if ($o || $n || $members) {
            AuditService::log('update', $tab['table'], (int) $old['id'], $o ?: null, ($n + self::memberAudit($members)) ?: null);
        }
    }

    public static function archive(array $tab, int $id): void
    {
        Model::query('UPDATE ' . self::ident($tab['table']) . ' SET deleted_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $id]);
        AuditService::log('archive', $tab['table'], $id);
    }

    private static function syncMembers(array $tab, int $id, array $members): void
    {
        foreach ($members as $name => $ids) {
            $p = $tab['fields'][$name]['pivot'];
            $table = self::ident($p['table']);
            $fk    = self::ident($p['fk']);
            Model::query("DELETE FROM $table WHERE $fk = ?", [$id]);
            foreach ($ids as $pid) {
                Model::query("INSERT INTO $table ($fk, profile_id, created_at) VALUES (?, ?, ?)", [$id, $pid, date('Y-m-d H:i:s')]);
            }
        }
    }

    private static function memberAudit(array $members): array
    {
        return array_map(fn ($ids) => implode(',', $ids), $members);
    }

    // ------------------------------------------------------------------ display

    /** Human-readable value of one field of a row. */
    public static function display(array $f, string $name, array $row): string
    {
        if ($f['type'] === 'members') {
            $n = (int) ($row[$name . '__count'] ?? 0);
            return $n . ' ' . ($n === 1 ? 'person' : 'people');
        }
        $v = $row[$name] ?? null;
        if ($v === null || $v === '') {
            return '';
        }
        if (isset(self::LOOKUPS[$f['type']])) {
            return (string) ($row[$name . '__label'] ?? '');
        }
        switch ($f['type']) {
            case 'select':
                return (string) ($f['options'][(string) $v] ?? $v);
            case 'date':
                return format_date((string) $v);
            case 'month':
                return date('M Y', strtotime($v . '-01'));
            case 'number':
                return rtrim(rtrim(number_format((float) $v, 4, '.', ','), '0'), '.');
            case 'integer':
                return number_format((int) $v);
        }
        return (string) $v;
    }

    public static function computedLabel(string $key): string
    {
        return ['mapped' => 'Map', 'holder' => 'Held by', 'period' => 'Period', 'volume' => 'Volume',
                'yield' => 'Yield', 'loss' => 'Est. loss'][$key] ?? ucfirst($key);
    }

    /** Calculated list columns ('computed:<key>' in config): [heading, escaped HTML]. */
    public static function computed(string $key, array $row, array $tab): array
    {
        $num = fn ($v, int $d = 2) => rtrim(rtrim(number_format((float) $v, $d, '.', ','), '0'), '.');
        switch ($key) {
            case 'mapped':
                $on = $row['latitude'] !== null && $row['longitude'] !== null;
                return ['Map', $on ? '<span class="badge badge-soft-success"><i class="bi bi-geo-alt-fill"></i> Mapped</span>'
                                   : '<span class="badge badge-soft-muted">No location</span>'];
            case 'holder':
                $who = $row['ownership'] === 'organization' ? ($row['organization_id__label'] ?? '')
                     : ($row['ownership'] === 'lgu' ? 'LGU / MAO' : ($row['profile_id__label'] ?? ''));
                return ['Held by', e($who ?: '—')];
            case 'period':
                $half = (int) $row['period_half'] === 1 ? '1st half' : '2nd half';
                return ['Period', e(date('M Y', strtotime($row['period_month'] . '-01')) . ' · ' . $half)];
            case 'volume':
                $unit = $tab['fields']['unit']['options'][$row['unit'] ?? ''] ?? ($row['unit'] ?? '');
                return ['Volume', $row['volume'] === null ? '—' : e($num($row['volume']) . ' ' . $unit)];
            case 'yield':
                $area = (float) ($row['area_harvested_ha'] ?? 0);
                return ['Yield', ($row['volume'] === null || $area <= 0) ? '—'
                    : e($num((float) $row['volume'] / $area) . ' ' . ($row['unit'] ?? '') . '/ha')];
            case 'loss':
                return ['Est. loss', $row['estimated_loss'] === null ? '—' : e('PHP ' . number_format((float) $row['estimated_loss'], 2))];
        }
        return [$key, ''];
    }

    /** Raw values for the edit form (members as id arrays). */
    public static function formValues(array $tab, array $row): array
    {
        $out = ['id' => (int) $row['id']];
        foreach ($tab['fields'] as $name => $f) {
            $out[$name] = $f['type'] === 'members'
                ? array_values(array_filter(explode(',', (string) ($row[$name . '__ids'] ?? ''))))
                : ($row[$name] ?? '');
        }
        return $out;
    }

    /** [[label, value], ...] for the View modal. */
    public static function viewPairs(array $tab, array $row): array
    {
        $pairs = [];
        foreach ($tab['fields'] as $name => $f) {
            if (isset($tab['sections'][$name])) {
                $pairs[] = ['__section', $tab['sections'][$name]];   // heading row in the View modal
            }
            if ($f['type'] === 'members') {
                $ids    = array_filter(explode(',', (string) ($row[$name . '__ids'] ?? '')));
                $people = array_intersect_key(self::options($f), array_flip($ids));
                $pairs[] = [$f['label'], $people ? implode('; ', $people) : ''];
                continue;
            }
            $pairs[] = [$f['label'], self::display($f, $name, $row)];
        }
        $pairs[] = ['Recorded', format_date((string) $row['created_at'])];
        return $pairs;
    }
}
