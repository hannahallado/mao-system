/**
 * Distribution form helpers: the Beneficiary dropdown follows the chosen Program,
 * and the Unit field follows the chosen Assistance type (until the user manually changes it).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const program = document.getElementById('f_program_id');
        const ben = document.getElementById('f_program_beneficiary_id');
        const type = document.getElementById('f_assistance_type_id');
        const unit = document.getElementById('f_unit');
        if (!program || !ben) { return; }

        async function loadBeneficiaries() {
            ben.replaceChildren(new Option(program.value ? 'Loading...' : 'Select a program first', ''));
            if (!program.value) { return; }
            try {
                const res = await fetch(ben.dataset.optionsUrl.replace('__ID__', encodeURIComponent(program.value)), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const list = await res.json();
                ben.replaceChildren(new Option(list.length ? 'Select beneficiary...' : 'No beneficiaries on this program yet', ''));
                list.forEach((o) => ben.add(new Option(o.label + (o.meta ? ' - ' + o.meta : ''), o.id)));
                if (ben.dataset.selected) { ben.value = ben.dataset.selected; ben.dataset.selected = ''; }
            } catch (e) {
                ben.replaceChildren(new Option('Could not load beneficiaries', ''));
            }
        }
        program.addEventListener('change', () => { ben.dataset.selected = ''; loadBeneficiaries(); });

        if (type && unit) {
            let auto = !unit.value;
            type.addEventListener('change', () => {
                const u = (window.__assistanceTypes || {})[type.value];
                if (u !== undefined && (auto || !unit.value)) { 
                    unit.value = u || ''; 
                    auto = true; 
                }
            });
            unit.addEventListener('change', () => { auto = false; });
        }
    });
})();
