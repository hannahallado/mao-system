<?php
/**
 * Route table.  $router->get|post(path, 'Controller@method', options)
 *
 * Options:
 *   'public' => true                no login required
 *   'roles'  => ['agriculturist']   restrict to these roles
 *
 * IMPORTANT: register fixed paths (e.g. /farmers/create, /farmers/check-duplicate)
 * BEFORE wildcard paths (e.g. /farmers/{id}).
 * Every POST is CSRF-checked automatically.
 */

// --- Auth ---
$router->get('/login',   'AuthController@showLogin', ['public' => true]);
$router->post('/login',  'AuthController@login',     ['public' => true]);
$router->post('/logout', 'AuthController@logout');

// --- Home ---
$router->get('/',     'HomeController@index');
$router->get('/home', 'HomeController@index');

// --- Dev: component style guide ---
$router->get('/ui-kit', 'StyleGuideController@index');

// --- 1.0 Register Farmers/Fisherfolk ---
$router->get('/farmers',                  'FarmerController@index');
$router->get('/farmers/create',           'FarmerController@create');
$router->post('/farmers',                 'FarmerController@store');
$router->post('/farmers/check-duplicate', 'FarmerController@checkDuplicate');   // JSON for the warning modal
$router->get('/farmers/{id}',             'FarmerController@show');
$router->get('/farmers/{id}/edit',        'FarmerController@edit');
$router->post('/farmers/{id}',            'FarmerController@update');
$router->post('/farmers/{id}/delete',     'FarmerController@delete');

// --- 2.0 Record Agricultural Resources ---
$router->get('/farms', 'FarmController@index');
$router->get('/farms/create', 'FarmController@create');
$router->get('/farms/{id}', 'FarmController@show');
$router->get('/fishery', 'FisheryController@index');
$router->get('/fishery/create', 'FisheryController@create');
$router->get('/fishery/{id}', 'FisheryController@show');
$router->get('/livestock', 'LivestockController@index');
$router->get('/livestock/create', 'LivestockController@create');
$router->get('/equipment', 'EquipmentController@index');
$router->get('/equipment/create', 'EquipmentController@create');
$router->get('/organizations', 'OrganizationController@index');
$router->get('/organizations/create', 'OrganizationController@create');
$router->get('/organizations/{id}', 'OrganizationController@show');

// --- 3.0 Map Farm and Fishery Locations (GIS) ---
$router->get('/map', 'MapController@index');
$router->get('/map/farms', 'MapController@farms');
$router->get('/map/fishery', 'MapController@fishery');

// --- 4.0 Monitor Agricultural Activities ---
$router->get('/monitoring', 'MonitoringController@index');
$router->get('/monitoring/production', 'ProductionController@index');
$router->get('/monitoring/production/create', 'ProductionController@create');
$router->get('/monitoring/damage', 'DamageController@index');
$router->get('/monitoring/damage/create', 'DamageController@create');
$router->get('/monitoring/training', 'TrainingController@index');
$router->get('/monitoring/training/create', 'TrainingController@create');
$router->get('/monitoring/training/{id}', 'TrainingController@show');

// --- 5.0 Administer Agricultural Programs ---
// Programs: only the Municipal Agriculturist creates/edits (access matrix); everyone can view.
$router->get('/programs',              'ProgramController@index');
$router->get('/programs/create',       'ProgramController@create', ['roles' => ['agriculturist']]);
$router->post('/programs',             'ProgramController@store',  ['roles' => ['agriculturist']]);
$router->get('/programs/{id}',         'ProgramController@show');
$router->get('/programs/{id}/edit',    'ProgramController@edit',   ['roles' => ['agriculturist']]);
$router->post('/programs/{id}',        'ProgramController@update', ['roles' => ['agriculturist']]);
$router->post('/programs/{id}/delete', 'ProgramController@delete', ['roles' => ['agriculturist']]);
// Beneficiary master list (people come from the registry D1 only)
$router->get('/beneficiaries/search',                    'BeneficiaryController@search');            // JSON for the picker
$router->get('/programs/{id}/beneficiaries',             'BeneficiaryController@index');             // redirects to the tab
$router->get('/programs/{id}/beneficiaries/options',     'BeneficiaryController@options');           // JSON for the distribution form
$router->post('/programs/{id}/beneficiaries',            'BeneficiaryController@add');
$router->post('/programs/{id}/beneficiaries/{bid}/status', 'BeneficiaryController@status');
$router->post('/programs/{id}/beneficiaries/{bid}/remove', 'BeneficiaryController@remove');
// Distribution + duplicate assistance warning (register fixed paths BEFORE any /assistance/{id})
$router->get('/assistance',                       'AssistanceController@index');
$router->get('/assistance/create',                'AssistanceController@create');
$router->post('/assistance/check-duplicate',      'AssistanceController@checkDuplicate');   // JSON for the warning modal
$router->post('/assistance',                      'AssistanceController@store');
$router->get('/assistance/history',               'AssistanceController@history');
$router->get('/profiles/{id}/assistance-history', 'AssistanceController@profileHistory');
$router->get('/farmers/{id}/assistance-history',  'AssistanceController@profileHistory');   // Backward compat

// --- 6.0 Generate Reports ---
$router->get('/reports',                'ReportController@index');
$router->get('/reports/group/{group}',  'ReportController@group');
$router->get('/reports/{slug}',         'ReportController@show');

// --- System Management (Municipal Agriculturist only) ---
$router->get('/system/users', 'UserController@index', ['roles' => ['agriculturist']]);
$router->get('/system/users/create', 'UserController@create', ['roles' => ['agriculturist']]);
$router->get('/system/access-control', 'AccessControlController@index', ['roles' => ['agriculturist']]);
$router->get('/system/backup', 'BackupController@index', ['roles' => ['agriculturist']]);
$router->get('/system/settings', 'SettingController@index', ['roles' => ['agriculturist']]);
