<?php
/** Process 1.0 - Profile/Livelihood Registry (refactored for separated profiles). */
class FarmerController extends Controller
{
    /** Friendly names for validation messages. */
    private const LABELS = [
        'last_name' => 'Last name', 'first_name' => 'First name', 'middle_name' => 'Middle name',
        'contact_no' => 'Contact number', 'barangay_id' => 'Barangay', 'address_detail' => 'Address',
        'livelihood_type' => 'Livelihood type', 'rsbsa_no' => 'RSBSA number', 'fishr_no' => 'FishR number',
        'status' => 'Status',
    ];

    private const PROFILE_FIELDS = [
        'last_name', 'first_name', 'middle_name', 'suffix', 'sex', 'birthdate', 'contact_no',
        'barangay_id', 'address_detail', 'status',
    ];

    private const LIVELIHOOD_FIELDS = [
        'livelihood_type', 'rsbsa_no', 'fishr_no',
    ];

    // ------------------------------------------------------------------ list

    public function index(): void
    {
        $filters = [
            'q'               => trim((string) $this->input('q', '')),
            'barangay_id'     => (string) $this->input('barangay_id', ''),
            'livelihood_type' => (string) $this->input('livelihood_type', ''),
            'status'          => (string) $this->input('status', ''),
        ];
        $result = Farmer::search($filters, (int) $this->input('page', 1));

        // Register / View / Edit all happen in modals on this page.
        // A modal opens on its own when asked (?register=1, ?edit={id}) or when
        // the last save came back with validation errors or a duplicate warning.
        $duplicate = $_SESSION['profile_duplicate'] ?? null;   // ['for' => 'register'|'edit', 'payload' => ...]
        unset($_SESSION['profile_duplicate']);

        $editId      = (string) $this->input('edit', '');
        $editProfile = ctype_digit($editId) ? $this->loadProfile((int) $editId) : null;

        $this->view('farmers/index', [
            'title'            => 'Registry',
            'breadcrumbs'      => [['Home', '/home'], ['Registry', null]],
            'filters'          => $filters,
            'result'           => $result,
            'barangays'        => Lookup::barangays(),
            'openRegister'     => isset($_GET['register']) || ($duplicate['for'] ?? '') === 'register',
            'editProfile'      => $editProfile,
            'duplicatePayload' => $duplicate['payload'] ?? null,
            'duplicateFor'     => $duplicate['for'] ?? null,
            'scripts'          => ['js/duplicate-guard.js', 'js/registry.js'],
        ]);
    }

    // ------------------------------------------------------------------ register

    /** No separate page: open the register modal on the list. */
    public function create(): void
    {
        $this->redirect(self::REGISTER_URL);
    }

    private const REGISTER_URL = '/farmers?register=1';

    /** Import profiles from CSV/Excel JSON prepared in the browser. */
    public function importProfiles(): void
    {
        if (!$this->isAjax()) {
            $this->redirect(self::REGISTER_URL);
        }

        $payload = json_decode((string) file_get_contents('php://input'), true);
        $rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
        if (!$rows) {
            $this->json(['ok' => false, 'message' => 'No profile rows were found.'], 422);
        }

        $barangays = Lookup::barangays();
        $barangayByName = [];
        foreach ($barangays as $id => $name) {
            $barangayByName[mb_strtolower(trim((string) $name))] = (int) $id;
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            foreach (array_slice($rows, 0, 1000) as $i => $row) {
                $rowNumber = $i + 2;
                $lastName = trim((string) ($row['last_name'] ?? $row['Last Name'] ?? $row['Last name'] ?? ''));
                $firstName = trim((string) ($row['first_name'] ?? $row['First Name'] ?? $row['First name'] ?? ''));
                $middleName = trim((string) ($row['middle_name'] ?? $row['Middle Name'] ?? ''));
                $suffix = trim((string) ($row['suffix'] ?? $row['Suffix'] ?? ''));
                $sex = ucfirst(mb_strtolower(trim((string) ($row['sex'] ?? $row['Sex'] ?? ''))));
                $barangay = trim((string) ($row['barangay'] ?? $row['Barangay'] ?? $row['barangay_id'] ?? ''));
                $address = trim((string) ($row['address_detail'] ?? $row['Address'] ?? $row['Purok / Sitio / Street'] ?? ''));
                $livelihood = mb_strtolower(trim((string) ($row['livelihood_type'] ?? $row['Livelihood'] ?? 'farmer')));
                $contact = trim((string) ($row['contact_no'] ?? $row['Contact'] ?? $row['Contact number'] ?? ''));
                $birthdate = trim((string) ($row['birthdate'] ?? $row['Birthdate'] ?? ''));
                $rsbsa = trim((string) ($row['rsbsa_no'] ?? $row['RSBSA'] ?? $row['RSBSA number'] ?? ''));
                $fishr = trim((string) ($row['fishr_no'] ?? $row['FishR'] ?? $row['FishR number'] ?? ''));

                if ($livelihood === 'fisherfolk') {
                    $livelihood = 'fisherfolk';
                } elseif ($livelihood === 'both' || str_contains($livelihood, 'farmer') && str_contains($livelihood, 'fisher')) {
                    $livelihood = 'both';
                } else {
                    $livelihood = 'farmer';
                }
                $barangayId = ctype_digit($barangay) ? (int) $barangay : ($barangayByName[mb_strtolower($barangay)] ?? 0);

                $rowErrors = [];
                if ($lastName === '' || mb_strlen($lastName) > 60) $rowErrors[] = 'invalid last name';
                if ($firstName === '' || mb_strlen($firstName) > 60) $rowErrors[] = 'invalid first name';
                if (!in_array($sex, ['Male', 'Female'], true)) $rowErrors[] = 'sex must be Male or Female';
                if (!$barangayId) $rowErrors[] = 'unknown barangay';
                if ($birthdate !== '' && (DateTime::createFromFormat('Y-m-d', $birthdate) === false || DateTime::createFromFormat('Y-m-d', $birthdate)->format('Y-m-d') !== $birthdate)) {
                    $rowErrors[] = 'birthdate must be YYYY-MM-DD';
                }
                if ($rowErrors) {
                    $errors[] = 'Row ' . $rowNumber . ': ' . implode(', ', $rowErrors);
                    $skipped++;
                    continue;
                }

                $nameKey = Profile::makeNameKey($lastName, $firstName);
                $exists = Profile::rawOne(
                    'SELECT id FROM profiles WHERE name_key = ? AND deleted_at IS NULL LIMIT 1',
                    [$nameKey]
                );
                if ($exists) {
                    $errors[] = 'Row ' . $rowNumber . ': duplicate profile skipped (' . $lastName . ', ' . $firstName . ')';
                    $skipped++;
                    continue;
                }

                Farmer::insert([
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'middle_name' => $middleName ?: null,
                    'suffix' => $suffix ?: null,
                    'sex' => $sex,
                    'birthdate' => $birthdate ?: null,
                    'contact_no' => $contact ?: null,
                    'barangay_id' => $barangayId,
                    'address_detail' => $address ?: null,
                    'livelihood_type' => $livelihood,
                    'rsbsa_no' => $rsbsa ?: null,
                    'fishr_no' => $livelihood === 'farmer' ? null : ($fishr ?: null),
                    'status' => 'active',
                    'name_key' => $nameKey,
                ]);
                $imported++;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $this->json(['ok' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }

        AuditService::log('import', 'profiles', 0, null, ['imported' => $imported, 'skipped' => $skipped], 'Bulk profile import');
        $this->json([
            'ok' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 100),
        ]);
    }

    public function store(): void
    {
        $data  = $this->formData();
        $clean = $this->validate($data, $this->rules(), self::REGISTER_URL, self::LABELS);

        // Server-side duplicate guard
        $reason = trim((string) $this->input('duplicate_override_reason', ''));
        $result = DuplicateProfileService::check($clean);
        if ($result['level'] !== 'clear' && ($result['level'] === 'blocked' || mb_strlen($reason) < 5)) {
            flash_old($data);
            $_SESSION['profile_duplicate'] = ['for' => 'register', 'payload' => DuplicateProfileService::toPayload($result)];
            $this->redirect(self::REGISTER_URL);
        }

        $profileData = [
            'last_name'      => $clean['last_name'],
            'first_name'     => $clean['first_name'],
            'middle_name'    => $clean['middle_name'] ?? null,
            'suffix'         => $clean['suffix'] ?? null,
            'sex'            => $clean['sex'],
            'birthdate'      => $clean['birthdate'] ?? null,
            'contact_no'     => $clean['contact_no'] ?? null,
            'barangay_id'    => $clean['barangay_id'],
            'address_detail' => $clean['address_detail'] ?? null,
            'status'         => 'active',
            'name_key'       => Profile::makeNameKey($clean['last_name'], $clean['first_name']),
        ];

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            $profileId = Profile::insert($profileData);

            // Create livelihood profiles based on livelihood_type
            $type = $clean['livelihood_type'] ?? 'farmer';
            if (in_array($type, ['farmer', 'both'])) {
                FarmingProfile::insert([
                    'id'       => $profileId,
                    'rsbsa_no' => $clean['rsbsa_no'] ?? null,
                    'status'   => 'active',
                ]);
            }
            if (in_array($type, ['fisherfolk', 'both'])) {
                FisheryProfile::insert([
                    'id'       => $profileId,
                    'rsbsa_no' => $clean['rsbsa_no'] ?? null,
                    'fishr_no' => $clean['fishr_no'] ?? null,
                    'status'   => 'active',
                ]);
            }

            AuditService::log('create', 'profiles', $profileId, null, $profileData);
            if ($result['level'] === 'warning') {
                AuditService::log('override', 'profiles', $profileId, null,
                    ['matched_ids' => array_column($result['matches'], 'id')], 'Duplicate override: ' . $reason);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        flash('success', 'Registered ' . Profile::fullName($profileData) . '.');
        $this->redirect('/farmers/' . $profileId);
    }

    public function checkDuplicate(): void
    {
        $data    = $this->formData();
        $exclude = (int) $this->input('exclude_id', 0) ?: null;

        if ($exclude) {
            $stored = Profile::withBarangay($exclude);
            if ($stored && !DuplicateProfileService::identityChanged($stored, $data)) {
                $this->json(['level' => 'clear']);
                return;
            }
        }
        $result = DuplicateProfileService::check($data, $exclude);
        $this->json($result['level'] === 'clear' ? ['level' => 'clear'] : DuplicateProfileService::toPayload($result));
    }

    // ------------------------------------------------------------------ view / edit

    public function show(string $id): void
    {
        $profile = $this->findOr404($id);
        if ($this->isAjax()) {   // View modal on the Registry list
            $this->view('farmers/_details', ['profile' => $profile], null);
            return;
        }
        $this->view('farmers/show', [
            'title'       => Profile::fullName($profile),
            'breadcrumbs' => [['Home', '/home'], ['Registry', '/farmers'], [Profile::fullName($profile), null]],
            'profile'     => $profile,
        ]);
    }

    public function edit(string $id): void
    {
        $profile = $this->findOr404($id);
        if ($this->isAjax()) {   // Edit modal on the Registry list
            $this->view('farmers/_edit_body', ['profile' => $profile, 'barangays' => Lookup::barangays()], null);
            return;
        }
        $this->renderForm('edit', $profile);
    }

    public function delete(string $id): void
    {
        // Only agriculturists can delete
        if (!Auth::isAgriculturist()) {
            abort(403, 'Unauthorized. Only agriculturists can delete farmers/fisherfolk.');
        }

        $profile = $this->findOr404($id);
        $name = Profile::fullName($profile);

        $pdo = Database::connect();
        $pdo->beginTransaction();
        try {
            // Archive livelihoods first
            FarmingProfile::archive((int) $profile['id']);
            FisheryProfile::archive((int) $profile['id']);
            
            // Archive profile
            Profile::archive((int) $profile['id']);

            AuditService::log('archive', 'profiles', (int) $profile['id'], null, ['name' => $name], 'Profile deleted by user');
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        flash('success', $name . ' has been removed from the registry.');
        $this->redirect('/farmers');
    }

    public function update(string $id): void
    {
        $profile = $this->findOr404($id);
        $data    = $this->formData() + ['status' => (string) $this->input('status', '')];
        $rules   = $this->rules((int) $profile['id']) + ['status' => 'required|in:' . implode(',', array_keys(Profile::STATUSES))];
        $back    = '/farmers?edit=' . $profile['id'];   // reopens the Edit modal on the list
        $clean   = $this->validate($data, $rules, $back, self::LABELS);

        $reason = trim((string) $this->input('duplicate_override_reason', ''));
        $result = ['level' => 'clear', 'matches' => []];
        if (DuplicateProfileService::identityChanged($profile, $clean)) {
            $result = DuplicateProfileService::check($clean, (int) $profile['id']);
            if ($result['level'] !== 'clear' && ($result['level'] === 'blocked' || mb_strlen($reason) < 5)) {
                flash_old($data);
                $_SESSION['profile_duplicate'] = ['for' => 'edit', 'payload' => DuplicateProfileService::toPayload($result)];
                $this->redirect($back);
            }
        }

        // Update profile data
        $profileData = [];
        foreach (self::PROFILE_FIELDS as $col) {
            if (array_key_exists($col, $clean)) {
                $profileData[$col] = $clean[$col];
            }
        }
        $profileData['name_key'] = Profile::makeNameKey($clean['last_name'], $clean['first_name']);
        Profile::update((int) $profile['id'], $profileData);

        // Update livelihoods
        $livelihoods = Profile::getLivelihoods((int) $profile['id']);
        $type = $clean['livelihood_type'] ?? 'farmer';

        if (in_array($type, ['farmer', 'both'])) {
            if ($livelihoods['farming']) {
                FarmingProfile::update((int) $profile['id'], ['rsbsa_no' => $clean['rsbsa_no'] ?? null]);
            } else {
                FarmingProfile::insert([
                    'id'       => (int) $profile['id'],
                    'rsbsa_no' => $clean['rsbsa_no'] ?? null,
                    'status'   => $clean['status'] ?? 'active',
                ]);
            }
        } elseif ($livelihoods['farming']) {
            // Remove farming livelihood
            FarmingProfile::delete((int) $profile['id']);
        }

        if (in_array($type, ['fisherfolk', 'both'])) {
            if ($livelihoods['fishery']) {
                FisheryProfile::update((int) $profile['id'], ['rsbsa_no' => $clean['rsbsa_no'] ?? null, 'fishr_no' => $clean['fishr_no'] ?? null]);
            } else {
                FisheryProfile::insert([
                    'id'       => (int) $profile['id'],
                    'rsbsa_no' => $clean['rsbsa_no'] ?? null,
                    'fishr_no' => $clean['fishr_no'] ?? null,
                    'status'   => $clean['status'] ?? 'active',
                ]);
            }
        } elseif ($livelihoods['fishery']) {
            // Remove fishery livelihood
            FisheryProfile::delete((int) $profile['id']);
        }

        [$old, $new] = AuditService::diff($profile, $profileData, self::PROFILE_FIELDS);
        if ($new) {
            AuditService::log('update', 'profiles', (int) $profile['id'], $old, $new);
        }
        if ($result['level'] === 'warning') {
            AuditService::log('override', 'profiles', (int) $profile['id'], null,
                ['matched_ids' => array_column($result['matches'], 'id')], 'Duplicate override: ' . $reason);
        }

        flash('success', Profile::fullName($profileData + $profile) . ' was updated.');
        $this->redirect('/farmers');
    }

    // ------------------------------------------------------------------ helpers

    private function renderForm(string $mode, ?array $profile = null, ?array $duplicatePayload = null): void
    {
        // Only the edit page uses this now; registering happens in the list's modal
        $name   = $profile ? Profile::fullName($profile) : null;
        $title  = 'Edit Profile';
        $crumbs = [['Home', '/home'], ['Registry', '/farmers'], [$name, '/farmers/' . $profile['id']], [$title, null]];

        $this->view('farmers/' . $mode, [
            'title'            => $title,
            'breadcrumbs'      => $crumbs,
            'profile'          => $profile ?? [],
            'barangays'        => Lookup::barangays(),
            'duplicatePayload' => $duplicatePayload,
            'scripts'          => ['js/duplicate-guard.js'],
        ]);
    }

    private function findOr404(string $id): array
    {
        $profile = ctype_digit($id) ? $this->loadProfile((int) $id) : null;
        if (!$profile) {
            abort(404, 'Profile not found.');
        }
        return $profile;
    }

    /** Profile + livelihood details, or null if it doesn't exist. */
    private function loadProfile(int $id): ?array
    {
        $profile = Profile::withBarangay($id);
        if (!$profile) {
            return null;
        }

        $livelihoods = Profile::getLivelihoods((int) $profile['id']);
        $profile['has_farming']   = $livelihoods['farming'] !== null;
        $profile['has_fishery']   = $livelihoods['fishery'] !== null;
        $profile['farming_rsbsa'] = $livelihoods['farming']['rsbsa_no'] ?? null;
        $profile['fishery_rsbsa'] = $livelihoods['fishery']['rsbsa_no'] ?? null;
        $profile['fishr_no']      = $livelihoods['fishery']['fishr_no'] ?? null;

        // Fields the shared form reads (they were missing, so Edit showed them blank)
        $profile['rsbsa_no'] = $profile['farming_rsbsa'] ?? $profile['fishery_rsbsa'];
        $profile['livelihood_type'] = match (true) {
            $profile['has_farming'] && $profile['has_fishery'] => 'both',
            $profile['has_fishery'] => 'fisherfolk',
            $profile['has_farming'] => 'farmer',
            default => '',
        };

        return $profile;
    }

    /** Submitted profile fields with whitespace tidied. */
    private function formData(): array
    {
        $allFields = array_merge(self::PROFILE_FIELDS, self::LIVELIHOOD_FIELDS);
        $data = $this->only($allFields);
        foreach ($data as $k => $v) {
            $data[$k] = is_string($v) ? preg_replace('/\s+/', ' ', $v) : $v;
        }
        return $data;
    }

    private function rules(?int $ignoreId = null): array
    {
        $ign = $ignoreId ? ',' . $ignoreId : '';
        return [
            'last_name'       => 'required|max:60',
            'first_name'      => 'required|max:60',
            'middle_name'     => 'nullable|max:60',
            'suffix'          => 'nullable|max:10',
            'sex'             => 'required|in:' . implode(',', array_keys(Profile::SEXES)),
            'birthdate'       => 'nullable|date|not_future',
            'contact_no'      => 'nullable|phone',
            'barangay_id'     => 'required|integer|exists:barangays,id',
            'address_detail'  => 'nullable|max:150',
            'livelihood_type' => 'required|in:' . implode(',', array_keys(Farmer::LIVELIHOODS)),
            'rsbsa_no'        => 'nullable|max:30',
            'fishr_no'        => 'nullable|max:30',
        ];
    }
}
