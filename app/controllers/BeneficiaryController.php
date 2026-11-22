<?php
/** Process 5.0 - beneficiary master list: pick people from the registry (D1), never create new ones here. */
class BeneficiaryController extends Controller
{
    /** The master list lives on the program page (Beneficiaries tab): show that page with the tab open. */
    public function index(string $id): void
    {
        $_GET['tab'] = 'beneficiaries';
        (new ProgramController())->show($id);
    }

    /** JSON for the picker: active profiles not yet on the program. GET /beneficiaries/search?program_id=&q= */
    public function search(): void
    {
        $programId = (int) $this->input('program_id', 0);
        $q = trim((string) $this->input('q', ''));
        if (!$programId || mb_strlen($q) < 2) {
            $this->json([]);
        }
        $out = [];
        foreach (ProgramBeneficiary::searchEligible($programId, $q) as $r) {
            $meta = array_filter([$r['barangay_name'], ProgramBeneficiary::registryLabel($r)]);
            $out[] = ['id' => (int) $r['id'], 'label' => Profile::fullName($r), 'meta' => implode(' · ', $meta)];
        }
        $this->json($out);
    }

    /** JSON for the distribution form: people on one program's list. GET /programs/{id}/beneficiaries/options */
    public function options(string $id): void
    {
        $out = [];
        foreach (ProgramBeneficiary::forProgram((int) $id) as $r) {
            if ($r['profile_status'] !== 'active') {
                continue;
            }
            $out[] = ['id' => (int) $r['id'], 'label' => Profile::fullName($r), 'meta' => $r['barangay_name'] ?? ''];
        }
        $this->json($out);
    }

    /** POST /programs/{id}/beneficiaries  (profile_id) */
    public function add(string $id): void
    {
        $program = $this->program($id);
        $back = '/programs/' . $program['id'] . '?tab=beneficiaries';
        $profile = ctype_digit((string) $this->input('profile_id')) ? Profile::withBarangay((int) $this->input('profile_id')) : null;
        if (!$profile || $profile['status'] !== 'active') {
            flash('danger', 'Only active registered profiles can be added as beneficiaries.');
            $this->redirect($back);
        }
        if ($program['status'] === 'completed') {
            flash('warning', 'This program is completed. Change its status before adding beneficiaries.');
            $this->redirect($back);
        }

        $existing = ProgramBeneficiary::findInProgram((int) $program['id'], (int) $profile['id']);
        if ($existing && $existing['status'] !== 'removed') {
            flash('warning', Profile::fullName($profile) . ' is already on this program.');
            $this->redirect($back);
        }
        if ($existing) {                       // re-add someone who was removed
            ProgramBeneficiary::update((int) $existing['id'], ['status' => 'identified']);
            $rowId = (int) $existing['id'];
        } else {
            $rowId = ProgramBeneficiary::insert(['program_id' => $program['id'], 'profile_id' => $profile['id'], 'status' => 'identified']);
        }
        AuditService::log('create', 'program_beneficiaries', $rowId, null, ['program_id' => (int) $program['id'], 'profile_id' => (int) $profile['id']]);
        flash('success', 'Added ' . Profile::fullName($profile) . ' to the beneficiary list.');
        $this->redirect($back);
    }

    /** POST /programs/{id}/beneficiaries/{bid}/status  (status) */
    public function status(string $id, string $bid): void
    {
        [$program, $row] = $this->rowOr404($id, $bid);
        $back = '/programs/' . $program['id'] . '?tab=beneficiaries';
        $status = (string) $this->input('status', '');
        if (!isset(ProgramBeneficiary::MANUAL_STATUSES[$status])) {
            flash('danger', 'Invalid status.');
            $this->redirect($back);
        }
        if (self::assistanceCount((int) $row['id']) > 0) {
            flash('warning', 'This beneficiary already received assistance, so the status stays "Served".');
            $this->redirect($back);
        }
        ProgramBeneficiary::update((int) $row['id'], ['status' => $status]);
        AuditService::log('update', 'program_beneficiaries', (int) $row['id'], ['status' => $row['status']], ['status' => $status]);
        flash('success', 'Beneficiary status updated.');
        $this->redirect($back);
    }

    /** POST /programs/{id}/beneficiaries/{bid}/remove */
    public function remove(string $id, string $bid): void
    {
        [$program, $row] = $this->rowOr404($id, $bid);
        $back = '/programs/' . $program['id'] . '?tab=beneficiaries';
        if (self::assistanceCount((int) $row['id']) > 0) {
            flash('warning', 'Cannot remove a beneficiary who already received assistance.');
            $this->redirect($back);
        }
        ProgramBeneficiary::update((int) $row['id'], ['status' => 'removed']);
        AuditService::log('archive', 'program_beneficiaries', (int) $row['id'], ['status' => $row['status']], ['status' => 'removed']);
        flash('success', 'Removed from the beneficiary list.');
        $this->redirect($back);
    }

    // ------------------------------------------------------------------ helpers

    private function program(string $id): array
    {
        $program = ctype_digit($id) ? Program::find((int) $id) : null;
        if (!$program) {
            abort(404, 'Program not found.');
        }
        return $program;
    }

    private function rowOr404(string $id, string $bid): array
    {
        $program = $this->program($id);
        $row = ctype_digit($bid) ? ProgramBeneficiary::find((int) $bid) : null;
        if (!$row || (int) $row['program_id'] !== (int) $program['id'] || $row['status'] === 'removed') {
            abort(404, 'Beneficiary not found on this program.');
        }
        return [$program, $row];
    }

    private static function assistanceCount(int $beneficiaryId): int
    {
        return (int) Model::query('SELECT COUNT(*) FROM assistance_records WHERE program_beneficiary_id = ?', [$beneficiaryId])->fetchColumn();
    }
}
