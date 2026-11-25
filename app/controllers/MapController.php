<?php
/** Process 3.0 Map Farm and Fishery Locations (GIS). */
class MapController extends Controller
{
    public function index(): void
    {
        $this->renderMap('Map Overview', 'all');
    }

    public function farms(): void
    {
        $this->renderMap('Farm Locations', 'farms');
    }

    public function fishery(): void
    {
        $this->renderMap('Fishery Locations', 'fishery');
    }

    private function renderMap(string $title, string $preset): void
    {
        $this->view('map/index', [
            'title' => $title,
            'preset' => $preset,
            'breadcrumbs' => [
                ['Home', '/home'],
                ['GIS Mapping', null],
                [$title, null],
            ],
            'features' => $this->mapFeatures(),
        ]);
    }

    /**
     * Load only fields the map displays. Table and column names are selected
     * from fixed allowlists so an absent or unfinished D1/D2 schema is safe.
     */
    private function mapFeatures(): array
    {
        $tables = [
            'farms' => ['farms', 'farm_records'],
            'fishery' => ['fishery_records', 'fisheries', 'fishery'],
        ];
        $tableNames = array_merge(...array_values($tables));
        $db = Database::connect();
        $placeholders = implode(', ', array_fill(0, count($tableNames), '?'));
        $stmt = $db->prepare(
            'SELECT TABLE_NAME, COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN (' . $placeholders . ')'
        );
        $stmt->execute(array_merge([config('db.name')], $tableNames));

        $columnsByTable = [];
        foreach ($stmt->fetchAll() as $column) {
            $columnsByTable[$column['TABLE_NAME']][$column['COLUMN_NAME']] = true;
        }

        $features = [];
        foreach ($tables as $kind => $candidates) {
            $table = null;
            foreach ($candidates as $candidate) {
                if (isset($columnsByTable[$candidate])) {
                    $table = $candidate;
                    break;
                }
            }
            if ($table === null) {
                continue;
            }

            $fieldOptions = [
                'id' => ['id', $kind === 'farms' ? 'farm_id' : 'fishery_id'],
                'name' => ['name', 'farm_name', 'fishery_name', 'site_name', 'record_name'],
                'barangay' => ['barangay', 'barangay_name', 'location_barangay'],
                'commodity' => ['commodity', 'commodity_name', 'crop', 'primary_commodity'],
                'category' => ['category', 'farm_type', 'fishery_type', 'type'],
                'latitude' => ['latitude', 'lat'],
                'longitude' => ['longitude', 'lng', 'lon'],
                'boundary' => ['boundary_geojson', 'boundary', 'geojson'],
                'farmer_id' => ['farmer_id'],
            ];
            $selected = [];
            foreach ($fieldOptions as $alias => $options) {
                foreach ($options as $option) {
                    if (isset($columnsByTable[$table][$option])) {
                        $selected[$alias] = $option;
                        break;
                    }
                }
            }
            if (!isset($selected['id'])) {
                continue;
            }

            $select = [];
            foreach ($selected as $alias => $column) {
                $select[] = '`' . $column . '` AS `' . $alias . '`';
            }
            $where = isset($columnsByTable[$table]['deleted_at']) ? ' WHERE `deleted_at` IS NULL' : '';
            $records = $db->query(
                'SELECT ' . implode(', ', $select) . ' FROM `' . $table . '`' . $where
            )->fetchAll();

            foreach ($records as $record) {
                $id = (string) $record['id'];
                $farmerId = $record['farmer_id'] ?? null;
                $features[] = [
                    'id' => $id,
                    'kind' => $kind,
                    'label' => trim((string) ($record['name'] ?? '')) ?: ucfirst($kind) . ' ' . $id,
                    'barangay' => trim((string) ($record['barangay'] ?? '')) ?: 'Not specified',
                    'commodity' => trim((string) ($record['commodity'] ?? '')) ?: 'Not specified',
                    'category' => trim((string) ($record['category'] ?? '')) ?: ucfirst($kind),
                    'latitude' => $this->coordinate($record['latitude'] ?? null, -90, 90),
                    'longitude' => $this->coordinate($record['longitude'] ?? null, -180, 180),
                    'boundary' => $this->geoJson($record['boundary'] ?? null),
                    'recordUrl' => url(($kind === 'farms' ? 'farms/' : 'fishery/') . rawurlencode($id)),
                    'profileUrl' => $farmerId !== null && $farmerId !== ''
                        ? url('farmers/' . rawurlencode((string) $farmerId))
                        : null,
                ];
            }
        }

        $features[] = [
            'id' => 'sample-patic-location',
            'kind' => 'farms',
            'label' => 'Sample farm location',
            'barangay' => 'Patic',
            'commodity' => 'Rice',
            'category' => 'Crop farm',
            'latitude' => 10.5220,
            'longitude' => 122.8070,
            'boundary' => null,
            'recordUrl' => url('farms/sample-patic-location'),
            'profileUrl' => null,
        ];

        $features[] = [
            'id' => 'sample-patic-fishpond',
            'kind' => 'fishery',
            'label' => 'Sample Tilapia Fishpond',
            'barangay' => 'Patic',
            'commodity' => 'Tilapia',
            'category' => 'Fishpond',
            'latitude' => 10.5162,
            'longitude' => 122.8038,
            'boundary' => null,
            'owner' => 'Sample Fisherfolk A',
            'description' => 'Freshwater tilapia fishpond',
            'area' => '1,200 sq. m.',
            'status' => 'Active',
            'recordUrl' => url('fishery'),
            'profileUrl' => null,
        ];

        $features[] = [
            'id' => 'sample-zone5-banca',
            'kind' => 'fishery',
            'label' => 'Sample Motorized Banca',
            'barangay' => 'Barangay Zone 5',
            'commodity' => 'Milkfish (Bangus)',
            'category' => 'Boat',
            'latitude' => 10.5108,
            'longitude' => 122.8065,
            'boundary' => null,
            'owner' => 'Sample Fisherfolk B',
            'description' => 'Motorized banca used for coastal fishing',
            'area' => 'Boat capacity: 8 persons',
            'status' => 'Active',
            'recordUrl' => url('fishery'),
            'profileUrl' => null,
        ];

        $features[] = [
            'id' => 'sample-zone7-aquaculture',
            'kind' => 'fishery',
            'label' => 'Sample Bangus Aquaculture Unit',
            'barangay' => 'Barangay Zone 7',
            'commodity' => 'Milkfish (Bangus)',
            'category' => 'Aquaculture unit',
            'latitude' => 10.5235,
            'longitude' => 122.7982,
            'boundary' => null,
            'owner' => 'Sample Fisherfolk C',
            'description' => 'Small-scale milkfish grow-out area',
            'area' => '2,500 sq. m.',
            'status' => 'Active',
            'recordUrl' => url('fishery'),
            'profileUrl' => null,
        ];

        return $features;
    }

    private function coordinate(mixed $value, float $min, float $max): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }
        $coordinate = (float) $value;
        return is_finite($coordinate) && $coordinate >= $min && $coordinate <= $max
            ? $coordinate
            : null;
    }

    private function geoJson(mixed $value): ?array
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        $geometry = json_decode($value, true);
        $supportedTypes = ['Feature', 'FeatureCollection', 'Point', 'LineString', 'MultiPoint',
            'MultiLineString', 'Polygon', 'MultiPolygon', 'GeometryCollection'];
        return is_array($geometry) && in_array($geometry['type'] ?? null, $supportedTypes, true)
            ? $geometry
            : null;
    }
}
