<?php
/** Local database backup and security guidance. */
class BackupController extends Controller
{
    public function index(): void
    {
        $files = glob(BASE_PATH . '/storage/backups/mao-system-*.sql');
        if ($files === false) {
            throw new RuntimeException('Unable to read the backup directory.');
        }
        usort($files, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $backups = array_map(static function (string $path): array {
            return [
                'filename' => basename($path),
                'created_at' => date('M d, Y g:i A', filemtime($path)),
                'size' => self::formatBytes((int) filesize($path)),
            ];
        }, $files);

        $this->view('system/backup', [
            'title' => 'Backup and Security',
            'breadcrumbs' => [['Home', '/home'], ['System Management', null], ['Backup and Security', null]],
            'backups' => $backups,
            'menu_active' => '/system/backup',
        ]);
    }

    public function backupNow(): void
    {
        if (!function_exists('proc_open')) {
            throw new RuntimeException('Database backup is unavailable because process execution is disabled in PHP.');
        }

        $directory = BASE_PATH . '/storage/backups';
        if (!is_dir($directory) || !is_writable($directory)) {
            throw new RuntimeException('The local backup directory is missing or not writable.');
        }

        $db = config('db');
        $timestamp = date('Ymd-His');
        $suffix = bin2hex(random_bytes(4));
        $backupPath = $directory . '/mao-system-' . $timestamp . '-' . $suffix . '.sql';
        $errorPath = $directory . '/backup-error-' . $suffix . '.log';
        $backupHandle = fopen($backupPath, 'x');
        if ($backupHandle === false) {
            throw new RuntimeException('Unable to create the database backup file.');
        }
        fclose($backupHandle);
        $errorHandle = fopen($errorPath, 'x');
        if ($errorHandle === false) {
            unlink($backupPath);
            throw new RuntimeException('Unable to create the temporary backup error log.');
        }
        fclose($errorHandle);

        $environment = getenv();
        if (!is_array($environment)) {
            $environment = [];
        }
        $environment['MYSQL_PWD'] = (string) $db['pass'];
        $command = [
            $this->dumpExecutable(),
            '--host=' . $db['host'],
            '--port=' . $db['port'],
            '--user=' . $db['user'],
            '--default-character-set=utf8mb4',
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
            '--databases',
            $db['name'],
        ];
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['file', $backupPath, 'wb'],
            2 => ['file', $errorPath, 'wb'],
        ], $pipes, $directory, $environment);

        if (!is_resource($process)) {
            unlink($backupPath);
            unlink($errorPath);
            throw new RuntimeException('Unable to start the database backup process.');
        }
        fclose($pipes[0]);
        $exitCode = proc_close($process);
        $error = trim((string) file_get_contents($errorPath));
        unlink($errorPath);

        if ($exitCode !== 0 || !is_file($backupPath) || filesize($backupPath) === 0) {
            if (is_file($backupPath)) {
                unlink($backupPath);
            }
            throw new RuntimeException('Database backup failed.' . ($error !== '' ? ' ' . $error : ''));
        }

        flash('success', 'Database backup created: ' . basename($backupPath));
        $this->redirect('/system/backup');
    }

    private function dumpExecutable(): string
    {
        $configured = getenv('MYSQLDUMP_PATH');
        $candidates = [
            is_string($configured) ? $configured : '',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mariadb-dump.exe',
        ];
        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_file($candidate)) {
                return $candidate;
            }
        }
        return 'mysqldump';
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $units = ['KB', 'MB', 'GB', 'TB'];
        $size = (float) $bytes;
        foreach ($units as $unit) {
            $size /= 1024;
            if ($size < 1024 || $unit === 'TB') {
                return number_format($size, 1) . ' ' . $unit;
            }
        }
        return number_format($size, 1) . ' TB';
    }
}
