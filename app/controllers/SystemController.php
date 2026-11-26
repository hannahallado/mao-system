<?php
/**
 * System (Municipal Agriculturist only): one page, tabs
 *   users     user accounts (add / edit / deactivate; never deleted)
 *   access    role capability matrix (from the design outline)
 *   audit     audit log viewer
 *   backup    download a full .sql backup of the database
 *   settings  office details, report header and signatories
 */
class SystemController extends Controller
{
    public const TABS = [
        'users'    => ['Users', 'people'],
        'access'   => ['Access Control', 'shield-lock'],
        'audit'    => ['Audit Log', 'journal-text'],
        'backup'   => ['Backup', 'database-down'],
        'settings' => ['Settings', 'sliders'],
    ];

    public const ROLES   = ['technician' => 'Agricultural Technologist', 'agriculturist' => 'Municipal Agriculturist'];
    public const SECTORS = ['all' => 'All sectors', 'crops' => 'Crops', 'livestock_poultry' => 'Livestock and Poultry', 'fisheries' => 'Fisheries'];

    private const USER_LABELS = ['full_name' => 'Full name', 'username' => 'Username', 'role' => 'Role', 'sector' => 'Sector', 'password' => 'Password'];

    // ------------------------------------------------------------------ page

    public function index(): void
    {
        $tab = (string) $this->input('tab', 'users');
        if (!isset(self::TABS[$tab])) {
            $tab = 'users';
        }
        $data = [
            'title'       => 'System',
            'tab'         => $tab,
            'breadcrumbs' => [['Home', '/home'], ['System', '/system'], [self::TABS[$tab][0], null]],
            'scripts'     => ['js/system.js'],
        ];

        switch ($tab) {
            case 'users':
                $form   = (string) $this->input('form', '');
                $editId = (int) $this->input('id', 0);
                $data += [
                    'users'    => Model::raw('SELECT id, full_name, username, role, sector, is_active, last_login_at, created_at FROM users ORDER BY is_active DESC, full_name'),
                    'openForm' => $form === 'add' || ($form === 'edit' && $editId),
                    'editId'   => $form === 'edit' ? $editId : 0,
                ];
                break;
            case 'audit':
                $data += $this->auditData();
                break;
            case 'backup':
                $data['tables'] = Model::raw(
                    'SELECT TABLE_NAME AS name, TABLE_ROWS AS approx_rows, ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024) AS size_kb
                     FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = "BASE TABLE" ORDER BY TABLE_NAME', [config('db.name')]);
                break;
            case 'settings':
                $data += ['settings' => Setting::all(), 'settingsReady' => Setting::ready()];
                break;
        }
        $this->view('system/index', $data);
    }

    /** Old /system/users, /system/backup, ... links open the matching tab. */
    public function legacy(): void
    {
        $map = ['/system/users' => 'users', '/system/users/create' => 'users', '/system/access-control' => 'access',
                '/system/backup' => 'backup', '/system/settings' => 'settings'];
        $this->redirect('/system?tab=' . ($map[current_path()] ?? 'users'));
    }

    // ------------------------------------------------------------------ users

    public function storeUser(): void
    {
        $this->saveUser(null);
    }

    public function updateUser(string $id): void
    {
        $this->saveUser((int) $id);
    }

    private function saveUser(?int $id): void
    {
        $old = $id ? (User::find($id) ?? abort(404, 'User not found.')) : null;
        $back = '/system?tab=users&form=' . ($id ? 'edit&id=' . $id : 'add');

        $data = [
            'full_name' => $this->input('full_name'),
            'username'  => strtolower((string) $this->input('username', '')),
            'role'      => $this->input('role'),
            'sector'    => $this->input('sector'),
            'password'  => (string) ($_POST['password'] ?? ''),   // not trimmed
        ];
        $rules = [
            'full_name' => 'required|max:120',
            'username'  => 'required|min:3|max:50|unique:users,username' . ($id ? ',' . $id : ''),
            'role'      => 'required|in:' . implode(',', array_keys(self::ROLES)),
            'sector'    => 'required|in:' . implode(',', array_keys(self::SECTORS)),
            'password'  => ($id ? 'nullable' : 'required') . '|min:8|max:72',
        ];
        $v = Validator::make($data, $rules, self::USER_LABELS);
        $errors = $v->errors();
        if (!isset($errors['username']) && !preg_match('/^[a-z0-9._-]+$/', $data['username'])) {
            $errors['username'][] = 'Username may only use letters, numbers, dots, dashes and underscores.';
        }
        if ($data['password'] !== '' && $data['password'] !== (string) ($_POST['password_confirm'] ?? '')) {
            $errors['password_confirm'][] = 'The passwords do not match.';
        }
        // Don't let the last active agriculturist lose that role (nobody could manage users)
        if ($old && $old['role'] === 'agriculturist' && $data['role'] !== 'agriculturist' && $this->activeAgriculturists($id) === 0) {
            $errors['role'][] = 'At least one active Municipal Agriculturist account is required.';
        }
        if ($errors) {
            flash_errors($errors);
            flash_old(array_diff_key($data, ['password' => 1]));
            $this->redirect($back);
        }

        $clean = $v->validated();
        $row = [
            'full_name' => $clean['full_name'],
            'username'  => $clean['username'],
            'role'      => $clean['role'],
            // Agriculturists always cover every sector
            'sector'    => $clean['role'] === 'agriculturist' ? 'all' : $clean['sector'],
        ];
        if ($data['password'] !== '') {
            $row['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if ($old) {
            User::update($id, $row);
            [$o, $n] = AuditService::diff($old, $row, ['full_name', 'username', 'role', 'sector']);
            AuditService::log('update', 'users', $id, $o ?: null, ($n + (isset($row['password_hash']) ? ['password' => 'changed'] : [])) ?: null);
            if ($id === Auth::id()) {
                // Keep the top bar and permissions in step with the edit
                $_SESSION['user'] = array_merge($_SESSION['user'], array_diff_key($row, ['password_hash' => 1]));
            }
            flash('success', $row['full_name'] . ' was updated.');
        } else {
            $newId = User::insert($row + ['is_active' => 1]);
            AuditService::log('create', 'users', $newId, null, $row);
            flash('success', 'Account created for ' . $row['full_name'] . '.');
        }
        $this->redirect('/system?tab=users');
    }

    /** Deactivate / reactivate (accounts are never deleted, so the audit trail keeps its names). */
    public function toggleUser(string $id): void
    {
        $user = User::find((int) $id) ?? abort(404, 'User not found.');
        $activate = !$user['is_active'];
        if (!$activate && (int) $user['id'] === Auth::id()) {
            flash('danger', 'You cannot deactivate your own account.');
            $this->redirect('/system?tab=users');
        }
        if (!$activate && $user['role'] === 'agriculturist' && $this->activeAgriculturists((int) $user['id']) === 0) {
            flash('danger', 'At least one active Municipal Agriculturist account is required.');
            $this->redirect('/system?tab=users');
        }
        User::update((int) $user['id'], ['is_active' => $activate ? 1 : 0]);
        AuditService::log('update', 'users', (int) $user['id'], ['is_active' => (int) $user['is_active']], ['is_active' => $activate ? 1 : 0]);
        flash('success', $user['full_name'] . ($activate ? ' can log in again.' : ' can no longer log in.'));
        $this->redirect('/system?tab=users');
    }

    private function activeAgriculturists(int $excludeId): int
    {
        return (int) Model::query("SELECT COUNT(*) FROM users WHERE role = 'agriculturist' AND is_active = 1 AND id <> ?", [$excludeId])->fetchColumn();
    }

    // ------------------------------------------------------------------ audit log

    private function auditData(): array
    {
        $filters = [
            'table'     => (string) $this->input('table', ''),
            'action'    => (string) $this->input('action', ''),
            'user_id'   => (string) $this->input('user_id', ''),
            'date_from' => (string) $this->input('date_from', ''),
            'date_to'   => (string) $this->input('date_to', ''),
        ];
        $tables  = array_column(Model::raw('SELECT DISTINCT table_name FROM audit_logs ORDER BY table_name'), 'table_name', 'table_name');
        $users   = array_column(Model::raw('SELECT id, full_name FROM users ORDER BY full_name'), 'full_name', 'id');
        $actions = ['create' => 'Created', 'update' => 'Updated', 'archive' => 'Archived / deleted', 'override' => 'Duplicate override'];

        $where = [];
        $params = [];
        if (isset($tables[$filters['table']]))    { $where[] = 'a.table_name = ?'; $params[] = $filters['table']; }
        if (isset($actions[$filters['action']]))  { $where[] = 'a.action = ?';     $params[] = $filters['action']; }
        if (isset($users[$filters['user_id']]))   { $where[] = 'a.user_id = ?';    $params[] = $filters['user_id']; }
        foreach (['date_from' => '>=', 'date_to' => '<='] as $k => $op) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$k])) { $where[] = "DATE(a.created_at) $op ?"; $params[] = $filters[$k]; }
            else { $filters[$k] = ''; }
        }
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $perPage = 25;
        $total = (int) Model::query('SELECT COUNT(*) FROM audit_logs a' . $sqlWhere, $params)->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page  = min(max(1, (int) $this->input('page', 1)), $pages);
        $rows  = Model::raw('SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id' . $sqlWhere
            . ' ORDER BY a.created_at DESC, a.id DESC LIMIT ' . $perPage . ' OFFSET ' . (($page - 1) * $perPage), $params);

        return ['filters' => $filters, 'tables' => $tables, 'auditUsers' => $users, 'actions' => $actions,
                'result' => ['data' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage]];
    }

    // ------------------------------------------------------------------ backup

    /** Streams a full SQL dump (structure + data) of the application database. */
    public function backup(): void
    {
        $db   = Database::connect();
        $name = (string) config('db.name');
        $tables = array_column(Model::raw(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = "BASE TABLE" ORDER BY TABLE_NAME', [$name]), 'TABLE_NAME');
        // Generated columns can't be inserted into
        $generated = [];
        foreach (Model::raw('SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND EXTRA LIKE "%GENERATED%"', [$name]) as $c) {
            $generated[$c['TABLE_NAME']][$c['COLUMN_NAME']] = true;
        }

        AuditService::log('create', 'backup', 0, null, ['tables' => count($tables)], 'Database backup downloaded');

        $file = $name . '-backup-' . date('Y-m-d-His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Cache-Control: no-store');

        echo "-- MAO Information System backup\n-- Database: $name\n-- Created: " . date('Y-m-d H:i:s') . ' by ' . (Auth::user()['username'] ?? '') . "\n"
           . "-- Restore: create/select the database in phpMyAdmin, then Import this file.\n\n"
           . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n";

        foreach ($tables as $table) {
            $q = '`' . str_replace('`', '``', $table) . '`';
            $create = $db->query("SHOW CREATE TABLE $q")->fetch(PDO::FETCH_NUM)[1];
            echo "-- --------------------------------------------------------\n-- Table $table\n"
               . "DROP TABLE IF EXISTS $q;\n$create;\n\n";

            $stmt = $db->query("SELECT * FROM $q", PDO::FETCH_ASSOC);
            $batch = [];
            $cols = null;
            while ($row = $stmt->fetch()) {
                $row = array_diff_key($row, $generated[$table] ?? []);
                $cols ??= '(' . implode(', ', array_map(fn ($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row))) . ')';
                $batch[] = '(' . implode(', ', array_map(fn ($v) => $v === null ? 'NULL' : $db->quote((string) $v), $row)) . ')';
                if (count($batch) === 200) {
                    echo "INSERT INTO $q $cols VALUES\n" . implode(",\n", $batch) . ";\n";
                    $batch = [];
                }
            }
            if ($batch) {
                echo "INSERT INTO $q $cols VALUES\n" . implode(",\n", $batch) . ";\n";
            }
            echo "\n";
        }
        echo "SET FOREIGN_KEY_CHECKS = 1;\n";
        exit;
    }

    // ------------------------------------------------------------------ settings

    public function saveSettings(): void
    {
        if (!Setting::ready()) {
            flash('warning', 'Settings are not set up yet. Run database/schema_modules.sql first.');
            $this->redirect('/system?tab=settings');
        }
        $data = $rules = $labels = [];
        foreach (Setting::FIELDS as $key => [$label, , $max]) {
            $data[$key] = $this->input($key);
            $rules[$key] = ($key === 'office_name' ? 'required' : 'nullable') . '|max:' . $max . ($key === 'email' ? '|email' : '');
            $labels[$key] = $label;
        }
        $v = Validator::make($data, $rules, $labels);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($data);
            $this->redirect('/system?tab=settings');
        }
        $old = Setting::all();
        Setting::save($v->validated());
        [$o, $n] = AuditService::diff($old, $v->validated(), array_keys(Setting::FIELDS));
        if ($n) {
            AuditService::log('update', 'settings', 0, $o, $n);
        }
        flash('success', 'Settings saved.');
        $this->redirect('/system?tab=settings');
    }
}
