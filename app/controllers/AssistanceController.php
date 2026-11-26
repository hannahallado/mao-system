<?php
/** Process 5.0 - assistance distribution, duplicate-assistance warning and history (functional). */
class AssistanceController extends Controller
{
    private const LABELS = [
        'program_id' => 'Program', 'program_beneficiary_id' => 'Beneficiary', 'assistance_type_id' => 'Assistance type',
        'quantity' => 'Quantity', 'date_distributed' => 'Date distributed', 'unit_value' => 'Unit value',
    ];
    private const FIELDS = [
        'program_id', 'program_beneficiary_id', 'assistance_type_id', 'quantity', 'unit',
        'unit_value', 'date_distributed', 'ack_receipt_no', 'released_by',
    ];

    // ------------------------------------------------------------------ distribution list

    public function index(): void
    {
        $filters = [
            'q'           => trim((string) $this->input('q', '')),
            'program_id'  => (string) $this->input('program_id', ''),
            'barangay_id' => (string) $this->input('barangay_id', ''),
            'flagged'     => (string) $this->input('flagged', ''),
        ];
        $this->view('assistance/index', [
            'title'       => 'Assistance Distribution',
            'breadcrumbs' => [['Home', '/home'], ['Programs', '/programs'], ['Distribution', null]],
            'filters'     => $filters,
            'result'      => AssistanceRecord::search($filters, (int) $this->input('page', 1)),
            'programs'    => Program::options(),
            'barangays'   => Lookup::barangays(),
        ]);
    }

    // ------------------------------------------------------------------ record a distribution

    public function create(): void
    {
        $programId = (string) $this->input('program_id', '');
        $this->renderForm($programId, (string) $this->input('beneficiary', ''));
    }

    public function store(): void
    {
        $data = $this->formData();
        $back = '/assistance/create';
        $errors = [];

        $v = Validator::make($data, [
            'program_id'             => 'required|integer|exists:programs,id',
            'program_beneficiary_id' => 'required|integer|exists:program_beneficiaries,id',
            'assistance_type_id'     => 'required|integer|exists:assistance_types,id',
            'quantity'               => 'required|numeric',
            'unit'                   => 'nullable|max:20',
            'unit_value'             => 'nullable|numeric',
            'date_distributed'       => 'required|date|not_future',
            'ack_receipt_no'         => 'nullable|max:40',
            'released_by'            => 'nullable|max:120',
        ], self::LABELS);
        $errors = $v->errors();
        $clean = $v->validated();

        if (!isset($errors['quantity']) && $clean['quantity'] !== null && (float) $clean['quantity'] <= 0) {
            $errors['quantity'][] = 'Quantity must be greater than zero.';
        }
        if (!isset($errors['unit_value']) && $clean['unit_value'] !== null && (float) $clean['unit_value'] < 0) {
            $errors['unit_value'][] = 'Unit value cannot be negative.';
        }

        // The beneficiary must be on the chosen program's list, and active.
        $row = null;
        if (!isset($errors['program_beneficiary_id']) && !isset($errors['program_id'])) {
            $row = ProgramBeneficiary::detail((int) $clean['program_beneficiary_id']);
            if (!$row || (int) $row['program_id'] !== (int) $clean['program_id'] || $row['status'] === 'removed') {
                $errors['program_beneficiary_id'][] = 'This person is not on the selected program\'s beneficiary list.';
            } elseif ($row['profile_status'] !== 'active') {
                $errors['program_beneficiary_id'][] = 'This profile is not active, so assistance cannot be recorded.';
            } elseif ($row['program_status'] === 'completed') {
                $errors['program_id'][] = 'This program is completed. Change its status to record more distributions.';
            }
        }
        if ($errors) {
            flash_errors($errors);
            flash_old($data);
            $this->redirect($back);
        }

        // Server-side duplicate guard (the JS modal normally resolves this before we get here)
        $reason = trim((string) $this->input('override_reason', ''));
        $result = DuplicateAssistanceService::check((int) $row['profile_id'], (int) $clean['assistance_type_id'], (int) $clean['program_id'], $clean['date_distributed']);
        if ($result['level'] !== 'clear' && mb_strlen($reason) < 5) {
            flash_old($data);
            $this->renderForm((string) $clean['program_id'], (string) $clean['program_beneficiary_id'], DuplicateAssistanceService::toPayload($result));
            return;
        }

        $total = $clean['unit_value'] !== null ? round((float) $clean['quantity'] * (float) $clean['unit_value'], 2) : null;
        $record = [
            'program_beneficiary_id' => (int) $clean['program_beneficiary_id'],
            'assistance_type_id'     => (int) $clean['assistance_type_id'],
            'quantity'               => $clean['quantity'],
            'unit'                   => $clean['unit'],
            'unit_value'             => $clean['unit_value'],
            'total_value'            => $total,
            'date_distributed'       => $clean['date_distributed'],
            'ack_receipt_no'         => $clean['ack_receipt_no'],
            'released_by'            => $clean['released_by'],
            'duplicate_flag'         => $result['level'] === 'clear' ? 0 : 1,
            'override_reason'        => $result['level'] === 'clear' ? null : mb_substr($reason, 0, 255),
            'recorded_by'            => Auth::id(),
        ];

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $record['assistance_no'] = IdGenerator::assistanceId();
            $id = AssistanceRecord::insert($record);
            ProgramBeneficiary::update((int) $row['id'], ['status' => 'served']);
            
            // Update program unit allocation tracking
            if ($clean['unit']) {
                Model::query(
                    'UPDATE program_unit_allocations SET distributed_amount = distributed_amount + ?, distributed_value = distributed_value + ? WHERE program_id = ? AND unit = ?',
                    [(float) $clean['quantity'], $total ?? 0, (int) $row['program_id'], $clean['unit']]
                );
            }
            
            AuditService::log('create', 'assistance_records', $id, null, $record);
            if ($result['level'] !== 'clear') {
                AuditService::log('override', 'assistance_records', $id, null,
                    ['matched_ids' => array_column($result['matches'], 'id')], 'Duplicate assistance override: ' . $reason);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        flash('success', 'Recorded ' . $record['assistance_no'] . ' for ' . Profile::fullName($row) . '.'
            . ($record['duplicate_flag'] ? ' Saved with a duplicate override.' : ''));
        $this->redirect('/assistance');
    }

    /** JSON endpoint used by the warning modal. Shape: see DuplicateAssistanceService::toPayload(). */
    public function checkDuplicate(): void
    {
        $bid  = (int) $this->input('program_beneficiary_id', 0);
        $type = (int) $this->input('assistance_type_id', 0);
        $date = (string) $this->input('date_distributed', '') ?: date('Y-m-d');
        $row  = $bid ? ProgramBeneficiary::detail($bid) : null;
        if (!$row || !$type || DateTime::createFromFormat('Y-m-d', $date) === false) {
            $this->json(['level' => 'clear']);      // incomplete form: the server validation will explain
        }
        $result = DuplicateAssistanceService::check((int) $row['profile_id'], $type, (int) $row['program_id'], $date);
        $this->json($result['level'] === 'clear' ? ['level' => 'clear'] : DuplicateAssistanceService::toPayload($result));
    }

    // ------------------------------------------------------------------ history

    /** Search a farmer (or pick a program) to see assistance history. */
    public function history(): void
    {
        $by = $this->input('by') === 'program' ? 'program' : 'farmer';
        $q = trim((string) $this->input('q', ''));
        $programId = (int) $this->input('program_id', 0);

        $people = [];
        $counts = [];
        if ($by === 'farmer' && $q !== '') {
            $people = Profile::search(['q' => $q], 1, 10)['data'];
            $counts = AssistanceRecord::countsByProfile(array_column($people, 'id'));
        }
        $this->view('assistance/history', [
            'title'       => 'Assistance History',
            'breadcrumbs' => [['Home', '/home'], ['Programs', '/programs'], ['History', null]],
            'by'          => $by,
            'q'           => $q,
            'people'      => $people,
            'counts'      => $counts,
            'programs'    => Program::options(),
            'programId'   => $programId,
            'records'     => $by === 'program' && $programId ? AssistanceRecord::forProgram($programId, 200) : [],
        ]);
    }

    /** One person's timeline, newest first. Also opened from the Farmer Profile's Assistance tab. */
    public function profileHistory(string $id): void
    {
        $profile = ctype_digit($id) ? Profile::withBarangay((int) $id) : null;
        if (!$profile) {
            abort(404, 'Profile not found.');
        }
        $live = Profile::getLivelihoods((int) $profile['id']);
        $profile['has_farming'] = $live['farming'] !== null;
        $profile['has_fishery'] = $live['fishery'] !== null;
        $this->view('assistance/profile-history', [
            'title'       => 'Assistance History',
            'breadcrumbs' => [['Home', '/home'], ['Programs', '/programs'], ['History', '/assistance/history'], [Profile::fullName($profile), null]],
            'profile'     => $profile,
            'records'     => AssistanceRecord::timelineForProfile((int) $profile['id']),
            'menu_active' => '/assistance/history',
        ]);
    }

    // ------------------------------------------------------------------ helpers

    private function renderForm(string $programId, string $beneficiaryId, ?array $duplicatePayload = null): void
    {
        $beneficiaries = [];
        if (ctype_digit($programId) && $programId !== '') {
            foreach (ProgramBeneficiary::forProgram((int) $programId) as $r) {
                if ($r['profile_status'] === 'active') {
                    $beneficiaries[$r['id']] = Profile::fullName($r) . ($r['barangay_name'] ? ' - ' . $r['barangay_name'] : '');
                }
            }
        }
        
        // Get assistance types for the program (or all if no program selected)
        $types = [];
        $typeUnits = [];
        if (ctype_digit($programId) && $programId !== '') {
            // Get units allocated to this program and filter types by those units
            $allocatedUnits = Model::query(
                'SELECT DISTINCT unit FROM program_unit_allocations WHERE program_id = ? AND allocated_amount > 0',
                [(int) $programId]
            )->fetchAll(PDO::FETCH_COLUMN);
            
            foreach (AssistanceType::active() as $t) {
                if (in_array($t['unit'], $allocatedUnits, true)) {
                    $types[$t['id']] = $t['name'];
                    $typeUnits[$t['id']] = $t['unit'] ?? '';
                }
            }
        } else {
            // All types if no program
            foreach (AssistanceType::active() as $t) {
                $types[$t['id']] = $t['name'];
                $typeUnits[$t['id']] = $t['unit'] ?? '';
            }
        }
        
        $this->view('assistance/create', [
            'title'            => 'Record Distribution',
            'breadcrumbs'      => [['Home', '/home'], ['Programs', '/programs'], ['Distribution', '/assistance'], ['Record Distribution', null]],
            'menu_active'      => '/assistance',
            'programs'         => Program::options(true),
            'programId'        => $programId,
            'beneficiaryId'    => $beneficiaryId,
            'beneficiaries'    => $beneficiaries,
            'types'            => $types,
            'typeUnits'        => $typeUnits,
            'duplicatePayload' => $duplicatePayload,
            'scripts'          => ['js/assistance-form.js', 'js/duplicate-guard.js'],
        ]);
    }

    private function formData(): array
    {
        $data = $this->only(self::FIELDS);
        foreach ($data as $k => $v) {
            $data[$k] = is_string($v) ? preg_replace('/\s+/', ' ', $v) : $v;
        }
        foreach (['quantity', 'unit_value'] as $k) {
            $data[$k] = is_string($data[$k]) ? str_replace([',', ' '], '', $data[$k]) : $data[$k];
        }
        return $data;
    }
}
