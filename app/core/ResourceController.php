<?php
/**
 * Shared CRUD for the Agricultural Resources screens (process 2.0).
 * A child controller only describes its resource in build(); list, form, detail, save and archive come from here.
 *
 * build() keys: model, table, path, title, singular, sector (null = any technician), select, from (alias r),
 *   search[], filters[name => [label, column, options]], stats[label => [sqlExpr, decimals]], columns[],
 *   fields[], defaults[], label(callable row), detail(callable row => pairs)
 */
abstract class ResourceController extends Controller
{
    private ?array $built = null;

    abstract protected function build(): array;

    protected function cfg(): array
    {
        return $this->built ??= $this->build() + [
            'sector' => null, 'orderBy' => 'r.id DESC', 'search' => [], 'filters' => [],
            'stats' => [], 'defaults' => [], 'columns' => [],
        ];
    }

    protected function canEdit(): bool
    {
        $s = $this->cfg()['sector'];
        return $s === null || Auth::canEditSector($s);
    }

    protected function guardEdit(): void
    {
        if (!$this->canEdit()) {
            abort(403, 'This record belongs to another sector.');
        }
    }

    /** Resource-specific validation; add messages to $errors['field'][]. */
    protected function validateExtra(array $clean, array &$errors, ?array $existing): void {}

    protected function toRow(array $clean): array
    {
        return $clean;
    }

    protected function showExtra(array $row): string
    {
        return '';
    }

    protected function crumbs(array $tail = []): array
    {
        $c = $this->cfg();
        return array_merge([['Home', '/home'], ['Agricultural Resources', null], [$c['title'], $tail ? $c['path'] : null]], $tail);
    }

    // ------------------------------------------------------------------ list

    public function index(): void
    {
        $c = $this->cfg();
        $filters = ['q' => trim((string) $this->input('q', ''))];
        foreach ($c['filters'] as $name => $f) {
            $filters[$name] = (string) $this->input($name, '');
        }
        $where = ['r.deleted_at IS NULL'];
        $params = [];
        if ($filters['q'] !== '' && $c['search']) {
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $where[] = '(' . implode(' OR ', array_map(fn ($s) => "$s LIKE ?", $c['search'])) . ')';
            foreach ($c['search'] as $_) {
                $params[] = $like;
            }
        }
        foreach ($c['filters'] as $name => $f) {
            if ($filters[$name] !== '') {
                $where[] = $f['column'] . ' = ?';
                $params[] = $filters[$name];
            }
        }
        $w = ' WHERE ' . implode(' AND ', $where);

        $per = (int) config('per_page', 15);
        $total = (int) Model::query('SELECT COUNT(*) ' . $c['from'] . $w, $params)->fetchColumn();
        $pages = max(1, (int) ceil($total / $per));
        $page = min(max(1, (int) $this->input('page', 1)), $pages);
        $rows = Model::raw('SELECT ' . $c['select'] . ' ' . $c['from'] . $w . ' ORDER BY ' . $c['orderBy']
            . ' LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per), $params);

        $stats = [];
        if ($c['stats']) {
            $exprs = [];
            $i = 0;
            foreach ($c['stats'] as [$expr]) {
                $exprs[] = $expr . ' AS s' . $i++;
            }
            $one = Model::rawOne('SELECT ' . implode(', ', $exprs) . ' ' . $c['from'] . $w, $params) ?? [];
            $i = 0;
            foreach ($c['stats'] as $label => [$expr, $dec]) {
                $stats[$label] = number_format((float) ($one['s' . $i++] ?? 0), $dec);
            }
        }

        $this->view('resources/index', [
            'title' => $c['title'], 'breadcrumbs' => $this->crumbs(), 'c' => $c, 'filters' => $filters,
            'result' => ['data' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $per],
            'stats' => $stats, 'canEdit' => $this->canEdit(),
        ]);
    }

    // ------------------------------------------------------------------ detail

    protected function findRow(string $id): array
    {
        $c = $this->cfg();
        $row = ctype_digit($id)
            ? Model::rawOne('SELECT ' . $c['select'] . ' ' . $c['from'] . ' WHERE r.id = ? AND r.deleted_at IS NULL LIMIT 1', [$id])
            : null;
        if (!$row) {
            abort(404, $c['singular'] . ' not found.');
        }
        return $row;
    }

    public function show(string $id): void
    {
        $c = $this->cfg();
        $row = $this->findRow($id);
        $name = ($c['label'])($row);
        $this->view('resources/show', [
            'title' => $name, 'breadcrumbs' => $this->crumbs([[$name, null]]), 'c' => $c, 'row' => $row,
            'pairs' => ($c['detail'])($row), 'extra' => $this->showExtra($row), 'canEdit' => $this->canEdit(),
        ]);
    }

    // ------------------------------------------------------------------ create / edit

    public function create(): void
    {
        $this->guardEdit();
        $c = $this->cfg();
        $values = $c['defaults'];
        if (ctype_digit((string) $this->input('profile_id', ''))) {
            $values['profile_id'] = $this->input('profile_id');
        }
        $this->renderForm('create', $values);
    }

    public function edit(string $id): void
    {
        $this->guardEdit();
        $this->renderForm('edit', $this->findRow($id));
    }

    private function renderForm(string $mode, array $values): void
    {
        $c = $this->cfg();
        $title = ($mode === 'create' ? 'Add ' : 'Edit ') . $c['singular'];
        $tail = $mode === 'edit' ? [[($c['label'])($values), $c['path'] . '/' . $values['id']], [$title, null]] : [[$title, null]];
        $this->view('resources/form', [
            'title' => $title, 'breadcrumbs' => $this->crumbs($tail), 'c' => $c, 'mode' => $mode, 'values' => $values,
        ]);
    }

    // ------------------------------------------------------------------ save

    private function collect(?array $existing, string $back): array
    {
        $c = $this->cfg();
        $data = $this->only(array_column($c['fields'], 'name'));
        foreach ($data as $k => $v) {
            $data[$k] = is_string($v) ? preg_replace('/[ \t]+/', ' ', $v) : $v;
        }
        $rules = $labels = [];
        foreach ($c['fields'] as $f) {
            $rules[$f['name']] = $f['rules'];
            $labels[$f['name']] = $f['label'];
        }
        $v = Validator::make($data, $rules, $labels);
        $errors = $v->errors();
        $clean = $v->validated();
        foreach ($c['fields'] as $f) {
            $n = $f['name'];
            if (!empty($f['positive']) && !isset($errors[$n]) && $clean[$n] !== null && (float) $clean[$n] <= 0) {
                $errors[$n][] = $f['label'] . ' must be greater than zero.';
            }
        }
        $this->validateExtra($clean, $errors, $existing);
        if ($errors) {
            flash_errors($errors);
            flash_old($data);
            $this->redirect($back);
        }
        return $clean;
    }

    public function store(): void
    {
        $this->guardEdit();
        $c = $this->cfg();
        $row = $this->toRow($this->collect(null, $c['path'] . '/create'));
        $model = $c['model'];
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $id = $model::insert($row);
            AuditService::log('create', $c['table'], $id, null, $row);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        flash('success', $c['singular'] . ' added.');
        $this->redirect($c['path'] . '/' . $id);
    }

    public function update(string $id): void
    {
        $this->guardEdit();
        $c = $this->cfg();
        $existing = $this->findRow($id);
        $row = $this->toRow($this->collect($existing, $c['path'] . '/' . $existing['id'] . '/edit'));
        $model = $c['model'];
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $model::update((int) $existing['id'], $row);
            [$old, $new] = AuditService::diff($existing, $row, array_keys($row));
            if ($new) {
                AuditService::log('update', $c['table'], (int) $existing['id'], $old, $new);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        flash('success', $c['singular'] . ' updated.');
        $this->redirect($c['path'] . '/' . $existing['id']);
    }

    /** Soft delete: the plan says records are never hard-deleted. Municipal Agriculturist only. */
    public function delete(string $id): void
    {
        if (!Auth::isAgriculturist()) {
            abort(403, 'Only the Municipal Agriculturist can remove records.');
        }
        $c = $this->cfg();
        $row = $this->findRow($id);
        $model = $c['model'];
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $model::archive((int) $row['id']);
            AuditService::log('archive', $c['table'], (int) $row['id'], null, ['record' => ($c['label'])($row)], $c['singular'] . ' removed by user');
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        flash('success', $c['singular'] . ' removed.');
        $this->redirect($c['path']);
    }

    // ------------------------------------------------------------------ helpers for children

    protected function validateGeo(array $clean, array &$errors): void
    {
        $lat = $clean['latitude'] ?? null;
        $lng = $clean['longitude'] ?? null;
        if (($lat === null) !== ($lng === null)) {
            $errors['latitude'][] = 'Enter both latitude and longitude, or leave both blank.';
        } elseif ($lat !== null && is_numeric($lat) && is_numeric($lng)) {
            if ($lat < -90 || $lat > 90) {
                $errors['latitude'][] = 'Latitude must be between -90 and 90.';
            }
            if ($lng < -180 || $lng > 180) {
                $errors['longitude'][] = 'Longitude must be between -180 and 180.';
            }
        }
        if (!empty($clean['boundary_geojson'])) {
            $g = json_decode((string) $clean['boundary_geojson'], true);
            if (!is_array($g) || !isset($g['type'])) {
                $errors['boundary_geojson'][] = 'Boundary must be valid GeoJSON.';
            }
        }
    }

    protected static function in(array $options): string
    {
        return 'in:' . implode(',', array_keys($options));
    }

    protected static function profileLink(array $r): string
    {
        return '<a href="' . e(url('/farmers/' . $r['profile_id'])) . '">' . e(Profile::fullName($r)) . '</a>';
    }
}
