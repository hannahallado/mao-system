<?php
/** Process 2.0 - Farm Records (functional). */
class FarmController extends ResourceController
{
    protected function build(): array
    {
        $brgy = Lookup::barangays();
        $crops = Lookup::commodities(['rice', 'corn', 'sugarcane', 'high_value_crop']);
        return [
            'model' => 'Farm', 'table' => 'farm_parcels', 'path' => '/farms', 'title' => 'Farm Records', 'singular' => 'Farm',
            'sector' => 'crops', 'orderBy' => 'p.last_name, p.first_name, r.parcel_name',
            'select' => 'r.*, p.last_name, p.first_name, p.middle_name, p.suffix, b.name AS barangay_name, c.name AS commodity_name',
            'from' => 'FROM farm_parcels r JOIN profiles p ON p.id = r.profile_id
                       LEFT JOIN barangays b ON b.id = r.barangay_id LEFT JOIN commodities c ON c.id = r.commodity_id',
            'search' => ['r.parcel_name', 'p.last_name', 'p.first_name'],
            'filters' => [
                'barangay_id' => ['label' => 'All barangays', 'column' => 'r.barangay_id', 'options' => $brgy],
                'commodity_id' => ['label' => 'All commodities', 'column' => 'r.commodity_id', 'options' => $crops],
                'ecosystem' => ['label' => 'All ecosystems', 'column' => 'r.ecosystem', 'options' => Farm::ECOSYSTEMS],
            ],
            'stats' => [
                'Farms' => ['COUNT(*)', 0], 'Total area (ha)' => ['SUM(r.area_ha)', 2],
                'Irrigated (ha)' => ['SUM(CASE WHEN r.ecosystem = "irrigated" THEN r.area_ha ELSE 0 END)', 2],
                'Rainfed (ha)' => ['SUM(CASE WHEN r.ecosystem = "rainfed" THEN r.area_ha ELSE 0 END)', 2],
            ],
            'columns' => [
                ['label' => 'Parcel', 'link' => true, 'key' => 'parcel_name'],
                ['label' => 'Farmer', 'render' => fn ($r) => self::profileLink($r)],
                ['label' => 'Barangay', 'key' => 'barangay_name'],
                ['label' => 'Commodity', 'key' => 'commodity_name'],
                ['label' => 'Ecosystem', 'render' => fn ($r) => Ui::badge(Farm::ECOSYSTEMS[$r['ecosystem']], $r['ecosystem'] === 'irrigated' ? 'info' : 'muted')],
                ['label' => 'Area (ha)', 'class' => 'num', 'render' => fn ($r) => number_format((float) $r['area_ha'], 2)],
            ],
            'defaults' => ['status' => 'active'],
            'label' => fn ($r) => $r['parcel_name'],
            'detail' => fn ($r) => [
                'Farmer' => fn () => self::profileLink($r),
                'Barangay' => $r['barangay_name'], 'Main commodity' => $r['commodity_name'],
                'Area' => number_format((float) $r['area_ha'], 2) . ' ha',
                'Ecosystem' => Farm::ECOSYSTEMS[$r['ecosystem']] ?? $r['ecosystem'], 'Tenure' => Farm::TENURES[$r['tenure']] ?? $r['tenure'],
                'Irrigation source' => $r['irrigation_source'], 'Status' => ucfirst($r['status']),
                'Coordinates' => $r['latitude'] !== null ? $r['latitude'] . ', ' . $r['longitude'] : null,
                'Boundary' => $r['boundary_geojson'] ? 'GeoJSON boundary saved' : null,
            ],
            'fields' => [
                ['section' => 'Parcel', 'name' => 'profile_id', 'label' => 'Farmer', 'type' => 'select', 'options' => Lookup::profiles(), 'rules' => 'required|integer|exists:profiles,id', 'col' => 'col-md-6', 'placeholder' => 'Select registered farmer', 'help' => 'Only active profiles from the registry.'],
                ['name' => 'parcel_name', 'label' => 'Parcel name', 'rules' => 'required|max:100', 'col' => 'col-md-6', 'maxlength' => 100],
                ['name' => 'barangay_id', 'label' => 'Barangay', 'type' => 'select', 'options' => $brgy, 'rules' => 'required|integer|exists:barangays,id', 'col' => 'col-md-4', 'placeholder' => 'Select barangay'],
                ['name' => 'commodity_id', 'label' => 'Main commodity', 'type' => 'select', 'options' => $crops, 'rules' => 'required|integer|exists:commodities,id', 'col' => 'col-md-4', 'placeholder' => 'Select commodity'],
                ['name' => 'area_ha', 'label' => 'Area (hectares)', 'type' => 'number', 'min' => '0.01', 'step' => '0.01', 'rules' => 'required|numeric', 'positive' => true, 'col' => 'col-md-4'],
                ['section' => 'Land and water', 'name' => 'ecosystem', 'label' => 'Ecosystem', 'type' => 'select', 'options' => Farm::ECOSYSTEMS, 'rules' => 'required|' . self::in(Farm::ECOSYSTEMS), 'col' => 'col-md-3', 'placeholder' => 'Select', 'help' => 'Feeds the masterlist irrigated/rainfed totals.'],
                ['name' => 'tenure', 'label' => 'Tenure', 'type' => 'select', 'options' => Farm::TENURES, 'rules' => 'required|' . self::in(Farm::TENURES), 'col' => 'col-md-3', 'placeholder' => 'Select'],
                ['name' => 'irrigation_source', 'label' => 'Irrigation source', 'rules' => 'nullable|max:100', 'col' => 'col-md-3', 'maxlength' => 100],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'rules' => 'required|in:active,inactive', 'col' => 'col-md-3'],
                ['section' => 'Map location (optional)', 'name' => 'latitude', 'label' => 'Latitude', 'type' => 'number', 'step' => 'any', 'rules' => 'nullable|numeric', 'col' => 'col-md-3', 'placeholder' => '10.5192'],
                ['name' => 'longitude', 'label' => 'Longitude', 'type' => 'number', 'step' => 'any', 'rules' => 'nullable|numeric', 'col' => 'col-md-3', 'placeholder' => '122.8034'],
                ['name' => 'boundary_geojson', 'label' => 'Boundary (GeoJSON)', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6', 'help' => 'Optional polygon; shown on the map.'],
            ],
        ];
    }

    protected function validateExtra(array $clean, array &$errors, ?array $existing): void
    {
        $this->validateGeo($clean, $errors);
    }
}
