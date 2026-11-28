<?php
/** Shared by create.php and edit.php. Var: $program (stored values or defaults) */
$v = fn (string $k) => $program[$k] ?? '';
$allocations = $program['allocations'] ?? [];
$assistanceTypes = $assistanceTypes ?? [];
$years = [];
for ($year = (int) date('Y') + 1; $year >= (int) date('Y') - 10; $year--) {
    $years[(string) $year] = (string) $year;
}
?>
<?= Ui::section('Program information',
    Ui::input('name', 'Program name', ['value' => $v('name'), 'required' => true, 'col' => 'col-md-8', 'maxlength' => 150, 'placeholder' => 'e.g. Rice Seed Distribution 2026'])
  . Ui::select('source_agency', 'Source agency', Program::AGENCIES, ['value' => $v('source_agency'), 'required' => true, 'col' => 'col-md-4', 'placeholder' => 'Select agency'])
  . Ui::textarea('description', 'Description', ['value' => $v('description'), 'col' => 'col-md-6', 'rows' => 3])
  . Ui::textarea('objectives', 'Objectives', ['value' => $v('objectives'), 'col' => 'col-md-6', 'rows' => 3])
) ?>

<?= Ui::section('Schedule and targets',
    Ui::select('year', 'Year', $years, ['value' => $v('year') ?: date('Y'), 'required' => true, 'col' => 'col-md-2'])
  . Ui::input('start_date', 'Start date', ['value' => $v('start_date'), 'type' => 'date', 'col' => 'col-md-3'])
  . Ui::input('end_date', 'End date', ['value' => $v('end_date'), 'type' => 'date', 'col' => 'col-md-3'])
  . Ui::select('status', 'Status', Program::STATUSES, ['value' => $v('status') ?: 'planned', 'required' => true, 'col' => 'col-md-4'])
  . Ui::input('allocation_amount', 'Total Budget (PHP)', ['value' => $v('allocation_amount'), 'col' => 'col-md-4', 'inputmode' => 'decimal', 'placeholder' => '0.00', 'help' => 'Total program budget or allocation amount'])
  . Ui::input('target_beneficiaries', 'Target beneficiaries', ['value' => $v('target_beneficiaries'), 'col' => 'col-md-4', 'inputmode' => 'numeric', 'help' => 'Optional. Used for the progress bar.'])
  . Ui::input('program_no_display', 'Program ID', ['value' => $v('program_no') ?: 'Assigned automatically on save', 'col' => 'col-md-4', 'readonly' => true])
) ?>

<?php
// Build assistance selector with dropdown
$assistanceInputs = '<div class="col-md-12">'
    . '<p class="text-muted small mb-4">Add assistance items to allocate. For each item, specify the quantity and the value per unit (PHP).</p>'
    . '</div>'
    . '<div class="col-md-4">'
    . '<label class="form-label">Assistance Item</label>'
    . '<select class="form-select" id="assistance_type_select">'
    . '<option value="">Select an item...</option>';

if (!empty($assistanceTypes)) {
    foreach ($assistanceTypes as $typeId => $typeData) {
        $assistanceInputs .= '<option value="' . e($typeId) . '" data-unit="' . e($typeData['unit']) . '">'
            . e($typeData['name']) . ' (' . e($typeData['unit']) . ')'
            . '</option>';
    }
}

$assistanceInputs .= '</select>'
    . '</div>'
    . '<div class="col-md-3">'
    . '<label class="form-label">Quantity</label>'
    . '<input type="number" class="form-control" id="assistance_qty" step="0.01" inputmode="decimal" placeholder="e.g. 100">'
    . '</div>'
    . '<div class="col-md-3">'
    . '<label class="form-label">Value per Unit (PHP)</label>'
    . '<input type="number" class="form-control" id="assistance_unit_value" step="0.01" inputmode="decimal" placeholder="e.g. 100">'
    . '</div>'
    . '<div class="col-md-2 d-flex align-items-end">'
    . '<button type="button" class="btn btn-primary w-100" id="add_assistance_btn">Add</button>'
    . '</div>'
    . '<div class="col-md-12 mt-4">'
    . '<div id="assistance_items_container"></div>'
    . '</div>';

if (empty($assistanceTypes)) {
    $assistanceInputs = '<div class="col-md-12"><p class="text-muted">No assistance types available. Create assistance types first.</p></div>';
}
?>

<?= Ui::section('Assistance items', $assistanceInputs) ?>

<script>
// Custom alert that uses the confirmation modal
function showAlert(message) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('tabindex', '-1');
    modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Message</h5>
                    <button type="button" class="x-close-btn" data-bs-dismiss="modal" aria-label="Close"><span class="bar"></span><span class="bar"></span></button>
                </div>
                <div class="modal-body">${escapeHtml(message)}</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    
    modal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modal);
    });
    
    bsModal.show();
}

// Track added assistance items
let addedItems = [];

document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('assistance_items_container');
    const typeSelect = document.getElementById('assistance_type_select');
    const qtyInput = document.getElementById('assistance_qty');
    const valueInput = document.getElementById('assistance_unit_value');
    const addBtn = document.getElementById('add_assistance_btn');
    
    // Load existing allocations if editing
    const existingAllocations = <?= !empty($allocations) ? json_encode($allocations) : '{}' ?>;
    const assistanceTypesData = <?= json_encode($assistanceTypes) ?>;
    
    // Populate addedItems from existing allocations
    Object.keys(existingAllocations).forEach(unit => {
        const alloc = existingAllocations[unit];
        // Find first type with this unit
        const type = Object.values(assistanceTypesData).find(t => t.unit === unit);
        if (type) {
            addedItems.push({
                typeId: type.name || null,
                typeName: type.name,
                unit: unit,
                quantity: parseFloat(alloc.amount) || 0,
                unitValue: alloc.unit_value !== null ? parseFloat(alloc.unit_value) : null
            });
        }
    });
    
    function renderTable() {
        container.innerHTML = '';
        
        if (addedItems.length === 0) {
            container.innerHTML = '<p class="text-muted small">No items added yet</p>';
            return;
        }
        
        let html = '<div class="table-responsive">'
            + '<table class="table table-sm table-bordered">'
            + '<thead><tr><th>Assistance Item</th><th class="text-end">Quantity</th><th class="text-end">Value per Unit</th><th class="text-end">Total Value</th><th></th></tr></thead>'
            + '<tbody>';
        
        addedItems.forEach((item, idx) => {
            const totalValue = (item.quantity || 0) * (item.unitValue || 0);
            html += `
                <tr>
                    <td><strong>${escapeHtml(item.typeName)}</strong> <span class="text-muted">(${escapeHtml(item.unit)})</span></td>
                    <td class="text-end">${item.quantity}</td>
                    <td class="text-end">${item.unitValue !== null ? item.unitValue.toFixed(2) : '—'}</td>
                    <td class="text-end"><strong>${totalValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmRemoveItem(${idx})">Remove</button>
                        <input type="hidden" name="assistance_allocations[${idx}][type_name]" value="${escapeHtml(item.typeName)}">
                        <input type="hidden" name="assistance_allocations[${idx}][unit]" value="${escapeHtml(item.unit)}">
                        <input type="hidden" name="assistance_allocations[${idx}][quantity]" value="${item.quantity}">
                        <input type="hidden" name="assistance_allocations[${idx}][unit_value]" value="${item.unitValue || ''}">
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        container.innerHTML = html;
    }
    
    window.confirmRemoveItem = function(idx) {
        confirm('Remove this assistance item from the allocation?', function() {
            addedItems.splice(idx, 1);
            renderTable();
        });
    };
    
    addBtn.addEventListener('click', function() {
        const typeId = typeSelect.value;
        const typeName = typeSelect.options[typeSelect.selectedIndex].text;
        const unit = typeSelect.options[typeSelect.selectedIndex].getAttribute('data-unit');
        const qty = parseFloat(qtyInput.value);
        const value = valueInput.value !== '' ? parseFloat(valueInput.value) : null;
        
        if (!typeId) {
            showAlert('Please select an assistance item');
            return;
        }
        
        if (!qty || qty <= 0) {
            showAlert('Please enter a valid quantity');
            return;
        }
        
        // Check if already added
        if (addedItems.find(item => item.unit === unit && item.typeName === typeName)) {
            showAlert('This item is already added');
            return;
        }
        
        addedItems.push({
            typeId: typeId,
            typeName: typeName,
            unit: unit,
            quantity: qty,
            unitValue: value
        });
        
        renderTable();
        typeSelect.value = '';
        qtyInput.value = '';
        valueInput.value = '';
        typeSelect.focus();
    });
    
    // Initial render
    renderTable();
});
</script>
