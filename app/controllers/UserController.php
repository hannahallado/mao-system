<?php
/** System user administration. */
class UserController extends Controller
{
    private const ROLES = [
        'technician' => 'Technician',
        'agriculturist' => 'Municipal Agriculturist',
    ];

    private const SECTORS = [
        'crops' => 'Crops',
        'livestock_poultry' => 'Livestock and Poultry',
        'fisheries' => 'Fisheries',
        'all' => 'All sectors',
    ];

    public function index(): void
    {
        $filters = [
            'q' => trim((string) $this->input('q', '')),
            'role' => (string) $this->input('role', ''),
            'sector' => (string) $this->input('sector', ''),
            'status' => (string) $this->input('status', ''),
        ];
        $where = [];
        $params = [];

        if ($filters['q'] !== '') {
            $where[] = '(full_name LIKE ? OR username LIKE ?)';
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $params[] = $like;
            $params[] = $like;
        }
        foreach (['role' => 'role', 'sector' => 'sector'] as $key => $column) {
            $options = $key === 'role' ? self::ROLES : self::SECTORS;
            if ($filters[$key] !== '' && isset($options[$filters[$key]])) {
                $where[] = '`' . $column . '` = ?';
                $params[] = $filters[$key];
            }
        }
        if ($filters['status'] === 'active' || $filters['status'] === 'inactive') {
            $where[] = 'is_active = ?';
            $params[] = $filters['status'] === 'active' ? 1 : 0;
        }

        $sql = 'SELECT id, full_name, username, role, sector, is_active, last_login_at
                FROM users' . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY is_active DESC, role, full_name, id';
        $users = User::raw($sql, $params);
        $activeAdministrators = (int) User::query(
            'SELECT COUNT(*) FROM users WHERE role = ? AND is_active = 1',
            ['agriculturist']
        )->fetchColumn();

        $this->view('users/index', [
            'title' => 'User Management',
            'breadcrumbs' => [['Home', '/home'], ['System Management', null], ['User Management', null]],
            'users' => $users,
            'filters' => $filters,
            'roles' => self::ROLES,
            'sectors' => self::SECTORS,
            'activeAdministrators' => $activeAdministrators,
            'activeCount' => count(array_filter($users, static fn ($user) => (int) $user['is_active'] === 1)),
            'inactiveCount' => count(array_filter($users, static fn ($user) => (int) $user['is_active'] !== 1)),
            'menu_active' => '/system/users',
        ]);
    }

    public function setStatus(string $id): void
    {
        if (!ctype_digit($id)) {
            abort(404, 'User not found.');
        }
        $action = (string) $this->input('action', '');
        if (!in_array($action, ['activate', 'deactivate'], true)) {
            abort(400, 'Invalid account status action.');
        }

        $userId = (int) $id;
        $activate = $action === 'activate';
        if (!$activate && Auth::id() === $userId) {
            flash('danger', 'You cannot deactivate your own account.');
            $this->redirect('/system/users');
        }

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id, full_name, username, role, is_active FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) {
                $pdo->rollBack();
                abort(404, 'User not found.');
            }

            if ((int) $user['is_active'] === (int) $activate) {
                $pdo->rollBack();
                flash('warning', 'That account is already ' . ($activate ? 'active.' : 'inactive.'));
                $this->redirect('/system/users');
            }

            if (!$activate && $user['role'] === 'agriculturist') {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE role = ? AND is_active = 1 FOR UPDATE');
                $stmt->execute(['agriculturist']);
                if (count($stmt->fetchAll()) <= 1) {
                    $pdo->rollBack();
                    flash('danger', 'The last active Municipal Agriculturist account cannot be deactivated.');
                    $this->redirect('/system/users');
                }
            }

            $newStatus = $activate ? 1 : 0;
            $stmt = $pdo->prepare('UPDATE users SET is_active = ?, updated_at = ? WHERE id = ?');
            $stmt->execute([$newStatus, date('Y-m-d H:i:s'), $userId]);
            AuditService::log('update', 'users', $userId, ['is_active' => (int) $user['is_active']], ['is_active' => $newStatus], 'User account ' . $action . 'd');
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        flash('success', $user['full_name'] . ' account ' . ($activate ? 'activated.' : 'deactivated.'));
        $this->redirect('/system/users');
    }

    public function create(): void
    {
        $this->mockup([
            'title'   => 'Add User',
            'group'   => 'System Management',
            'process' => 'System Management',
            'status'  => 'mockup',
            'todo'    => [
                'Name, username, role, sector, password',
            ],
        ]);
    }
}
