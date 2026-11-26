<?php
/**
 * Top bar notifications for the Municipal Agriculture Office, built from the
 * records themselves (nothing is queued), in four categories:
 *   registrations  new farmer / fisherfolk profiles
 *   assistance     beneficiaries to validate or approve, approved but not yet given,
 *                  duplicate-assistance overrides
 *   programs       programs starting or ending soon, upcoming trainings
 *   reminders      production report due dates, recent damage and pest reports
 *
 * Read state is per user in `notification_reads` (database/schema_modules.sql).
 * Each item has a stable key; when the underlying record changes, the key
 * changes and the item shows as unread again.
 */
class NotificationService
{
    public const CATEGORIES = [
        'registrations' => 'Registrations',
        'assistance'    => 'Agricultural Assistance',
        'programs'      => 'Programs and Activities',
        'reminders'     => 'Reports and Reminders',
    ];

    private const LIMIT = 10;

    private static ?array $cache = null;

    public static function readsReady(): bool
    {
        return RecordModule::tableReady('notification_reads');
    }

    /** Items for the logged-in user, unread first then newest. */
    public static function forCurrentUser(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $items = [];
        try {
            $items = array_merge(self::registrations(), self::assistance(), self::programs(), self::reminders());
        } catch (Throwable $e) {
            // Never break the page over the bell
            error_log('[notifications] ' . $e->getMessage() . PHP_EOL, 3, BASE_PATH . '/storage/logs/app.log');
        }

        $read = [];
        if ($items && self::readsReady()) {
            $keys = array_column($items, 'key');
            $read = array_flip(array_column(Model::raw(
                'SELECT notif_key FROM notification_reads WHERE user_id = ? AND notif_key IN (' . implode(',', array_fill(0, count($keys), '?')) . ')',
                array_merge([Auth::id()], $keys)), 'notif_key'));
        }
        foreach ($items as &$it) {
            $it['unread'] = !isset($read[$it['key']]);
        }
        unset($it);

        usort($items, fn ($a, $b) => [$b['unread'], $b['sort']] <=> [$a['unread'], $a['sort']]);
        return self::$cache = array_slice($items, 0, self::LIMIT);
    }

    public static function unreadCount(): int
    {
        return count(array_filter(self::forCurrentUser(), fn ($i) => $i['unread']));
    }

    public static function find(string $key): ?array
    {
        foreach (self::forCurrentUser() as $it) {
            if ($it['key'] === $key) {
                return $it;
            }
        }
        return null;
    }

    public static function markRead(array $keys): void
    {
        if (!$keys || !self::readsReady()) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        foreach (array_unique($keys) as $k) {
            Model::query('INSERT IGNORE INTO notification_reads (user_id, notif_key, read_at) VALUES (?, ?, ?)', [Auth::id(), mb_substr($k, 0, 100), $now]);
        }
        self::$cache = null;
    }

    // ------------------------------------------------------------------ categories

    private static function item(string $key, string $category, string $icon, string $title, string $text,
                                 string $url, string $when, ?string $tag = null, string $tagTone = 'muted', bool $upcoming = false): array
    {
        $ts = strtotime($when) ?: time();
        return [
            'key' => $key, 'category' => $category, 'icon' => $icon, 'title' => $title, 'text' => $text,
            'url' => $url, 'when' => $when, 'upcoming' => $upcoming, 'tag' => $tag, 'tagTone' => $tagTone,
            // Upcoming items rank by how soon they happen; past items by when they happened
            'sort' => $upcoming ? time() - max(0, $ts - time()) / 24 : $ts,
        ];
    }

    /** New profiles from the last 7 days, registered by someone else. */
    private static function registrations(): array
    {
        $rows = Model::raw(
            "SELECT p.id, p.created_at, b.name AS barangay,
                    CASE WHEN fp.id IS NOT NULL AND fs.id IS NOT NULL THEN 'farmer and fisherfolk'
                         WHEN fs.id IS NOT NULL THEN 'fisherfolk' ELSE 'farmer' END AS kind
             FROM profiles p
             JOIN barangays b ON b.id = p.barangay_id
             LEFT JOIN farming_profiles fp ON fp.id = p.id AND fp.deleted_at IS NULL
             LEFT JOIN fishery_profiles fs ON fs.id = p.id AND fs.deleted_at IS NULL
             WHERE p.deleted_at IS NULL AND p.created_at >= ? AND (p.created_by IS NULL OR p.created_by <> ?)
             ORDER BY p.created_at DESC LIMIT 5",
            [date('Y-m-d H:i:s', strtotime('-7 days')), Auth::id()]);
        $out = [];
        $titles = ['farmer' => 'Farmer', 'fisherfolk' => 'Fisherfolk', 'farmer and fisherfolk' => 'Farmer/Fisherfolk'];
        foreach ($rows as $r) {
            $out[] = self::item('reg:' . $r['id'], 'registrations', 'person-plus', 'New ' . $titles[$r['kind']] . ' Registration',
                'A new ' . $r['kind'] . ' was registered from ' . self::brgy($r['barangay']) . '.',
                '/farmers', $r['created_at'], 'New', 'info');
        }
        return $out;
    }

    /** "Brgy. Canjusa", but "Barangay Zone 1" stays as is (no "Brgy. Barangay"). */
    private static function brgy(?string $name): string
    {
        $name = trim((string) $name);
        return $name === '' ? '' : (preg_match('/^(brgy\.?|barangay)\s/i', $name) ? $name : 'Brgy. ' . $name);
    }

    /** Beneficiary workflow (identified -> validated -> approved -> served) and duplicate overrides. */
    private static function assistance(): array
    {
        $out = [];
        $byProgram = Model::raw(
            "SELECT pr.id, pr.name, pb.status, COUNT(*) n, MAX(COALESCE(pb.updated_at, pb.created_at)) last_change
             FROM program_beneficiaries pb JOIN programs pr ON pr.id = pb.program_id AND pr.deleted_at IS NULL
             WHERE pb.status IN ('identified', 'validated', 'approved') AND pr.status <> 'completed'
             GROUP BY pr.id, pr.name, pb.status");
        foreach ($byProgram as $r) {
            $n = (int) $r['n'];
            $who = count_label($n, 'beneficiary');
            $url = '/programs/' . (int) $r['id'] . '?tab=beneficiaries';
            $key = 'ben:' . $r['id'] . ':' . $r['status'] . ':' . $n . ':' . strtotime((string) $r['last_change']);
            if ($r['status'] === 'identified') {
                $out[] = self::item($key, 'assistance', 'clipboard-check', 'Beneficiaries to Validate',
                    $who . ' in ' . $r['name'] . ' ' . ($n === 1 ? 'needs' : 'need') . ' validation.', $url, $r['last_change'], 'For Review', 'warning');
            } elseif ($r['status'] === 'validated' && Auth::isAgriculturist()) {
                $out[] = self::item($key, 'assistance', 'patch-check', 'Assistance Awaiting Approval',
                    $who . ' in ' . $r['name'] . ' ' . ($n === 1 ? 'is' : 'are') . ' validated and waiting for your approval.', $url, $r['last_change'], 'For Review', 'warning');
            } elseif ($r['status'] === 'approved') {
                $out[] = self::item($key, 'assistance', 'box-seam', 'Pending Distribution',
                    $who . ' in ' . $r['name'] . ' ' . ($n === 1 ? 'is' : 'are') . ' approved but ' . ($n === 1 ? 'has' : 'have') . ' not received assistance yet.',
                    $url, $r['last_change'], 'Pending', 'info');
            }
        }

        if (Auth::isAgriculturist()) {
            $rows = Model::raw(
                "SELECT ar.id, ar.created_at, ar.date_distributed, CONCAT(p.first_name, ' ', p.last_name) AS name, COALESCE(t.name, 'Assistance') AS type
                 FROM assistance_records ar
                 JOIN program_beneficiaries pb ON pb.id = ar.program_beneficiary_id
                 JOIN profiles p ON p.id = pb.profile_id
                 LEFT JOIN assistance_types t ON t.id = ar.assistance_type_id
                 WHERE ar.duplicate_flag = 1 AND ar.date_distributed >= ?
                 ORDER BY ar.date_distributed DESC LIMIT 5", [date('Y-m-d', strtotime('-30 days'))]);
            foreach ($rows as $r) {
                $out[] = self::item('dup:' . $r['id'], 'assistance', 'exclamation-triangle', 'Duplicate Assistance Override',
                    $r['type'] . ' was given to ' . $r['name'] . ' again with an override reason.', '/assistance',
                    $r['created_at'] ?: $r['date_distributed'], 'For Review', 'warning');
            }
        }
        return $out;
    }

    /** Programs starting in the next 14 days or ending in the next 7, trainings in the next 14. */
    private static function programs(): array
    {
        $out = [];
        $today = date('Y-m-d');
        foreach (Model::raw(
            "SELECT id, name, start_date FROM programs WHERE deleted_at IS NULL AND status <> 'completed'
             AND start_date BETWEEN ? AND ? ORDER BY start_date", [$today, date('Y-m-d', strtotime('+14 days'))]) as $r) {
            $out[] = self::item('prog-start:' . $r['id'] . ':' . $r['start_date'], 'programs', 'calendar-event', 'Upcoming Agricultural Program',
                $r['name'] . ' is scheduled to start on ' . date('F j, Y', strtotime($r['start_date'])) . '.',
                '/programs/' . (int) $r['id'], $r['start_date'], 'Upcoming', 'info', true);
        }
        foreach (Model::raw(
            "SELECT id, name, end_date FROM programs WHERE deleted_at IS NULL AND status = 'ongoing'
             AND end_date BETWEEN ? AND ? ORDER BY end_date", [$today, date('Y-m-d', strtotime('+7 days'))]) as $r) {
            $out[] = self::item('prog-end:' . $r['id'] . ':' . $r['end_date'], 'programs', 'hourglass-split', 'Program Ending Soon',
                $r['name'] . ' ends on ' . date('F j, Y', strtotime($r['end_date'])) . '. Finish the remaining distributions.',
                '/programs/' . (int) $r['id'], $r['end_date'], 'Reminder', 'muted', true);
        }
        if (RecordModule::tableReady('trainings')) {
            $types = RecordModule::module('monitoring')['tabs']['training']['fields']['activity_type']['options'];
            foreach (Model::raw(
                'SELECT t.id, t.title, t.activity_type, t.date_held, t.venue, b.name AS barangay FROM trainings t
                 LEFT JOIN barangays b ON b.id = t.barangay_id
                 WHERE t.deleted_at IS NULL AND t.date_held BETWEEN ? AND ? ORDER BY t.date_held',
                [$today, date('Y-m-d', strtotime('+14 days'))]) as $r) {
                $where = $r['venue'] ?: self::brgy($r['barangay']);
                $out[] = self::item('training:' . $r['id'] . ':' . $r['date_held'], 'programs', 'people', 'Upcoming ' . ($types[$r['activity_type']] ?? 'Activity'),
                    $r['title'] . ' is on ' . date('F j, Y', strtotime($r['date_held'])) . ($where ? ' at ' . $where : '') . '.',
                    '/monitoring?tab=training', $r['date_held'], 'Upcoming', 'info', true);
            }
        }
        return $out;
    }

    /** Twice-monthly production report due date, and damage reports from the last 7 days. */
    private static function reminders(): array
    {
        $out = [];
        if (RecordModule::tableReady('production_records')) {
            $day  = (int) date('j');
            $half = $day <= 15 ? 1 : 2;
            $due  = $half === 1 ? date('Y-m-15') : date('Y-m-t');
            $daysLeft = (int) round((strtotime($due) - strtotime(date('Y-m-d'))) / 86400);
            if ($daysLeft <= 5) {
                $month = date('Y-m');
                $count = (int) Model::query('SELECT COUNT(*) FROM production_records WHERE deleted_at IS NULL AND period_month = ? AND period_half = ?', [$month, $half])->fetchColumn();
                $out[] = self::item('due:production:' . $month . ':' . $half, 'reminders', 'calendar-check', 'Report Submission Reminder',
                    'The production monitoring report for the ' . ($half === 1 ? '1st' : '2nd') . ' half of ' . date('F Y') . ' is due on '
                    . date('F j, Y', strtotime($due)) . '. ' . count_label($count, 'record') . ' entered so far.',
                    '/monitoring?tab=production', $due . ' 23:59:00', 'Reminder', 'muted', true);
            }
        }
        if (RecordModule::tableReady('damage_reports')) {
            $opts = RecordModule::module('monitoring')['tabs']['damage']['fields'];
            foreach (Model::raw(
                'SELECT d.id, d.created_at, d.cause_type, d.calamity_name, d.commodity, d.severity, b.name AS barangay
                 FROM damage_reports d JOIN barangays b ON b.id = d.barangay_id
                 WHERE d.deleted_at IS NULL AND d.created_at >= ? AND (d.created_by IS NULL OR d.created_by <> ?)
                 ORDER BY d.created_at DESC LIMIT 5', [date('Y-m-d H:i:s', strtotime('-7 days')), Auth::id()]) as $r) {
                $severe = in_array($r['severity'], ['high', 'total'], true);
                $out[] = self::item('damage:' . $r['id'], 'reminders', 'bug', 'Damage Report Filed',
                    ($r['calamity_name'] ?: ($opts['cause_type']['options'][$r['cause_type']] ?? $r['cause_type'])) . ' damage on '
                    . strtolower($opts['commodity']['options'][$r['commodity']] ?? $r['commodity']) . ' in ' . self::brgy($r['barangay'])
                    . ' (' . strtolower($opts['severity']['options'][$r['severity']] ?? $r['severity']) . ').',
                    '/monitoring?tab=damage', $r['created_at'], $severe ? 'Urgent' : 'Alert', $severe ? 'danger' : 'warning');
            }
        }
        return $out;
    }
}
