<?php
/**
 * Demo data for Programs and Assistance (uses the people already in your registry - run after seeding/registering some).
 *   php database/seed_programs_demo.php          (aborts if programs already exist; add --force to add anyway)
 *
 * Creates two programs, puts up to 6 active registered profiles on the first one, and records ONE earlier
 * distribution (Certified Rice Seeds, about two months ago) so you can demo the duplicate warning:
 *   Assistance Distribution > Record Distribution > same program, same person, Certified Rice Seeds  -> warning.
 */
if (PHP_SAPI !== 'cli') { exit('CLI only.'); }
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
require APP_PATH . '/bootstrap.php';

if (Program::count() > 0 && !in_array('--force', $argv, true)) {
    exit("Programs already exist. Use --force to add demo data anyway.\n");
}
$people = Model::raw('SELECT id FROM profiles WHERE deleted_at IS NULL AND status = "active" ORDER BY id LIMIT 6');
if (!$people) { exit("No active profiles found. Register some farmers/fisherfolk first.\n"); }

$year = (int) date('Y');
$p1 = Program::insert([
    'program_no' => IdGenerator::programId(), 'name' => "Rice Seed Distribution $year", 'source_agency' => 'Department of Agriculture',
    'year' => $year, 'description' => 'Certified rice seeds for registered rice farmers.', 'target_beneficiaries' => 20,
    'start_date' => "$year-03-01", 'end_date' => "$year-12-31", 'status' => 'ongoing', 'allocation_amount' => 150000,
]);
$p2 = Program::insert([
    'program_no' => IdGenerator::programId(), 'name' => "Fertilizer Subsidy $year", 'source_agency' => 'LGU',
    'year' => $year, 'description' => 'Subsidized fertilizer for the wet season.', 'target_beneficiaries' => 10,
    'start_date' => "$year-06-01", 'end_date' => "$year-09-30", 'status' => 'planned',
]);

$rows = [];
foreach ($people as $i => $p) {
    $rows[] = ProgramBeneficiary::insert(['program_id' => $p1, 'profile_id' => $p['id'], 'status' => $i === 0 ? 'served' : 'approved']);
}
$seed = Model::rawOne('SELECT id, unit FROM assistance_types WHERE name = "Certified Rice Seeds"');
if ($seed) {
    AssistanceRecord::insert([
        'assistance_no' => IdGenerator::assistanceId(), 'program_beneficiary_id' => $rows[0], 'assistance_type_id' => $seed['id'],
        'quantity' => 2, 'unit' => $seed['unit'], 'date_distributed' => date('Y-m-d', strtotime('-2 months')), 'released_by' => 'MAO Staff',
        'duplicate_flag' => 0,
    ]);
}
echo "Created 2 programs, " . count($rows) . " beneficiaries and 1 earlier distribution.\n";
