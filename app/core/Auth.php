<?php
/**
 * Session authentication + role/sector checks.
 * Roles: technician | agriculturist.  Sectors: crops | livestock_poultry | fisheries | all
 */
class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 300;

    public static function attempt(string $identifier, string $password): bool
    {
        if (self::lockedOut()) {
            return false;
        }

        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['login_fail'] = [
                'count' => (($_SESSION['login_fail']['count'] ?? 0) + 1),
                'time'  => time(),
            ];
            return false;
        }

        unset($_SESSION['login_fail']);
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => (int) $user['id'],
            'full_name' => $user['full_name'],
            'username'  => $user['username'],
            'role'      => $user['role'],
            'sector'    => $user['sector'],
        ];
        Database::connect()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')
            ->execute([date('Y-m-d H:i:s'), $user['id']]);
        return true;
    }

    /** Simple throttle (session based; enough for a prototype). */
    public static function lockedOut(): bool
    {
        $f = $_SESSION['login_fail'] ?? null;
        if (!$f) {
            return false;
        }
        if ($f['count'] >= self::MAX_ATTEMPTS && (time() - $f['time']) < self::LOCKOUT_SECONDS) {
            return true;
        }
        if ((time() - $f['time']) >= self::LOCKOUT_SECONDS) {
            unset($_SESSION['login_fail']);
        }
        return false;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        $user = $_SESSION['user'] ?? null;
        if (!is_array($user) || empty($user['id'])) {
            return false;
        }

        static $checkedId = null;
        static $isActive = false;
        $userId = (int) $user['id'];
        if ($checkedId !== $userId) {
            $stmt = Database::connect()->prepare('SELECT is_active FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $isActive = (bool) $stmt->fetchColumn();
            $checkedId = $userId;
        }

        if (!$isActive) {
            unset($_SESSION['user']);
            return false;
        }
        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    public static function isAgriculturist(): bool
    {
        return self::role() === 'agriculturist';
    }

    public static function hasRole(array|string $roles): bool
    {
        return in_array(self::role(), (array) $roles, true);
    }

    /** Agriculturist edits everything; technician only their own sector (or 'all'). */
    public static function canEditSector(string $sector): bool
    {
        if (!self::check()) {
            return false;
        }
        if (self::isAgriculturist()) {
            return true;
        }
        $mine = $_SESSION['user']['sector'] ?? '';
        return $mine === 'all' || $mine === $sector;
    }
}
