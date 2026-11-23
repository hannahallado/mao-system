<?php
/** Process 4.0 Monitor Agricultural Activities. */
class TrainingController extends Controller
{
    private const SAMPLE_ACTIVITIES = [
        [
            'date' => '2026-11-14',
            'title' => 'Post-harvest handling session',
            'type' => 'Training',
            'barangay_id' => 10,
            'venue' => 'Canjusa Barangay Hall',
            'topic' => 'Rice post-harvest practices',
            'personnel' => 'Sample MAO Personnel',
            'participants' => 24,
            'status' => 'Completed',
        ],
        [
            'date' => '2026-09-18',
            'title' => 'Vegetable production orientation',
            'type' => 'Seminar',
            'barangay_id' => 15,
            'venue' => 'Palaka Norte Barangay Hall',
            'topic' => 'Vegetable production basics',
            'personnel' => 'Sample MAO Personnel',
            'participants' => 18,
            'status' => 'Completed',
        ],
        [
            'date' => '2026-08-25',
            'title' => 'Farm field consultation',
            'type' => 'Technical assistance',
            'barangay_id' => 19,
            'venue' => 'Ubay',
            'topic' => 'Sample crop monitoring visit',
            'personnel' => 'Sample MAO Personnel',
            'participants' => 1,
            'status' => 'Completed',
        ],
    ];

    private const ACTIVITY_TYPES = [
        'training' => 'Training',
        'seminar' => 'Seminar',
        'technical_assistance' => 'Technical assistance',
    ];

    public function index(): void
    {
        $barangays = Lookup::barangays();
        $activities = array_merge($_SESSION['training_demo_activities'] ?? [], self::SAMPLE_ACTIVITIES);
        $activities = array_map(static function (array $activity) use ($barangays): array {
            $activity['barangay'] = $barangays[$activity['barangay_id']] ?? 'Unknown barangay';
            $activity['display_date'] = date('M j, Y', strtotime($activity['date']));
            return $activity;
        }, $activities);
        usort($activities, static fn (array $a, array $b): int => strcmp($b['date'], $a['date']));

        $this->view('monitoring/training/index', [
            'title' => 'Training and Activities',
            'breadcrumbs' => [['Home', '/home'], ['Agricultural Monitoring', null], ['Training and Activities', null]],
            'activities' => $activities,
        ]);
    }

    public function create(): void
    {
        $this->view('monitoring/training/create', [
            'title' => 'Add Training/Activity',
            'breadcrumbs' => [
                ['Home', '/home'],
                ['Agricultural Monitoring', null],
                ['Training and Activities', '/monitoring/training'],
                ['Add Training/Activity', null],
            ],
            'activityTypes' => self::ACTIVITY_TYPES,
            'barangays' => Lookup::barangays(),
            'participants' => $this->activeD1Profiles(),
            'personnel' => Auth::user()['full_name'] ?? '',
        ]);
    }

    public function store(): void
    {
        $data = $this->only([
            'title', 'type', 'record_date', 'venue', 'barangay_id', 'topic', 'personnel',
        ]);
        foreach ($data as $field => $value) {
            if (!is_string($value)) {
                $data[$field] = null;
            }
        }

        $activityTypes = self::ACTIVITY_TYPES;
        $barangays = Lookup::barangays();
        $profiles = $this->activeD1Profiles();
        $validator = Validator::make($data, [
            'title' => 'required|max:150',
            'type' => 'required|in:' . implode(',', array_keys($activityTypes)),
            'record_date' => 'required|date',
            'venue' => 'required|max:150',
            'barangay_id' => 'required|in:' . implode(',', array_keys($barangays)),
            'topic' => 'required|max:500',
            'personnel' => 'required|max:120',
        ], [
            'record_date' => 'Activity date',
            'barangay_id' => 'Barangay',
        ]);
        $errors = $validator->errors();
        $clean = $validator->validated();

        $submittedParticipants = $_POST['participants'] ?? [];
        $participantIds = [];
        $participantsAreArray = is_array($submittedParticipants);
        if (!$participantsAreArray) {
            $submittedParticipants = [];
        }
        foreach ($submittedParticipants as $id) {
            if ((is_string($id) || is_int($id)) && ctype_digit((string) $id) && isset($profiles[(string) $id])) {
                $participantIds[] = (int) $id;
            }
        }
        $participantIds = array_values(array_unique($participantIds));
        if (!$participantsAreArray || !$participantIds || count($participantIds) !== count($submittedParticipants)) {
            $errors['participants'][] = 'Choose one or more active profiles from the D1 registry.';
        }

        if ($errors) {
            flash_errors($errors);
            flash_old($data + ['participants' => is_array($submittedParticipants) ? $submittedParticipants : []]);
            $this->redirect('/monitoring/training/create');
        }

        $activity = [
            'date' => $clean['record_date'],
            'title' => $clean['title'],
            'type' => $activityTypes[$clean['type']],
            'barangay_id' => (int) $clean['barangay_id'],
            'venue' => $clean['venue'],
            'topic' => $clean['topic'],
            'personnel' => $clean['personnel'],
            'participant_ids' => $participantIds,
            'participants' => count($participantIds),
            'status' => $clean['record_date'] > date('Y-m-d') ? 'Planned' : 'Completed',
        ];
        $_SESSION['training_demo_activities'] ??= [];
        $_SESSION['training_demo_activities'][] = $activity;

        flash('success', 'Training/activity added.');
        $this->redirect('/monitoring/training');
    }

    public function show(string $id): void
    {
        $this->mockup([
            'title'   => 'Activity Details',
            'group'   => 'Agricultural Monitoring',
            'process' => '4.0 Monitor Agricultural Activities',
            'status'  => 'mockup',
            'id'      => $id,
            'todo'    => [
                'Details and participant list',
            ],
        ]);
    }

    private function activeD1Profiles(): array
    {
        $rows = Model::raw(
            'SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix, b.name AS barangay_name
             FROM profiles p
             LEFT JOIN barangays b ON b.id = p.barangay_id
             WHERE p.deleted_at IS NULL AND p.status = ?
             ORDER BY p.last_name, p.first_name, p.id',
            ['active']
        );

        $profiles = [];
        foreach ($rows as $profile) {
            $profiles[(string) $profile['id']] = [
                'name' => Profile::fullName($profile),
                'barangay' => $profile['barangay_name'] ?? '',
            ];
        }
        return $profiles;
    }
}
