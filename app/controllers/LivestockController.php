<?php
/** Process 2.0 - Livestock and Poultry (functional). */
class LivestockController extends ResourceController
{
    protected function build(): array
    {
        $brgy = Lookup::barangays();
        $species = Lookup::commodities(['livestock', 'poultry']);
        return [
            'model' => 'LivestockRecord', 'table' => 'livestock_poultry_records', 'path' => '/livestock', 'title' => 'Livestock and Poultry', 'singular' => 'Livestock/poultry record',
            'sector' => 'livestock_poultry', 'orderBy' => 'r.as_of_date DESC, r.id DESC',
            'select' => 'r.*, p.last_name, p.first_name, p.middle_name, p.suffix, b.name AS barangay_name, c.name AS species_name, c.category AS species_category',
            'from' => 'FROM livestock_poultry_records r JOIN profiles p ON p.id = r.profile_id
                       JOIN commodities c ON c.id = r.commodity_id LEFT JOIN barangays b ON b.id = r.barangay_id',
            'search' => ['p.last_name', 'p.first_name', 'c.name'],
            'filters' => [
                'commodity_id' => ['label' => 'All species', 'column' => 'r.commodity_id', 'options' => $species],
                'barangay_id' => ['label' => 'All barangays', 'column' => 'r.barangay_id', 'options' => $brgy],
            ],
            'stats' => [
                'Records' => ['COUNT(*)', 0], 'Raisers' => ['COUNT(DISTINCT r.profile_id)', 0],
                'Livestock heads' => ['SUM(CASE WHEN c.category = "livestock" THEN r.animal_count ELSE 0 END)', 0],
                'Poultry heads' => ['SUM(CASE WHEN c.category = "poultry" THEN r.animal_count ELSE 0 END)', 0],
            ],
            'columns' => [
                ['label' => 'Raiser', 'render' => fn ($r) => '<a class="fw-semibold" href="' . e(url('/livestock/' . $r['id'])) . '">' . e(Profile::fullName($r)) . '</a>'],
                ['label' => 'Species', 'key' => 'species_name'],
                ['label' => 'Barangay', 'key' => 'barangay_name'],
                ['label' => 'Count', 'class' => 'num', 'render' => fn ($r) => number_format((int) $r['animal_count'])],
                ['label' => 'Purpose', 'render' => fn ($r) => e(LivestockRecord::PURPOSES[$r['purpose']] ?? '—')],
                ['label' => 'As of', 'render' => fn ($r) => e(format_date($r['as_of_date']))],
            ],
            'defaults' => ['as_of_date' => date('Y-m-d')],
            'label' => fn ($r) => Profile::fullName($r) . ' - ' . $r['species_name'],
            'detail' => fn ($r) => [
                'Raiser' => fn () => self::profileLink($r), 'Species' => $r['species_name'], 'Barangay' => $r['barangay_name'],
                'Animal count' => number_format((int) $r['animal_count']), 'Purpose' => LivestockRecord::PURPOSES[$r['purpose']] ?? null,
                'As of' => format_date($r['as_of_date']),
            ],
            'fields' => [
                ['section' => 'Holding', 'name' => 'profile_id', 'label' => 'Raiser', 'type' => 'select', 'options' => Lookup::profiles(), 'rules' => 'required|integer|exists:profiles,id', 'col' => 'col-md-6', 'placeholder' => 'Select registered profile'],
                ['name' => 'commodity_id', 'label' => 'Species', 'type' => 'select', 'options' => $species, 'rules' => 'required|integer|exists:commodities,id', 'col' => 'col-md-3', 'placeholder' => 'Select species'],
                ['name' => 'barangay_id', 'label' => 'Barangay', 'type' => 'select', 'options' => $brgy, 'rules' => 'required|integer|exists:barangays,id', 'col' => 'col-md-3', 'placeholder' => 'Select barangay'],
                ['name' => 'animal_count', 'label' => 'Number of animals', 'type' => 'number', 'min' => '1', 'step' => '1', 'rules' => 'required|integer', 'positive' => true, 'col' => 'col-md-4'],
                ['name' => 'purpose', 'label' => 'Purpose', 'type' => 'select', 'options' => LivestockRecord::PURPOSES, 'rules' => 'nullable|' . self::in(LivestockRecord::PURPOSES), 'col' => 'col-md-4', 'placeholder' => 'Select purpose'],
                ['name' => 'as_of_date', 'label' => 'Count as of', 'type' => 'date', 'max' => date('Y-m-d'), 'rules' => 'required|date|not_future', 'col' => 'col-md-4'],
            ],
        ];
    }
}
