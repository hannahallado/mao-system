<?php
/** Process 5.0 Administer Agricultural Programs - programs and projects (functional). */
class ProgramController extends Controller
{
    private const LABELS = [
        'name' => 'Program name', 'source_agency' => 'Source agency', 'year' => 'Year',
        'allocation_amount' => 'Allocation', 'target_beneficiaries' => 'Target beneficiaries',
        'start_date' => 'Start date', 'end_date' => 'End date', 'status' => 'Status',
    ];
    private const FIELDS = [
        'name', 'description', 'objectives', 'source_agency', 'year', 'allocation_amount',
        'target_beneficiaries', 'start_date', 'end_date', 'status',
    ];

    public function index(): void
    {
        $filters = [
            'q'             => trim((string) $this->input('q', '')),
            'status'        => (string) $this->input('status', ''),
            'year'          => (string) $this->input('year', ''),
            'source_agency' => (string) $this->input('source_agency', ''),
        ];
        $this->view('programs/index', [
            'title'       => 'Programs and Projects',
            'breadcrumbs' => [['Home', '/home'], ['Programs', null]],
            'filters'     => $filters,
            'result'      => Program::search($filters, (int) $this->input('page', 1)),
            'years'       => Program::years(),
        ]);
    }

    public function create(): void
    {
        $this->renderForm('create', ['status' => 'planned', 'year' => date('Y')]);
    }

    public function store(): void
    {
        $clean = $this->check($this->formData(), '/programs/create');
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $row = $clean + ['program_no' => IdGenerator::programId()];
            $id = Program::insert($row);
            
            // Save unit allocations from assistance_allocations
            $allocationData = $this->input('assistance_allocations', []);
            if (is_array($allocationData)) {
                // Group by unit to aggregate quantities and values
                $unitMap = [];
                foreach ($allocationData as $alloc) {
                    $unit = $alloc['unit'] ?? null;
                    if (!$unit) continue;
                    
                    if (!isset($unitMap[$unit])) {
                        $unitMap[$unit] = [
                            'amount' => 0,
                            'unit_value' => null
                        ];
                    }
                    
                    $unitMap[$unit]['amount'] += (float) ($alloc['quantity'] ?? 0);
                    // Use the unit_value from the first item (they should all be the same per unit)
                    if ($alloc['unit_value'] !== null && $alloc['unit_value'] !== '') {
                        $unitMap[$unit]['unit_value'] = (float) $alloc['unit_value'];
                    }
                }
                
                // Insert into program_unit_allocations
                foreach ($unitMap as $unit => $data) {
                    Model::query(
                        'INSERT INTO program_unit_allocations (program_id, unit, allocated_amount, unit_value) VALUES (?, ?, ?, ?)',
                        [$id, $unit, $data['amount'], $data['unit_value']]
                    );
                }
            }
            
            AuditService::log('create', 'programs', $id, null, $row);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        flash('success', 'Program ' . $row['program_no'] . ' created. Add its beneficiaries next.');
        $this->redirect('/programs/' . $id . '?tab=beneficiaries');
    }

    public function delete(string $id): void
    {
        // Only agriculturists can delete
        if (!Auth::isAgriculturist()) {
            abort(403, 'Unauthorized. Only agriculturists can delete programs.');
        }

        $program = $this->findOr404($id);
        $programNo = $program['program_no'];

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            // Delete related records (not soft-deleted since they're just linking records)
            Model::query('DELETE FROM program_beneficiaries WHERE program_id = ?', [(int) $program['id']]);
            Model::query('DELETE FROM program_unit_allocations WHERE program_id = ?', [(int) $program['id']]);
            
            // Archive program (soft delete)
            Program::archive((int) $program['id']);

            AuditService::log('archive', 'programs', (int) $program['id'], null, ['program_no' => $programNo], 'Program deleted by user');
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        flash('success', 'Program ' . $programNo . ' has been removed.');
        $this->redirect('/programs');
    }

    public function show(string $id): void
    {
        $program = $this->findOr404($id);
        $tab = in_array($this->input('tab'), ['overview', 'beneficiaries', 'distributions'], true) ? $this->input('tab') : 'overview';
        $this->view('programs/show', [
            'title'         => $program['name'],
            'breadcrumbs'   => [['Home', '/home'], ['Programs', '/programs'], [$program['name'], null]],
            'program'       => $program,
            'tab'           => $tab,
            'beneficiaries' => ProgramBeneficiary::forProgram((int) $program['id']),
            'distributions' => AssistanceRecord::forProgram((int) $program['id']),
            'scripts'       => ['js/beneficiary-picker.js'],
        ]);
    }

    public function edit(string $id): void
    {
        $this->renderForm('edit', $this->findOr404($id));
    }

    public function update(string $id): void
    {
        $program = $this->findOr404($id);
        $clean = $this->check($this->formData(), '/programs/' . $program['id'] . '/edit');
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            Program::update((int) $program['id'], $clean);
            
            // Update unit allocations from assistance_allocations
            Model::query('DELETE FROM program_unit_allocations WHERE program_id = ?', [(int) $program['id']]);
            $allocationData = $this->input('assistance_allocations', []);
            if (is_array($allocationData)) {
                // Group by unit to aggregate quantities and values
                $unitMap = [];
                foreach ($allocationData as $alloc) {
                    $unit = $alloc['unit'] ?? null;
                    if (!$unit) continue;
                    
                    if (!isset($unitMap[$unit])) {
                        $unitMap[$unit] = [
                            'amount' => 0,
                            'unit_value' => null
                        ];
                    }
                    
                    $unitMap[$unit]['amount'] += (float) ($alloc['quantity'] ?? 0);
                    // Use the unit_value from the first item (they should all be the same per unit)
                    if ($alloc['unit_value'] !== null && $alloc['unit_value'] !== '') {
                        $unitMap[$unit]['unit_value'] = (float) $alloc['unit_value'];
                    }
                }
                
                // Insert into program_unit_allocations
                foreach ($unitMap as $unit => $data) {
                    Model::query(
                        'INSERT INTO program_unit_allocations (program_id, unit, allocated_amount, unit_value) VALUES (?, ?, ?, ?)',
                        [(int) $program['id'], $unit, $data['amount'], $data['unit_value']]
                    );
                }
            }
            
            [$old, $new] = AuditService::diff($program, $clean, array_keys($clean));
            if ($new) {
                AuditService::log('update', 'programs', (int) $program['id'], $old, $new);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        flash('success', 'Program updated.');
        $this->redirect('/programs/' . $program['id']);
    }

    // ------------------------------------------------------------------ helpers

    private function renderForm(string $mode, array $program): void
    {
        $title = $mode === 'create' ? 'Create Program' : 'Edit Program';
        $crumbs = [['Home', '/home'], ['Programs', '/programs']];
        if ($mode === 'edit') {
            $crumbs[] = [$program['name'], '/programs/' . $program['id']];
        }
        $crumbs[] = [$title, null];
        
        // Get all assistance types with their units
        $allTypes = Model::query('SELECT id, name, unit FROM assistance_types ORDER BY name')->fetchAll();
        
        // Build assistanceTypes array: [id => ['name' => ..., 'unit' => ...]]
        $assistanceTypes = [];
        foreach ($allTypes as $type) {
            $assistanceTypes[$type['id']] = [
                'name' => $type['name'],
                'unit' => $type['unit'],
            ];
        }
        
        // Load allocations if editing
        $allocations = [];
        if ($mode === 'edit') {
            $allocs = Model::query('SELECT unit, allocated_amount, unit_value FROM program_unit_allocations WHERE program_id = ?', [(int) $program['id']])->fetchAll();
            foreach ($allocs as $alloc) {
                $allocations[$alloc['unit']] = [
                    'amount' => $alloc['allocated_amount'],
                    'unit_value' => $alloc['unit_value'],
                ];
            }
        }
        
        $this->view('programs/' . $mode, [
            'title' => $title,
            'breadcrumbs' => $crumbs,
            'program' => $program + ['allocations' => $allocations],
            'assistanceTypes' => $assistanceTypes,
        ]);
    }

    private function findOr404(string $id): array
    {
        $program = ctype_digit($id) ? Program::withStats((int) $id) : null;
        if (!$program) {
            abort(404, 'Program not found.');
        }
        return $program;
    }

    private function formData(): array
    {
        $data = $this->only(self::FIELDS);
        foreach (['name', 'source_agency', 'year', 'target_beneficiaries', 'status'] as $k) {
            $data[$k] = is_string($data[$k]) ? preg_replace('/\s+/', ' ', $data[$k]) : $data[$k];
        }
        $data['allocation_amount'] = is_string($data['allocation_amount']) ? str_replace([',', ' '], '', $data['allocation_amount']) : $data['allocation_amount'];
        return $data;
    }

    /** Validate; on failure flash errors + old input and go back. Returns clean values. */
    private function check(array $data, string $backTo): array
    {
        $v = Validator::make($data, [
            'name'                 => 'required|max:150',
            'description'          => 'nullable|max:2000',
            'objectives'           => 'nullable|max:2000',
            'source_agency'        => 'required|in:' . implode(',', array_keys(Program::AGENCIES)),
            'year'                 => 'required|integer',
            'allocation_amount'    => 'nullable|numeric',
            'target_beneficiaries' => 'nullable|integer',
            'start_date'           => 'nullable|date',
            'end_date'             => 'nullable|date',
            'status'               => 'required|in:' . implode(',', array_keys(Program::STATUSES)),
        ], self::LABELS);
        $errors = $v->errors();
        $clean = $v->validated();

        if (!isset($errors['year']) && $clean['year'] !== null && ((int) $clean['year'] < 2000 || (int) $clean['year'] > 2100)) {
            $errors['year'][] = 'Year must be between 2000 and 2100.';
        }
        if (!isset($errors['allocation_amount']) && $clean['allocation_amount'] !== null && (float) $clean['allocation_amount'] < 0) {
            $errors['allocation_amount'][] = 'Allocation cannot be negative.';
        }
        if (!isset($errors['target_beneficiaries']) && $clean['target_beneficiaries'] !== null && (int) $clean['target_beneficiaries'] < 0) {
            $errors['target_beneficiaries'][] = 'Target beneficiaries cannot be negative.';
        }
        if ($clean['start_date'] && $clean['end_date'] && !isset($errors['end_date']) && $clean['end_date'] < $clean['start_date']) {
            $errors['end_date'][] = 'End date cannot be before the start date.';
        }
        if ($errors) {
            flash_errors($errors);
            flash_old($data);
            redirect($backTo);
        }
        return $clean;
    }
}
