<?php
/**
 * Report catalogue for the Reports Center (process 6.0).
 * slug => [title, description]. Every report uses the one template
 * (views/reports/show.php); queries live in app/services/ReportService.php.
 */
return [
    'farmer-resource' => ['title' => 'Farmer and Resource Reports', 'icon' => 'people', 'reports' => [
        'masterlist'      => ['Farmer/Fisherfolk Masterlist', 'Registered profiles with livelihood, RSBSA/FishR numbers and status.'],
        'equipment'       => ['Equipment Inventory', 'Machinery and equipment, condition and who holds them.'],
        'organizations'   => ['Organization Report', 'Associations and cooperatives, registration and members.'],
        'livestock'       => ['Livestock and Poultry Report', 'Raisers and number of heads by barangay and species.'],
        'fisheries-boat'  => ['Fisheries and Boat Report', 'Boats, gear, fishponds and aquaculture units.'],
    ]],
    'production-monitoring' => ['title' => 'Production and Monitoring Reports', 'icon' => 'graph-up-arrow', 'reports' => [
        'production'      => ['Agricultural Production Report', 'Area planted and harvested, volume, yield and farm gate price.'],
        'training'        => ['Training and Technical Assistance Report', 'Trainings and activities with participants by sex.'],
        'damage-pest'     => ['Agricultural Damage and Pest Report', 'Pest, disease and calamity incidents, affected area and losses.'],
    ]],
    'programs-assistance' => ['title' => 'Programs and Assistance Reports', 'icon' => 'hand-thumbs-up', 'reports' => [
        'programs-beneficiaries' => ['Programs and Beneficiary Report', 'Beneficiaries per program and their status.'],
        'assistance-history'     => ['Beneficiary Assistance History', 'Everything each beneficiary received, with values.'],
        'program-accomplishment' => ['Program Accomplishment Report', 'Targets versus beneficiaries served and value distributed.'],
    ]],
    'summary' => ['title' => 'Summary Reports', 'icon' => 'clipboard2-check', 'reports' => [
        'barangay-commodity-summary' => ['Barangay/Commodity Summary', 'Farmers, farms, area, fishery, livestock and beneficiaries per barangay.'],
    ]],
];
