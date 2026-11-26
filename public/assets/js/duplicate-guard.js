/**
 * DuplicateGuard - checks a form for duplicates before it is saved and shows the warning modal.
 *
 * Usage (any screen):
 *   <form data-duplicate-check="/some/check-url"> ... </form>
 *   include partials/duplicate-modal.php and this script.
 *
 * The check URL receives the form data (POST + CSRF) and answers JSON:
 *   { level: 'clear' }                                    -> form is submitted
 *   { level: 'warning'|'blocked', title, message,
 *     matches: [{label, meta, reason, strength, url}],
 *     requires_reason, confirm_label, reason_label, reason_field, reason_min, match_action }
 * 'warning' lets the user continue by entering a reason (sent in the field named reason_field);
 * 'blocked' only lets them open the existing record. The server re-checks on save, so this is a
 * convenience layer, never the only protection.
 */
(function () {
    'use strict';

    const STRENGTH = {
        exact_id: ['danger', 'Number already used'],
        strong:   ['warning', 'Strong match'],
        possible: ['muted', 'Possible match'],
    };

    class DuplicateGuard {
        constructor(form, modalEl) {
            this.form = form;
            this.el = modalEl;
            // One warning modal can serve several forms (Register + Edit on the
            // Registry list), so share one Bootstrap instance and only let the
            // form that opened the warning react to it
            this.modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            this.payload = null;
            this.confirmed = false;
            this.q = (name) => modalEl.querySelector('[data-dup="' + name + '"]');

            // Form inside a modal (e.g. Register Profile): Bootstrap can't stack
            // modals, so the form's modal steps aside while the warning shows
            this.hostEl = form.closest('.modal');
            this.host = this.hostEl ? bootstrap.Modal.getOrCreateInstance(this.hostEl) : null;
            form.addEventListener('submit', (e) => this.onSubmit(e));
            this.q('confirm').addEventListener('click', () => {
                if (DuplicateGuard.active === this) { this.onConfirm(); }
            });
            modalEl.addEventListener('hidden.bs.modal', () => {
                if (DuplicateGuard.active !== this) { return; }
                DuplicateGuard.active = null;
                this.setBusy(false);
                if (this.host && !this.confirmed) { this.host.show(); }   // "Back to form"
            });
        }

        setBusy(busy) {
            this.form.querySelectorAll('[type="submit"]').forEach((b) => { b.disabled = busy; });
        }

        async onSubmit(e) {
            if (this.confirmed) { return; }              // already resolved: let the browser submit
            e.preventDefault();
            this.setBusy(true);
            let payload;
            try {
                const res = await fetch(this.form.dataset.duplicateCheck, {
                    method: 'POST',
                    headers: csrfHeaders(),
                    body: new FormData(this.form),
                });
                if (!res.ok) { throw new Error('check failed'); }
                payload = await res.json();
            } catch (err) {
                this.submitNow();                          // server will validate and re-check anyway
                return;
            }
            if (payload.level === 'clear') { this.submitNow(); return; }
            this.show(payload);
        }

        submitNow() {
            this.confirmed = true;
            HTMLFormElement.prototype.submit.call(this.form);
        }

        show(payload) {
            DuplicateGuard.active = this;
            this.payload = payload;
            this.q('title').textContent = payload.title || 'Possible Duplicate Found';
            this.q('message').textContent = payload.message || '';

            const list = this.q('matches');
            list.replaceChildren();
            (payload.matches || []).forEach((m) => {
                const [type, badgeText] = STRENGTH[m.strength] || STRENGTH.possible;
                const item = document.createElement('div');
                item.className = 'match-item';

                const info = document.createElement('div');
                const name = document.createElement('div');
                name.className = 'match-name';
                name.textContent = m.label;
                const meta = document.createElement('div');
                meta.className = 'match-meta';
                meta.textContent = m.meta || '';
                const why = document.createElement('span');
                why.className = 'badge badge-soft-' + type;
                why.textContent = m.reason || badgeText;
                info.append(name, meta, why);

                const link = document.createElement('a');
                link.className = 'btn btn-outline-secondary btn-sm';
                link.href = m.url;
                link.textContent = payload.level === 'blocked' ? 'View profile' : (payload.match_action || 'Open');

                item.append(info, link);
                list.append(item);
            });

            const needsReason = payload.requires_reason !== false && payload.level !== 'blocked';
            this.q('reasonBox').hidden = !needsReason;
            this.q('confirm').hidden = !needsReason;
            this.q('confirm').textContent = payload.confirm_label || 'Continue anyway';
            this.q('reasonLabel').textContent = payload.reason_label || 'Reason';
            const reason = this.q('reason');
            reason.value = '';
            reason.classList.remove('is-invalid');

            if (this.hostEl && this.hostEl.classList.contains('show')) {
                this.hostEl.addEventListener('hidden.bs.modal', () => this.modal.show(), { once: true });
                this.host.hide();
            } else {
                this.modal.show();
            }
        }

        onConfirm() {
            const reason = this.q('reason');
            const min = this.payload.reason_min || 5;
            if (reason.value.trim().length < min) {
                reason.classList.add('is-invalid');
                this.q('reasonError').textContent = 'Please enter a reason (at least ' + min + ' characters).';
                reason.focus();
                return;
            }
            const field = this.payload.reason_field || 'override_reason';
            let hidden = this.form.querySelector('input[type="hidden"][name="' + field + '"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = field;
                this.form.append(hidden);
            }
            hidden.value = reason.value.trim();
            this.modal.hide();
            this.submitNow();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modalEl = document.getElementById('duplicateModal');
        if (!modalEl) { return; }
        document.querySelectorAll('form[data-duplicate-check]').forEach((form) => {
            const guard = new DuplicateGuard(form, modalEl);
            // Server-side fallback. With several forms on a page,
            // window.__duplicateFor names the one it belongs to (data-duplicate-key).
            const forThisForm = !window.__duplicateFor || form.dataset.duplicateKey === window.__duplicateFor;
            if (window.__duplicatePayload && forThisForm) { guard.show(window.__duplicatePayload); }
        });
    });

    window.DuplicateGuard = DuplicateGuard;
})();
