<?php
/**
 * Duplicate assistance check (plan section 7.2).
 *
 * check($profileId, $typeId, $programId, $date) returns:
 *   ['level' => 'clear' | 'possible' | 'duplicate', 'matches' => [ match, ... ]]
 *
 *   duplicate  the same assistance type was already given to this person within the type's
 *              duplicate_window_months (or ever, when the window is empty)
 *   possible   the same CATEGORY (e.g. seeds) was given under another program within that window
 *   clear      nothing similar
 *
 * match = id, label, meta, reason, strength ('strong' | 'possible'), url  (same shape the warning modal uses)
 *
 * The window is a business rule to confirm with the MAO: it lives in assistance_types.duplicate_window_months.
 */
class DuplicateAssistanceService
{
    private const MAX_MATCHES = 5;

    public static function check(int $profileId, int $typeId, int $programId, string $date): array
    {
        $type = Model::rawOne('SELECT * FROM assistance_types WHERE id = ?', [$typeId]);
        if (!$type) {
            return ['level' => 'clear', 'matches' => []];
        }
        $window = $type['duplicate_window_months'] === null ? null : (int) $type['duplicate_window_months'];

        // Everything this person already received in the same category (any program).
        $rows = Model::raw(
            'SELECT ar.id, ar.assistance_no, ar.quantity, ar.unit, ar.date_distributed,
                    t.id AS type_id, t.name AS type_name, pr.id AS program_id, pr.name AS program_name
             FROM assistance_records ar
             JOIN assistance_types t ON t.id = ar.assistance_type_id
             JOIN program_beneficiaries pb ON pb.id = ar.program_beneficiary_id
             JOIN programs pr ON pr.id = pb.program_id
             WHERE pb.profile_id = ? AND t.category = ?
             ORDER BY ar.date_distributed DESC, ar.id DESC',
            [$profileId, $type['category']]
        );

        $matches = [];
        foreach ($rows as $r) {
            $within   = self::withinWindow($r['date_distributed'], $date, $window);
            $sameType = (int) $r['type_id'] === $typeId;

            if ($sameType && $within) {
                $reason = $window === null ? 'Same assistance already received' : "Same assistance within the last $window months";
                $matches[] = self::makeMatch($r, $reason, 'strong', $profileId);
            } elseif (!$sameType && (int) $r['program_id'] !== $programId && $within) {
                $matches[] = self::makeMatch($r, 'Same category (' . ucfirst(str_replace('_', ' ', $type['category'])) . ') from another program', 'possible', $profileId);
            } elseif ($sameType && !$within) {
                continue;   // same type but outside the window: allowed
            }
        }

        usort($matches, fn ($a, $b) => ($a['strength'] === 'strong' ? 0 : 1) <=> ($b['strength'] === 'strong' ? 0 : 1));
        $matches = array_slice($matches, 0, self::MAX_MATCHES);

        $level = 'clear';
        if ($matches) {
            $level = $matches[0]['strength'] === 'strong' ? 'duplicate' : 'possible';
        }
        return ['level' => $level, 'matches' => $matches];
    }

    /** True when the two dates are no further apart than $months (or always, when $months is null). */
    private static function withinWindow(string $earlier, string $new, ?int $months): bool
    {
        if ($months === null) {
            return true;
        }
        $a = strtotime($earlier);
        $b = strtotime($new);
        $lo = min($a, $b);
        $hi = max($a, $b);
        return strtotime("+$months months", $lo) >= $hi;
    }

    /** JSON the warning modal (public/assets/js/duplicate-guard.js) understands. */
    public static function toPayload(array $result, ?array $type = null): array
    {
        $dup = $result['level'] === 'duplicate';
        $first = $result['matches'][0] ?? null;
        return [
            'level'           => 'warning',
            'title'           => $dup ? 'Duplicate Assistance Found' : 'Possible Duplicate Assistance',
            'message'         => $dup
                ? 'This beneficiary already received this assistance' . ($first ? ' (' . $first['meta'] . ')' : '') . '. An override reason is required to record it again.'
                : 'This beneficiary received similar assistance from another program. Check it before recording; an override reason is required to continue.',
            'matches'         => $result['matches'],
            'requires_reason' => true,
            'confirm_label'   => 'Override and save',
            'reason_label'    => 'Override reason',
            'reason_field'    => 'override_reason',
            'reason_min'      => 5,
            'match_action'    => 'View history',
        ];
    }

    private static function makeMatch(array $r, string $reason, string $strength, int $profileId): array
    {
        $qty = rtrim(rtrim(number_format((float) $r['quantity'], 2, '.', ','), '0'), '.');
        return [
            'id'       => (int) $r['id'],
            'label'    => $r['type_name'] . ' - ' . $r['program_name'],
            'meta'     => trim($qty . ' ' . ($r['unit'] ?? '')) . ' · ' . format_date($r['date_distributed']) . ' · ' . $r['assistance_no'],
            'reason'   => $reason,
            'strength' => $strength,
            'url'      => url('/profiles/' . $profileId . '/assistance-history'),
        ];
    }
}
