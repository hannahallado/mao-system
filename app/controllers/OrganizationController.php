<?php
/** Process 2.0 - Organizations and their members/officers (functional). */
class OrganizationController extends ResourceController
{
    protected function build(): array
    {
        $brgy = Lookup::barangays();
        return [
            'model' => 'Organization', 'table' => 'organizations', 'path' => '/organizations', 'title' => 'Organizations', 'singular' => 'Organization',
            'sector' => null, 'orderBy' => 'r.name',
            'select' => 'r.*, b.name AS barangay_name, (SELECT COUNT(*) FROM organization_members m WHERE m.organization_id = r.id) AS member_count',
            'from' => 'FROM organizations r LEFT JOIN barangays b ON b.id = r.barangay_id',
            'search' => ['r.name', 'r.registration_no'],
            'filters' => [
                'org_type' => ['label' => 'All types', 'column' => 'r.org_type', 'options' => Organization::TYPES],
                'barangay_id' => ['label' => 'All barangays', 'column' => 'r.barangay_id', 'options' => $brgy],
                'status' => ['label' => 'All statuses', 'column' => 'r.status', 'options' => Organization::STATUSES],
            ],
            'stats' => [
                'Organizations' => ['COUNT(*)', 0], 'Active' => ['SUM(r.status = "active")', 0],
                'Total members' => ['(SELECT COUNT(*) FROM organization_members)', 0],
            ],
            'columns' => [
                ['label' => 'Organization', 'link' => true, 'key' => 'name'],
                ['label' => 'Type', 'render' => fn ($r) => e(Organization::TYPES[$r['org_type']] ?? $r['org_type'])],
                ['label' => 'Barangay', 'key' => 'barangay_name'],
                ['label' => 'Reg. no.', 'key' => 'registration_no'],
                ['label' => 'Members', 'class' => 'num', 'key' => 'member_count'],
                ['label' => 'Status', 'render' => fn ($r) => Ui::statusBadge($r['status'], Organization::STATUSES[$r['status']] ?? null)],
            ],
            'defaults' => ['status' => 'active'],
            'label' => fn ($r) => $r['name'],
            'detail' => fn ($r) => [
                'Type' => Organization::TYPES[$r['org_type']] ?? $r['org_type'], 'Barangay' => $r['barangay_name'],
                'Registration no.' => $r['registration_no'], 'Registering agency' => $r['registering_agency'],
                'Date registered' => format_date($r['date_registered']), 'Members' => $r['member_count'],
                'Status' => Organization::STATUSES[$r['status']] ?? $r['status'],
            ],
            'fields' => [
                ['section' => 'Organization', 'name' => 'name', 'label' => 'Organization name', 'rules' => 'required|max:150', 'col' => 'col-md-8', 'maxlength' => 150],
                ['name' => 'org_type', 'label' => 'Type', 'type' => 'select', 'options' => Organization::TYPES, 'rules' => 'required|' . self::in(Organization::TYPES), 'col' => 'col-md-4', 'placeholder' => 'Select type'],
                ['name' => 'barangay_id', 'label' => 'Barangay', 'type' => 'select', 'options' => $brgy, 'rules' => 'required|integer|exists:barangays,id', 'col' => 'col-md-4', 'placeholder' => 'Select barangay'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => Organization::STATUSES, 'rules' => 'required|' . self::in(Organization::STATUSES), 'col' => 'col-md-4'],
                ['section' => 'Registration', 'name' => 'registration_no', 'label' => 'Registration no.', 'rules' => 'nullable|max:40', 'col' => 'col-md-4', 'maxlength' => 40],
                ['name' => 'registering_agency', 'label' => 'Registering agency', 'rules' => 'nullable|max:80', 'col' => 'col-md-4', 'maxlength' => 80, 'placeholder' => 'e.g. SEC, CDA, DOLE'],
                ['name' => 'date_registered', 'label' => 'Date registered', 'type' => 'date', 'max' => date('Y-m-d'), 'rules' => 'nullable|date|not_future', 'col' => 'col-md-4'],
            ],
        ];
    }

    /** Members and officers panel + "add member" form on the detail page. */
    protected function showExtra(array $row): string
    {
        $id = (int) $row['id'];
        $members = OrganizationMember::forOrganization($id);
        $cols = [
            ['label' => 'Member', 'render' => fn ($m) => '<a class="fw-semibold" href="' . e(url('/farmers/' . $m['profile_id'])) . '">' . e(Profile::fullName($m)) . '</a>'],
            ['label' => 'Position', 'render' => fn ($m) => $m['position'] === 'Member' ? e($m['position']) : Ui::badge($m['position'], 'info')],
            ['label' => 'Barangay', 'key' => 'barangay_name'],
            ['label' => 'Joined', 'render' => fn ($m) => e(format_date($m['date_joined']))],
            ['label' => '', 'class' => 'table-actions', 'render' => fn ($m) => '<form method="post" class="d-inline" action="' . e(url('/organizations/' . $id . '/members/' . $m['id'] . '/remove'))
                . '" onsubmit="return confirm(\'Remove this member?\')">' . Csrf::field() . '<button class="btn btn-outline-secondary btn-sm" type="submit">Remove</button></form>'],
        ];

        $eligible = Model::raw(
            'SELECT p.id, p.last_name, p.first_name, p.middle_name, p.suffix FROM profiles p
             WHERE p.deleted_at IS NULL AND p.status = "active"
               AND p.id NOT IN (SELECT profile_id FROM organization_members WHERE organization_id = ?)
             ORDER BY p.last_name, p.first_name', [$id]);
        $opts = '<option value="">Select a registered profile</option>';
        foreach ($eligible as $p) {
            $opts .= '<option value="' . e($p['id']) . '">' . e(Profile::fullName($p)) . '</option>';
        }

        return '<h2 class="h6 mt-4 mb-2">Members and officers (' . count($members) . ')</h2>'
            . '<div class="card mb-3"><div class="card-body"><form method="post" action="' . e(url('/organizations/' . $id . '/members')) . '" class="row g-2 align-items-end">'
            . Csrf::field()
            . '<div class="col-md-5"><label class="form-label" for="m_profile">Add member</label><select class="form-select" id="m_profile" name="profile_id" required>' . $opts . '</select></div>'
            . '<div class="col-md-3"><label class="form-label" for="m_pos">Position</label><input class="form-control" id="m_pos" name="position" value="Member" maxlength="60"></div>'
            . '<div class="col-md-2"><label class="form-label" for="m_date">Date joined</label><input class="form-control" id="m_date" type="date" name="date_joined" max="' . e(date('Y-m-d')) . '"></div>'
            . '<div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Add</button></div>'
            . '</form></div></div>'
            . Ui::dataTable($cols, $members, ['empty' => Ui::emptyState('No members yet', 'Add registered farmers or fisherfolk above. Officers are members with a position.')]);
    }

    public function addMember(string $id): void
    {
        $org = $this->findRow($id);
        $back = '/organizations/' . $org['id'];
        $pid = $this->input('profile_id', '');
        $profile = ctype_digit((string) $pid) ? Profile::withBarangay((int) $pid) : null;
        if (!$profile || $profile['status'] !== 'active') {
            flash('danger', 'Only active registered profiles can be added as members.');
            $this->redirect($back);
        }
        if (OrganizationMember::where(['organization_id' => (int) $org['id'], 'profile_id' => (int) $profile['id']])) {
            flash('warning', Profile::fullName($profile) . ' is already a member.');
            $this->redirect($back);
        }
        $position = mb_substr((string) $this->input('position', 'Member'), 0, 60) ?: 'Member';
        $date = (string) $this->input('date_joined', '');
        $d = DateTime::createFromFormat('Y-m-d', $date);
        $date = ($d && $d->format('Y-m-d') === $date && $date <= date('Y-m-d')) ? $date : null;

        $row = ['organization_id' => (int) $org['id'], 'profile_id' => (int) $profile['id'], 'position' => $position, 'date_joined' => $date];
        $rid = OrganizationMember::insert($row);
        AuditService::log('create', 'organization_members', $rid, null, $row);
        flash('success', 'Added ' . Profile::fullName($profile) . ' to ' . $org['name'] . '.');
        $this->redirect($back);
    }

    public function removeMember(string $id, string $mid): void
    {
        $org = $this->findRow($id);
        $m = ctype_digit($mid) ? OrganizationMember::find((int) $mid) : null;
        if (!$m || (int) $m['organization_id'] !== (int) $org['id']) {
            abort(404, 'Member not found.');
        }
        Model::query('DELETE FROM organization_members WHERE id = ?', [(int) $m['id']]);
        AuditService::log('archive', 'organization_members', (int) $m['id'], $m, null, 'Member removed from organization');
        flash('success', 'Member removed.');
        $this->redirect('/organizations/' . $org['id']);
    }
}
