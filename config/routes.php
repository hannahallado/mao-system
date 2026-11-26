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
$router->get('/',     'LandingController@index', ['public' => true]);
$router->get('/home', 'HomeController@index');

// --- Notifications (top bar bell) ---
$router->get('/notifications/open',      'NotificationController@open');
$router->post('/notifications/read-all', 'NotificationController@readAll');

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

// --- 2.0 Record Agricultural Resources (one page, tabs: config/modules.php) ---
$router->get('/resources',                     'ModuleController@resources');
$router->post('/resources/{tab}',              'ModuleController@resourcesStore');
$router->post('/resources/{tab}/{id}/delete',  'ModuleController@resourcesDelete');
$router->post('/resources/{tab}/{id}',         'ModuleController@resourcesUpdate');
// Old one-page-per-record URLs open the matching tab
foreach (['/farms', '/fishery', '/livestock', '/equipment', '/organizations'] as $old) {
    $router->get($old, 'ModuleController@legacy');
    $router->get($old . '/{x}', 'ModuleController@legacy');
}

// --- 3.0 Map Farm and Fishery Locations (GIS) ---
$router->get('/map', 'MapController@index');
$router->get('/map/farms', 'MapController@farms');
$router->get('/map/fishery', 'MapController@fishery');

// --- 4.0 Monitor Agricultural Activities (one page, tabs: config/modules.php) ---
$router->get('/monitoring',                    'ModuleController@monitoring');
foreach (['/monitoring/production', '/monitoring/damage', '/monitoring/training'] as $old) {
    $router->get($old, 'ModuleController@legacy');
    $router->get($old . '/{x}', 'ModuleController@legacy');
}
$router->post('/monitoring/{tab}',             'ModuleController@monitoringStore');
$router->post('/monitoring/{tab}/{id}/delete', 'ModuleController@monitoringDelete');
$router->post('/monitoring/{tab}/{id}',        'ModuleController@monitoringUpdate');

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

// --- System (Municipal Agriculturist only): one page, tabs ---
$agri = ['roles' => ['agriculturist']];
$router->get('/system',                    'SystemController@index',        $agri);
$router->post('/system/users',             'SystemController@storeUser',    $agri);
$router->post('/system/users/{id}/status', 'SystemController@toggleUser',   $agri);
$router->post('/system/users/{id}',        'SystemController@updateUser',   $agri);
$router->post('/system/backup',            'SystemController@backup',       $agri);
$router->post('/system/settings',          'SystemController@saveSettings', $agri);
foreach (['/system/users', '/system/users/create', '/system/access-control', '/system/backup', '/system/settings'] as $old) {
    $router->get($old, 'SystemController@legacy', $agri);   // old links open the matching tab
}
