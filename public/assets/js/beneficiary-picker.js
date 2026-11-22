/**
 * Beneficiary picker: type to search the registry (JSON), click Add to POST the person to the program.
 *   <div data-beneficiary-picker data-search-url="..." data-add-url="...">
 *     <input data-picker="input"> <div data-picker="results"></div>
 *   </div>
 */
(function () {
    'use strict';

    function init(root) {
        const input = root.querySelector('[data-picker="input"]');
        const results = root.querySelector('[data-picker="results"]');
        let timer = null;
        let seq = 0;

        function message(text) {
            results.replaceChildren();
            const p = document.createElement('div');
            p.className = 'text-muted small';
            p.textContent = text;
            results.append(p);
        }

        function addForm(person) {
            const form = document.createElement('form');
            form.method = 'post';
            form.action = root.dataset.addUrl;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_csrf';
            csrf.value = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
            const pid = document.createElement('input');
            pid.type = 'hidden';
            pid.name = 'profile_id';
            pid.value = person.id;
            const btn = document.createElement('button');
            btn.type = 'submit';
            btn.className = 'btn btn-primary btn-sm';
            btn.textContent = 'Add';
            form.append(csrf, pid, btn);
            return form;
        }

        function render(list) {
            results.replaceChildren();
            if (!list.length) { message('No matching active profiles that are not already on this program.'); return; }
            list.forEach((p) => {
                const item = document.createElement('div');
                item.className = 'match-item';
                const info = document.createElement('div');
                const name = document.createElement('div');
                name.className = 'match-name';
                name.textContent = p.label;
                const meta = document.createElement('div');
                meta.className = 'match-meta';
                meta.textContent = p.meta || '';
                info.append(name, meta);
                item.append(info, addForm(p));
                results.append(item);
            });
        }

        async function search() {
            const q = input.value.trim();
            if (q.length < 2) { results.replaceChildren(); return; }
            const mine = ++seq;
            try {
                const res = await fetch(root.dataset.searchUrl + '&q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) { throw new Error('search failed'); }
                const list = await res.json();
                if (mine === seq) { render(list); }          // ignore out-of-date responses
            } catch (e) {
                if (mine === seq) { message('Could not search right now. Please try again.'); }
            }
        }

        input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(search, 250); });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-beneficiary-picker]').forEach(init);
    });
})();
