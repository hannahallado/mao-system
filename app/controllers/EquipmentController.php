<?php
/** Process 2.0 - Equipment Inventory (functional). Recipient: an organization OR a farmer, not both. */
class EquipmentController extends ResourceController
{
    protected function build(): array
    {
        $brgy = Lookup::barangays();
        $types = Lookup::equipmentTypes();
        return [
            'model' => 'Equipment', 'table' => 'equipment', 'path' => '/equipment', 'title' => 'Equipment Inventory', 'singular' => 'Equipment',
            'sector' => null, 'orderBy' => 't.name, r.id DESC',
            'select' => 'r.*, t.name AS type_name, b.name AS barangay_name, o.name AS org_name,
                         p.last_name, p.first_name, p.middle_name, p.suffix',
            'from' => 'FROM equipment r JOIN equipment_types t ON t.id = r.equipment_type_id
                       LEFT JOIN barangays b ON b.id = r.barangay_id LEFT JOIN organizations o ON o.id = r.organization_id
                       LEFT JOIN profiles p ON p.id = r.profile_id',
            'search' => ['t.name', 'o.name', 'p.last_name', 'p.first_name', 'r.provided_by_agency'],
            'filters' => [
                'equipment_type_id' => ['label' => 'All types', 'column' => 'r.equipment_type_id', 'options' => $types],
                'barangay_id' => ['label' => 'All barangays', 'column' => 'r.barangay_id', 'options' => $brgy],
                'condition_status' => ['label' => 'All conditions', 'column' => 'r.condition_status', 'options' => Equipment::CONDITIONS],
            ],
            'stats' => [
                'Records' => ['COUNT(*)', 0], 'Total units' => ['SUM(r.quantity)', 0],
                'Needs repair' => ['SUM(r.condition_status = "needs_repair")', 0], 'Unserviceable' => ['SUM(r.condition_status = "unserviceable")', 0],
            ],
            'columns' => [
                ['label' => 'Equipment', 'link' => true, 'key' => 'type_name'],
                ['label' => 'Qty', 'class' => 'num', 'key' => 'quantity'],
                ['label' => 'Recipient', 'render' => fn ($r) => self::recipient($r)],
                ['label' => 'Barangay', 'key' => 'barangay_name'],
                ['label' => 'Condition', 'render' => fn ($r) => Ui::badge(Equipment::CONDITIONS[$r['condition_status']], match ($r['condition_status']) { 'good' => 'success', 'fair' => 'info', 'needs_repair' => 'warning', default => 'danger' })],
                ['label' => 'Agency / year', 'render' => fn ($r) => e(trim(($r['provided_by_agency'] ?? '') . ' ' . ($r['year_granted'] ?? '')) ?: '—')],
            ],
            'defaults' => ['quantity' => 1, 'condition_status' => 'good', 'status' => 'in_use'],
            'label' => fn ($r) => $r['type_name'] . ' - ' . strip_tags(self::recipient($r)),
            'detail' => fn ($r) => [
                'Equipment' => $r['type_name'], 'Quantity' => $r['quantity'], 'Recipient' => fn () => self::recipient($r),
                'Barangay' => $r['barangay_name'], 'Condition' => Equipment::CONDITIONS[$r['condition_status']] ?? null,
                'Status' => Equipment::STATUSES[$r['status']] ?? null, 'Provided by' => $r['provided_by_agency'], 'Year granted' => $r['year_granted'],
            ],
            'fields' => [
                ['section' => 'Equipment', 'name' => 'equipment_type_id', 'label' => 'Type', 'type' => 'select', 'options' => $types, 'rules' => 'required|integer|exists:equipment_types,id', 'col' => 'col-md-4', 'placeholder' => 'Select type'],
                ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'min' => '1', 'step' => '1', 'rules' => 'required|integer', 'positive' => true, 'col' => 'col-md-2'],
                ['name' => 'condition_status', 'label' => 'Condition', 'type' => 'select', 'options' => Equipment::CONDITIONS, 'rules' => 'required|' . self::in(Equipment::CONDITIONS), 'col' => 'col-md-3'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => Equipment::STATUSES, 'rules' => 'required|' . self::in(Equipment::STATUSES), 'col' => 'col-md-3'],
                ['section' => 'Recipient and location (choose an organization OR a farmer)', 'name' => 'organization_id', 'label' => 'Recipient organization', 'type' => 'select', 'options' => Lookup::organizations(), 'rules' => 'nullable|integer|exists:organizations,id', 'col' => 'col-md-4', 'placeholder' => 'None'],
                ['name' => 'profile_id', 'label' => 'Recipient farmer', 'type' => 'select', 'options' => Lookup::profiles(), 'rules' => 'nullable|integer|exists:profiles,id', 'col' => 'col-md-4', 'placeholder' => 'None'],
                ['name' => 'barangay_id', 'label' => 'Barangay / location', 'type' => 'select', 'options' => $brgy, 'rules' => 'required|integer|exists:barangays,id', 'col' => 'col-md-4', 'placeholder' => 'Select barangay'],
                ['section' => 'Source', 'name' => 'provided_by_agency', 'label' => 'Provided by agency', 'rules' => 'nullable|max:80', 'col' => 'col-md-6', 'maxlength' => 80, 'placeholder' => 'e.g. DA, LGU'],
                ['name' => 'year_granted', 'label' => 'Year granted', 'type' => 'number', 'min' => '1950', 'max' => date('Y'), 'step' => '1', 'rules' => 'nullable|integer', 'col' => 'col-md-6'],
            ],
        ];
    }

    private static function recipient(array $r): string
    {
        if (!empty($r['org_name'])) {
            return e($r['org_name']) . ' <span class="text-muted small">(org)</span>';
        }
        if (!empty($r['profile_id'])) {
            return self::profileLink($r);
        }
        return '<span class="text-muted">&mdash;</span>';
    }

    protected function validateExtra(array $clean, array &$errors, ?array $existing): void
    {
        $org = $clean['organization_id'] ?? null;
        $per = $clean['profile_id'] ?? null;
        if ($org !== null && $per !== null) {
            $errors['profile_id'][] = 'Choose an organization or a farmer, not both.';
        } elseif ($org === null && $per === null) {
            $errors['organization_id'][] = 'Choose the recipient organization or farmer.';
        }
        if ($clean['year_granted'] !== null && ((int) $clean['year_granted'] < 1950 || (int) $clean['year_granted'] > (int) date('Y'))) {
            $errors['year_granted'][] = 'Year granted must be between 1950 and ' . date('Y') . '.';
        }
    }
}
