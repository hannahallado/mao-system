<?php
/** Process 2.0 - Fishery Records: boats, gear, fishponds and aquaculture units (functional). */
class FisheryController extends ResourceController
{
    protected function build(): array
    {
        $brgy = Lookup::barangays();
        $species = Lookup::commodities(['fishery']);
        return [
            'model' => 'FisheryAsset', 'table' => 'fishery_assets', 'path' => '/fishery', 'title' => 'Fishery Records', 'singular' => 'Fishery record',
            'sector' => 'fisheries', 'orderBy' => 'p.last_name, p.first_name, r.asset_type',
            'select' => 'r.*, p.last_name, p.first_name, p.middle_name, p.suffix, b.name AS barangay_name, c.name AS species_name',
            'from' => 'FROM fishery_assets r JOIN profiles p ON p.id = r.profile_id
                       LEFT JOIN barangays b ON b.id = r.barangay_id LEFT JOIN commodities c ON c.id = r.commodity_id',
            'search' => ['r.description', 'r.registration_no', 'p.last_name', 'p.first_name'],
            'filters' => [
                'asset_type' => ['label' => 'All types', 'column' => 'r.asset_type', 'options' => FisheryAsset::TYPES],
                'barangay_id' => ['label' => 'All barangays', 'column' => 'r.barangay_id', 'options' => $brgy],
            ],
            'stats' => [
                'Records' => ['COUNT(*)', 0],
                'Boats' => ['SUM(r.asset_type = "boat")', 0],
                'Fishponds' => ['SUM(r.asset_type = "fishpond")', 0],
                'Pond area (sqm)' => ['SUM(CASE WHEN r.asset_type IN ("fishpond","aquaculture_unit") THEN r.area_sqm ELSE 0 END)', 0],
            ],
            'columns' => [
                ['label' => 'Description', 'link' => true, 'key' => 'description'],
                ['label' => 'Type', 'render' => fn ($r) => Ui::badge(FisheryAsset::TYPES[$r['asset_type']], 'info')],
                ['label' => 'Owner', 'render' => fn ($r) => self::profileLink($r)],
                ['label' => 'Barangay', 'key' => 'barangay_name'],
                ['label' => 'Reg. no.', 'render' => fn ($r) => $r['registration_no'] ? '<code>' . e($r['registration_no']) . '</code>' : '<span class="text-muted">&mdash;</span>'],
                ['label' => 'Status', 'render' => fn ($r) => Ui::statusBadge($r['status'])],
            ],
            'defaults' => ['status' => 'active'],
            'label' => fn ($r) => $r['description'],
            'detail' => fn ($r) => [
                'Owner' => fn () => self::profileLink($r), 'Type' => FisheryAsset::TYPES[$r['asset_type']] ?? $r['asset_type'],
                'Barangay' => $r['barangay_name'], 'Registration no.' => $r['registration_no'],
                'Boat type' => $r['boat_type'], 'Gear type' => $r['gear_type'],
                'Area' => $r['area_sqm'] !== null ? number_format((float) $r['area_sqm'], 2) . ' sqm' : null,
                'Species' => $r['species_name'], 'Status' => ucfirst($r['status']),
                'Coordinates' => $r['latitude'] !== null ? $r['latitude'] . ', ' . $r['longitude'] : null,
            ],
            'fields' => [
                ['section' => 'Asset', 'name' => 'profile_id', 'label' => 'Fisherfolk / owner', 'type' => 'select', 'options' => Lookup::profiles(), 'rules' => 'required|integer|exists:profiles,id', 'col' => 'col-md-6', 'placeholder' => 'Select registered profile'],
                ['name' => 'asset_type', 'label' => 'Asset type', 'type' => 'select', 'options' => FisheryAsset::TYPES, 'rules' => 'required|' . self::in(FisheryAsset::TYPES), 'col' => 'col-md-3', 'placeholder' => 'Select type'],
                ['name' => 'barangay_id', 'label' => 'Barangay', 'type' => 'select', 'options' => $brgy, 'rules' => 'required|integer|exists:barangays,id', 'col' => 'col-md-3', 'placeholder' => 'Select barangay'],
                ['name' => 'description', 'label' => 'Description', 'rules' => 'required|max:150', 'col' => 'col-md-6', 'maxlength' => 150, 'help' => 'e.g. Motorized banca, Tilapia pond 1'],
                ['name' => 'registration_no', 'label' => 'Registration no.', 'rules' => 'nullable|max:40', 'col' => 'col-md-3', 'maxlength' => 40, 'help' => 'Boat or pond registration. Must be unique.'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'rules' => 'required|in:active,inactive', 'col' => 'col-md-3'],
                ['section' => 'Type-specific details', 'name' => 'boat_type', 'label' => 'Boat type', 'rules' => 'nullable|max:60', 'col' => 'col-md-3', 'help' => 'Boats only.'],
                ['name' => 'gear_type', 'label' => 'Gear type', 'rules' => 'nullable|max:60', 'col' => 'col-md-3', 'help' => 'Gear only.'],
                ['name' => 'area_sqm', 'label' => 'Area (sqm)', 'type' => 'number', 'min' => '0.01', 'step' => '0.01', 'rules' => 'nullable|numeric', 'positive' => true, 'col' => 'col-md-3', 'help' => 'Required for fishponds and aquaculture units.'],
                ['name' => 'commodity_id', 'label' => 'Species', 'type' => 'select', 'options' => $species, 'rules' => 'nullable|integer|exists:commodities,id', 'col' => 'col-md-3', 'placeholder' => 'None'],
                ['section' => 'Map location (optional)', 'name' => 'latitude', 'label' => 'Latitude', 'type' => 'number', 'step' => 'any', 'rules' => 'nullable|numeric', 'col' => 'col-md-3'],
                ['name' => 'longitude', 'label' => 'Longitude', 'type' => 'number', 'step' => 'any', 'rules' => 'nullable|numeric', 'col' => 'col-md-3'],
                ['name' => 'boundary_geojson', 'label' => 'Boundary (GeoJSON)', 'type' => 'textarea', 'rules' => 'nullable', 'col' => 'col-md-6'],
            ],
        ];
    }

    protected function validateExtra(array $clean, array &$errors, ?array $existing): void
    {
        if (in_array($clean['asset_type'], ['fishpond', 'aquaculture_unit'], true) && $clean['area_sqm'] === null) {
            $errors['area_sqm'][] = 'Area is required for fishponds and aquaculture units.';
        }
        if ($clean['registration_no'] !== null) {
            $dup = Model::rawOne('SELECT id FROM fishery_assets WHERE registration_no = ? AND id <> ? LIMIT 1', [$clean['registration_no'], $existing['id'] ?? 0]);
            if ($dup) {
                $errors['registration_no'][] = 'This registration number is already used.';
            }
        }
        $this->validateGeo($clean, $errors);
    }
}
