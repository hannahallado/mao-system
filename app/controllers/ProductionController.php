<?php
/** Process 4.0 Monitor Agricultural Activities. */
class ProductionController extends Controller
{
    private const SAMPLE_RECORDS = [
        [
            'date' => '2026-10-16',
            'period' => 'Wet season',
            'commodity' => 'Rice',
            'barangay_id' => 17,
            'farmer_farm' => 'Sample Farmer A - Patic Farm',
            'area_ha' => '4.2',
            'volume' => '18.5',
            'unit' => 'metric tons',
        ],
        [
            'date' => '2026-10-06',
            'period' => 'Wet season',
            'commodity' => 'Eggplant',
            'barangay_id' => 15,
            'farmer_farm' => 'Sample Farmer B - Palaka Norte Farm',
            'area_ha' => '1.1',
            'volume' => '8.4',
            'unit' => 'metric tons',
        ],
        [
            'date' => '2026-09-02',
            'period' => 'Wet season',
            'commodity' => 'Corn',
            'barangay_id' => 12,
            'farmer_farm' => 'Sample Farmer C - Culo Farm',
            'area_ha' => '2.8',
            'volume' => '11.2',
            'unit' => 'metric tons',
        ],
    ];

    private const DEMO_REFERENCES = [
        'sample-patic' => 'Sample Farmer A - Patic Farm',
        'sample-palaka-norte' => 'Sample Farmer B - Palaka Norte Farm',
        'sample-culo' => 'Sample Farmer C - Culo Farm',
    ];

    private const COMMODITIES = [
        'rice' => 'Rice',
        'corn' => 'Corn',
        'eggplant' => 'Eggplant',
        'tomato' => 'Tomato',
        'vegetables' => 'Vegetables',
    ];

    private const UNITS = [
        'kg' => 'Kilograms',
        'metric_tons' => 'Metric tons',
        'sacks' => 'Sacks',
    ];

    public function index(): void
    {
        $query = (string) $this->input('q', '');
        $barangays = Lookup::barangays();
        $records = array_merge(self::SAMPLE_RECORDS, $_SESSION['production_demo_records'] ?? []);
        $records = array_map(static function (array $record) use ($barangays): array {
            $record['barangay'] = $barangays[$record['barangay_id']] ?? 'Unknown barangay';
            $record['display_date'] = date('M j, Y', strtotime($record['date']));
            return $record;
        }, $records);

        $terms = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        if ($terms) {
            $records = array_values(array_filter($records, static function (array $record) use ($terms): bool {
                $searchable = strtolower(implode(' ', [
                    $record['display_date'],
                    $record['period'],
                    $record['commodity'],
                    $record['barangay'],
                    $record['farmer_farm'],
                    $record['area_ha'],
                    $record['volume'],
                    $record['unit'],
                ]));
                foreach ($terms as $term) {
                    if (stripos($searchable, $term) === false) {
                        return false;
                    }
                }
                return true;
            }));
        }

        $this->view('monitoring/production/index', [
            'title' => 'Production Monitoring',
            'breadcrumbs' => [['Home', '/home'], ['Agricultural Monitoring', null], ['Production Monitoring', null]],
            'query' => $query,
            'records' => $records,
        ]);
    }

    public function create(): void
    {
        $this->view('monitoring/production/create', [
            'title' => 'Record Production',
            'breadcrumbs' => [
                ['Home', '/home'],
                ['Agricultural Monitoring', null],
                ['Production Monitoring', '/monitoring/production'],
                ['Record Production', null],
            ],
            'references' => self::DEMO_REFERENCES,
            'commodities' => self::COMMODITIES,
            'periods' => SystemSetting::productionPeriods(),
            'units' => self::UNITS,
        ]);
    }

    public function store(): void
    {
        $periods = SystemSetting::productionPeriods();
        $data = $this->only([
            'farmer_farm', 'commodity', 'period', 'record_date', 'area_ha', 'volume', 'unit',
        ]);
        $rules = [
            'farmer_farm' => 'required|in:' . implode(',', array_keys(self::DEMO_REFERENCES)),
            'commodity' => 'required|in:' . implode(',', array_keys(self::COMMODITIES)),
            'period' => 'required|in:' . implode(',', array_keys($periods)),
            'record_date' => 'required|date|not_future',
            'area_ha' => 'required|numeric',
            'volume' => 'required|numeric',
            'unit' => 'required|in:' . implode(',', array_keys(self::UNITS)),
        ];
        $labels = [
            'farmer_farm' => 'Farmer / farm',
            'record_date' => 'Production date',
            'area_ha' => 'Area',
            'volume' => 'Production volume',
        ];
        $validator = Validator::make($data, $rules, $labels);
        $errors = $validator->errors();
        $clean = $validator->validated();

        foreach (['area_ha' => 'Area', 'volume' => 'Production volume'] as $field => $label) {
            if (isset($clean[$field]) && is_numeric($clean[$field])
                && (!is_finite((float) $clean[$field]) || (float) $clean[$field] <= 0)) {
                $errors[$field][] = "$label must be greater than zero.";
            }
        }

        if ($errors) {
            flash_errors($errors);
            flash_old($data);
            $this->redirect('/monitoring/production/create');
        }

        $reference = self::DEMO_REFERENCES[$clean['farmer_farm']];
        $barangayId = match ($clean['farmer_farm']) {
            'sample-patic' => 17,
            'sample-palaka-norte' => 15,
            'sample-culo' => 12,
        };
        $record = [
            'date' => $clean['record_date'],
            'period' => $periods[$clean['period']],
            'commodity' => self::COMMODITIES[$clean['commodity']],
            'barangay_id' => $barangayId,
            'farmer_farm' => $reference,
            'area_ha' => number_format((float) $clean['area_ha'], 2, '.', ''),
            'volume' => number_format((float) $clean['volume'], 2, '.', ''),
            'unit' => self::UNITS[$clean['unit']],
        ];
        $_SESSION['production_demo_records'] ??= [];
        $_SESSION['production_demo_records'][] = $record;

        flash('success', 'Production record added.');
        $this->redirect('/monitoring/production');
    }
}
