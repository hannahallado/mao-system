// --- 2.0 Record Agricultural Resources (replaces the old farms/fishery/livestock/equipment/organizations routes) ---
foreach ([
    'farms' => 'FarmController', 'fishery' => 'FisheryController', 'livestock' => 'LivestockController',
    'equipment' => 'EquipmentController', 'organizations' => 'OrganizationController',
] as $path => $ctl) {
    $router->get("/$path",                 "$ctl@index");
    $router->get("/$path/create",          "$ctl@create");
    $router->post("/$path",                "$ctl@store");
    $router->get("/$path/{id}",            "$ctl@show");
    $router->get("/$path/{id}/edit",       "$ctl@edit");
    $router->post("/$path/{id}",           "$ctl@update");
    $router->post("/$path/{id}/delete",    "$ctl@delete");
}
$router->post('/organizations/{id}/members',                'OrganizationController@addMember');
$router->post('/organizations/{id}/members/{mid}/remove',   'OrganizationController@removeMember');
