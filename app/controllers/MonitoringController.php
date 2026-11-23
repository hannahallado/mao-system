<?php
/** Process 4.0 Monitor Agricultural Activities. */
class MonitoringController extends Controller
{
    private const SAMPLE_ACTIVITIES = [
        [
            'date' => '2026-11-14',
            'type' => 'training',
            'title' => 'Post-harvest handling session',
            'barangay_id' => 10,
            'commodity' => 'Rice',
            'details' => 'Sample session for local rice growers.',
        ],
        [
            'date' => '2026-11-05',
            'type' => 'damage',
            'title' => 'Pest observation report',
            'barangay_id' => 11,
            'commodity' => 'Tomato',
            'details' => 'Sample report for demonstration purposes.',
        ],
        [
            'date' => '2026-10-28',
            'type' => 'damage',
            'title' => 'Crop damage assessment',
            'barangay_id' => 17,
            'commodity' => 'Rice',
            'details' => 'Sample assessment; no real farm is represented.',
        ],
        [
            'date' => '2026-10-16',
            'type' => 'production',
            'title' => 'Wet-season harvest record',
            'barangay_id' => 17,
            'commodity' => 'Rice',
            'details' => 'Sample production entry.',
        ],
        [
            'date' => '2026-10-06',
            'type' => 'production',
            'title' => 'Vegetable harvest record',
            'barangay_id' => 15,
            'commodity' => 'Eggplant',
            'details' => 'Sample production entry.',
        ],
        [
            'date' => '2026-09-30',
            'type' => 'damage',
            'title' => 'Pest observation report',
            'barangay_id' => 19,
            'commodity' => 'Corn',
            'details' => 'Sample report for demonstration purposes.',
        ],
        [
            'date' => '2026-09-18',
            'type' => 'training',
            'title' => 'Vegetable production orientation',
            'barangay_id' => 15,
            'commodity' => 'Vegetables',
            'details' => 'Sample activity entry.',
        ],
        [
            'date' => '2026-09-02',
            'type' => 'production',
            'title' => 'Corn production record',
            'barangay_id' => 12,
            'commodity' => 'Corn',
            'details' => 'Sample production entry.',
        ],
    ];

    public function index(): void
    {
        $filters = [
            'date_from'     => (string) $this->input('date_from', ''),
            'date_to'       => (string) $this->input('date_to', ''),
            'barangay_id'   => (string) $this->input('barangay_id', ''),
            'activity_type' => (string) $this->input('activity_type', ''),
            'commodity'     => (string) $this->input('commodity', ''),
        ];

        $barangays = Lookup::barangays();
        $activities = array_values(array_filter(self::SAMPLE_ACTIVITIES, static function (array $activity) use ($filters): bool {
            if ($filters['date_from'] !== '' && $activity['date'] < $filters['date_from']) {
                return false;
            }
            if ($filters['date_to'] !== '' && $activity['date'] > $filters['date_to']) {
                return false;
            }
            if ($filters['barangay_id'] !== '' && (string) $activity['barangay_id'] !== $filters['barangay_id']) {
                return false;
            }
            if ($filters['activity_type'] !== '' && $activity['type'] !== $filters['activity_type']) {
                return false;
            }
            if ($filters['commodity'] !== '' && stripos($activity['commodity'], $filters['commodity']) === false) {
                return false;
            }
            return true;
        }));

        foreach ($activities as &$activity) {
            $activity['barangay'] = $barangays[$activity['barangay_id']] ?? 'Unknown barangay';
        }
        unset($activity);

        $summary = array_fill_keys(['production', 'damage', 'training'], 0);
        foreach ($activities as $activity) {
            $summary[$activity['type']]++;
        }

        $this->view('monitoring/index', [
            'title'        => 'Monitoring Overview',
            'breadcrumbs'  => [['Home', '/home'], ['Agricultural Monitoring', null], ['Monitoring Overview', null]],
            'filters'      => $filters,
            'barangays'    => $barangays,
            'activities'   => $activities,
            'summary'      => $summary,
            'activityTypes'  => [
                'production' => 'Production',
                'damage'     => 'Damage / Pest',
                'training'   => 'Training / Activity',
            ],
        ]);
    }
}
