<?php
/** Process 4.0 Monitor Agricultural Activities. */
class DamageController extends Controller
{
    private const SAMPLE_RECORDS = [
        [
            'date' => '2026-11-05',
            'record_type' => 'Pest observation',
            'issue' => 'Sample pest observation report',
            'commodity' => 'Tomato',
            'barangay_id' => 11,
            'farmer_farm' => 'Sample Farmer D - Crossing Pulupandan Farm',
            'area_ha' => '0.3',
            'severity' => 'Moderate',
            'calamity_name' => '',
            'response_notes' => 'Sample monitoring visit scheduled.',
            'status' => 'For follow-up',
        ],
        [
            'date' => '2026-10-28',
            'record_type' => 'Crop damage',
            'issue' => 'Sample crop damage assessment',
            'commodity' => 'Rice',
            'barangay_id' => 17,
            'farmer_farm' => 'Sample Farmer A - Patic Farm',
            'area_ha' => '0.8',
            'severity' => 'Moderate',
            'calamity_name' => 'Sample weather event',
            'response_notes' => 'Sample field assessment completed.',
            'status' => 'Assessed',
        ],
        [
            'date' => '2026-09-30',
            'record_type' => 'Pest observation',
            'issue' => 'Sample pest observation report',
            'commodity' => 'Corn',
            'barangay_id' => 19,
            'farmer_farm' => 'Sample Farmer E - Ubay Farm',
            'area_ha' => '0.5',
            'severity' => 'Low',
            'calamity_name' => '',
            'response_notes' => 'Sample report noted for monitoring.',
            'status' => 'Monitored',
        ],
    ];

    private const FARM_REFERENCES = [
        'sample-patic' => 'Sample Farmer A - Patic Farm',
        'sample-palaka-norte' => 'Sample Farmer B - Palaka Norte Farm',
        'sample-culo' => 'Sample Farmer C - Culo Farm',
        'sample-crossing-pulupandan' => 'Sample Farmer D - Crossing Pulupandan Farm',
        'sample-ubay' => 'Sample Farmer E - Ubay Farm',
    ];

    private const REFERENCE_BARANGAYS = [
        'sample-patic' => 17,
        'sample-palaka-norte' => 15,
        'sample-culo' => 12,
        'sample-crossing-pulupandan' => 11,
        'sample-ubay' => 19,
    ];

    private const RECORD_TYPES = [
        'pest' => 'Pest observation',
        'crop_damage' => 'Crop damage',
    ];

    private const COMMODITIES = [
        'rice' => 'Rice',
        'corn' => 'Corn',
        'tomato' => 'Tomato',
        'eggplant' => 'Eggplant',
        'vegetables' => 'Vegetables',
    ];

    private const SEVERITIES = [
        'low' => 'Low',
        'moderate' => 'Moderate',
        'severe' => 'Severe',
    ];

    public function index(): void
    {
        $barangays = Lookup::barangays();
        $records = array_merge($_SESSION['damage_demo_records'] ?? [], self::SAMPLE_RECORDS);
        $records = array_map(static function (array $record) use ($barangays): array {
            $record['barangay'] = $barangays[$record['barangay_id']] ?? 'Unknown barangay';
            $record['display_date'] = date('M j, Y', strtotime($record['date']));
            return $record;
        }, $records);
        usort($records, static fn (array $a, array $b): int => strcmp($b['date'], $a['date']));

        $this->view('monitoring/damage/index', [
            'title' => 'Damage and Pest Monitoring',
            'breadcrumbs' => [['Home', '/home'], ['Agricultural Monitoring', null], ['Damage and Pest Monitoring', null]],
            'records' => $records,
        ]);
    }

    public function create(): void
    {
        $this->view('monitoring/damage/create', [
            'title' => 'Record Damage/Pest',
            'breadcrumbs' => [
                ['Home', '/home'],
                ['Agricultural Monitoring', null],
                ['Damage and Pest Monitoring', '/monitoring/damage'],
                ['Record Damage/Pest', null],
            ],
            'farmReferences' => self::FARM_REFERENCES,
            'recordTypes' => self::RECORD_TYPES,
            'commodities' => self::COMMODITIES,
            'severities' => self::SEVERITIES,
        ]);
    }

    public function store(): void
    {
        $data = $this->only([
            'farmer_farm', 'record_date', 'record_type', 'commodity', 'area_ha',
            'severity', 'calamity_name', 'response_notes',
        ]);
        foreach ($data as $field => $value) {
            if (!is_string($value)) {
                $data[$field] = null;
            }
        }

        $rules = [
            'farmer_farm' => 'required|in:' . implode(',', array_keys(self::FARM_REFERENCES)),
            'record_date' => 'required|date|not_future',
            'record_type' => 'required|in:' . implode(',', array_keys(self::RECORD_TYPES)),
            'commodity' => 'required|in:' . implode(',', array_keys(self::COMMODITIES)),
            'area_ha' => 'required|numeric',
            'severity' => 'required|in:' . implode(',', array_keys(self::SEVERITIES)),
            'calamity_name' => 'nullable|max:120',
            'response_notes' => 'nullable|max:1000',
        ];
        $labels = [
            'farmer_farm' => 'Affected farmer / farm',
            'record_date' => 'Report date',
            'record_type' => 'Report type',
            'area_ha' => 'Affected area',
        ];
        $validator = Validator::make($data, $rules, $labels);
        $errors = $validator->errors();
        $clean = $validator->validated();

        if (isset($clean['area_ha']) && is_numeric($clean['area_ha'])
            && (!is_finite((float) $clean['area_ha']) || (float) $clean['area_ha'] <= 0)) {
            $errors['area_ha'][] = 'Affected area must be greater than zero.';
        }

        if ($errors) {
            flash_errors($errors);
            flash_old($data);
            $this->redirect('/monitoring/damage/create');
        }

        $recordType = self::RECORD_TYPES[$clean['record_type']];
        $record = [
            'date' => $clean['record_date'],
            'record_type' => $recordType,
            'issue' => $recordType . ' report: ' . self::COMMODITIES[$clean['commodity']],
            'commodity' => self::COMMODITIES[$clean['commodity']],
            'barangay_id' => self::REFERENCE_BARANGAYS[$clean['farmer_farm']],
            'farmer_farm' => self::FARM_REFERENCES[$clean['farmer_farm']],
            'area_ha' => number_format((float) $clean['area_ha'], 2, '.', ''),
            'severity' => self::SEVERITIES[$clean['severity']],
            'calamity_name' => $clean['calamity_name'] ?? '',
            'response_notes' => $clean['response_notes'] ?? '',
            'status' => 'Reported',
        ];
        $_SESSION['damage_demo_records'] ??= [];
        $_SESSION['damage_demo_records'][] = $record;

        flash('success', 'Damage/pest report added.');
        $this->redirect('/monitoring/damage');
    }
}
