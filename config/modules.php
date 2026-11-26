<?php
/**
 * Record modules: one page per module, one tab per record type.
 * ModuleController + RecordModule + views/modules/index.php render and save
 * every tab from this config, so adding a field means adding a line here
 * (and the column in database/schema_modules.sql).
 *
 * Tab keys:
 *   table      database table (soft deletes via deleted_at)
 *   label      tab label            singular  used in "Add Farm", messages
 *   icon       Bootstrap icon       sector    technician write restriction (crops|livestock_poultry|fisheries|null)
 *   fields     name => [label, type, required?, options?, col?, help?, max?, step?, list?]
 *              types: text, textarea, number, integer, date, month, select,
 *                     barangay, profile, farm, organization, members (many profiles)
 *   list       columns shown in the table (field names, or 'computed:<key>')
 *   search     columns matched by the search box ('profile_id' searches the name)
 *   filters    field names shown as filter dropdowns (select/barangay types)
 *   order      ORDER BY for the list (columns of the table)
 */

$commodities = [
    'rice' => 'Rice', 'corn' => 'Corn', 'sugarcane' => 'Sugarcane', 'coconut' => 'Coconut',
    'banana' => 'Banana', 'vegetables' => 'Vegetables', 'root_crops' => 'Root crops',
    'fruit_trees' => 'Fruit trees', 'others' => 'Others',
];
$fishSpecies = ['Bangus' => 'Bangus', 'Tilapia' => 'Tilapia', 'Shrimp' => 'Shrimp', 'Crab' => 'Crab', 'Seaweed' => 'Seaweed', 'Others' => 'Others'];

return [

    // ======================================================================
    // 2.0 Record Agricultural Resources (D2), with farm/fishery location (D3)
    // ======================================================================
    'resources' => [
        'title'    => 'Resources',
        'subtitle' => 'Farms, fishery assets, livestock, equipment and organizations linked to registered profiles.',
        'path'     => '/resources',
        'tabs'     => [

            'farms' => [
                'table' => 'farms', 'label' => 'Farms', 'singular' => 'Farm', 'icon' => 'tree', 'sector' => 'crops',
                'fields' => [
                    'farm_name'    => ['label' => 'Farm / parcel name', 'type' => 'text', 'required' => true, 'max' => 120, 'col' => 'col-md-6'],
                    'profile_id'   => ['label' => 'Owner / operator', 'type' => 'profile', 'required' => true, 'col' => 'col-md-6'],
                    'barangay_id'  => ['label' => 'Barangay', 'type' => 'barangay', 'required' => true, 'col' => 'col-md-4'],
                    'area_ha'      => ['label' => 'Area (ha)', 'type' => 'number', 'required' => true, 'step' => '0.0001', 'col' => 'col-md-4'],
                    'commodity'    => ['label' => 'Main commodity', 'type' => 'select', 'required' => true, 'options' => $commodities, 'col' => 'col-md-4'],
                    'water_source' => ['label' => 'Water source', 'type' => 'select', 'options' => ['irrigated' => 'Irrigated', 'rainfed' => 'Rainfed', 'upland' => 'Upland'], 'col' => 'col-md-4'],
                    'tenure'       => ['label' => 'Tenure', 'type' => 'select', 'options' => ['owner' => 'Owner', 'tenant' => 'Tenant', 'lessee' => 'Lessee', 'others' => 'Others'], 'col' => 'col-md-4'],
                    'latitude'     => ['label' => 'Latitude', 'type' => 'number', 'step' => '0.0000001', 'col' => 'col-md-4', 'help' => 'e.g. 10.5220. Shows the farm on the GIS Map.', 'min' => -90, 'max_value' => 90],
                    'longitude'    => ['label' => 'Longitude', 'type' => 'number', 'step' => '0.0000001', 'col' => 'col-md-4', 'help' => 'e.g. 122.8070', 'min' => -180, 'max_value' => 180],
                    'remarks'      => ['label' => 'Remarks', 'type' => 'textarea', 'col' => 'col-12'],
                ],
                'sections' => ['farm_name' => 'Farm details', 'latitude' => 'Map location', 'remarks' => 'Notes'],
                'list'    => ['farm_name', 'profile_id', 'barangay_id', 'area_ha', 'commodity', 'water_source', 'computed:mapped'],
                'search'  => ['farm_name', 'profile_id'],
                'filters' => ['barangay_id', 'commodity', 'water_source'],
                'order'   => 'farm_name ASC',
            ],

            'fishery' => [
                'table' => 'fishery_assets', 'label' => 'Fishery', 'singular' => 'Fishery Asset', 'icon' => 'water', 'sector' => 'fisheries',
                'fields' => [
                    'asset_type'      => ['label' => 'Asset type', 'type' => 'select', 'required' => true, 'options' => ['boat' => 'Boat', 'gear' => 'Fishing gear', 'fishpond' => 'Fishpond', 'aquaculture' => 'Aquaculture unit'], 'col' => 'col-md-4'],
                    'name'            => ['label' => 'Name / description', 'type' => 'text', 'required' => true, 'max' => 120, 'col' => 'col-md-4', 'help' => 'Boat name, gear type or pond name.'],
                    'registration_no' => ['label' => 'Registration no.', 'type' => 'text', 'max' => 40, 'col' => 'col-md-4', 'help' => 'BFAR / MARINA number, if any.'],
                    'profile_id'      => ['label' => 'Owner / operator', 'type' => 'profile', 'required' => true, 'col' => 'col-md-6'],
                    'barangay_id'     => ['label' => 'Barangay', 'type' => 'barangay', 'required' => true, 'col' => 'col-md-6'],
                    'size_capacity'   => ['label' => 'Size / capacity', 'type' => 'text', 'max' => 60, 'col' => 'col-md-4', 'help' => 'e.g. 3 GT, 28 ft, 500 m net.'],
                    'area_ha'         => ['label' => 'Area (ha)', 'type' => 'number', 'step' => '0.0001', 'col' => 'col-md-4', 'help' => 'Fishponds and aquaculture only.'],
                    'species'         => ['label' => 'Species', 'type' => 'select', 'options' => $fishSpecies, 'col' => 'col-md-4'],
                    'status'          => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['operational' => 'Operational', 'idle' => 'Idle', 'damaged' => 'Damaged', 'retired' => 'Retired'], 'col' => 'col-md-4', 'default' => 'operational'],
                    'latitude'        => ['label' => 'Latitude', 'type' => 'number', 'step' => '0.0000001', 'col' => 'col-md-4', 'min' => -90, 'max_value' => 90],
                    'longitude'       => ['label' => 'Longitude', 'type' => 'number', 'step' => '0.0000001', 'col' => 'col-md-4', 'min' => -180, 'max_value' => 180],
                    'remarks'         => ['label' => 'Remarks', 'type' => 'textarea', 'col' => 'col-12'],
                ],
                'sections' => ['asset_type' => 'Asset details', 'profile_id' => 'Owner and location', 'size_capacity' => 'Specifications', 'latitude' => 'Map location', 'remarks' => 'Notes'],
                'list'    => ['name', 'asset_type', 'profile_id', 'barangay_id', 'size_capacity', 'status', 'computed:mapped'],
                'search'  => ['name', 'registration_no', 'profile_id'],
                'filters' => ['barangay_id', 'asset_type', 'status'],
                'order'   => 'name ASC',
            ],

            'livestock' => [
                'table' => 'livestock_records', 'label' => 'Livestock', 'singular' => 'Livestock Record', 'icon' => 'egg', 'sector' => 'livestock_poultry',
                'fields' => [
                    'profile_id'  => ['label' => 'Raiser', 'type' => 'profile', 'required' => true, 'col' => 'col-md-6'],
                    'barangay_id' => ['label' => 'Barangay', 'type' => 'barangay', 'required' => true, 'col' => 'col-md-6'],
                    'category'    => ['label' => 'Category', 'type' => 'select', 'required' => true, 'options' => ['livestock' => 'Livestock', 'poultry' => 'Poultry'], 'col' => 'col-md-4'],
                    'species'     => ['label' => 'Species', 'type' => 'select', 'required' => true, 'options' => [
                        'carabao' => 'Carabao', 'cattle' => 'Cattle', 'goat' => 'Goat', 'sheep' => 'Sheep', 'swine' => 'Swine', 'horse' => 'Horse',
                        'chicken' => 'Chicken', 'duck' => 'Duck', 'quail' => 'Quail', 'turkey' => 'Turkey', 'others' => 'Others'], 'col' => 'col-md-4'],
                    'head_count'  => ['label' => 'Number of heads', 'type' => 'integer', 'required' => true, 'col' => 'col-md-4'],
                    'purpose'     => ['label' => 'Purpose', 'type' => 'select', 'options' => [
                        'breeding' => 'Breeding', 'fattening' => 'Fattening', 'draft' => 'Draft', 'dairy' => 'Dairy',
                        'layer' => 'Layer (eggs)', 'broiler' => 'Broiler (meat)', 'others' => 'Others'], 'col' => 'col-md-4'],
                    'remarks'     => ['label' => 'Remarks', 'type' => 'textarea', 'col' => 'col-12'],
                ],
                'sections' => ['profile_id' => 'Raiser', 'category' => 'Animals', 'remarks' => 'Notes'],
                'list'    => ['profile_id', 'barangay_id', 'category', 'species', 'head_count', 'purpose'],
                'search'  => ['profile_id'],
                'filters' => ['barangay_id', 'category', 'species'],
                'order'   => 'id DESC',
            ],

            'equipment' => [
                'table' => 'equipment', 'label' => 'Equipment', 'singular' => 'Equipment', 'icon' => 'tools', 'sector' => null,
                'fields' => [
                    'equipment_type'  => ['label' => 'Equipment type', 'type' => 'select', 'required' => true, 'options' => [
                        'hand_tractor' => 'Hand tractor', 'four_wheel_tractor' => '4-wheel tractor', 'thresher' => 'Thresher',
                        'combine_harvester' => 'Combine harvester', 'rice_transplanter' => 'Rice transplanter', 'water_pump' => 'Water pump',
                        'dryer' => 'Mechanical dryer', 'rice_mill' => 'Rice mill', 'sprayer' => 'Power sprayer', 'others' => 'Others'], 'col' => 'col-md-4'],
                    'description'     => ['label' => 'Brand / model / serial', 'type' => 'text', 'max' => 150, 'col' => 'col-md-5'],
                    'quantity'        => ['label' => 'Quantity', 'type' => 'integer', 'required' => true, 'col' => 'col-md-3', 'default' => '1'],
                    'item_condition'  => ['label' => 'Condition', 'type' => 'select', 'required' => true, 'options' => [
                        'good' => 'Good', 'fair' => 'Fair', 'needs_repair' => 'Needs repair', 'unserviceable' => 'Unserviceable'], 'col' => 'col-md-4', 'default' => 'good'],
                    'ownership'       => ['label' => 'Held by', 'type' => 'select', 'required' => true, 'options' => [
                        'individual' => 'Individual farmer', 'organization' => 'Organization', 'lgu' => 'LGU / MAO'], 'col' => 'col-md-4'],
                    'barangay_id'     => ['label' => 'Barangay', 'type' => 'barangay', 'required' => true, 'col' => 'col-md-4'],
                    'profile_id'      => ['label' => 'Farmer (if individual)', 'type' => 'profile', 'col' => 'col-md-6'],
                    'organization_id' => ['label' => 'Organization (if organization)', 'type' => 'organization', 'col' => 'col-md-6'],
                    'date_acquired'   => ['label' => 'Date acquired', 'type' => 'date', 'not_future' => true, 'col' => 'col-md-4'],
                    'source'          => ['label' => 'Source / program', 'type' => 'text', 'max' => 120, 'col' => 'col-md-8'],
                    'remarks'         => ['label' => 'Remarks', 'type' => 'textarea', 'col' => 'col-12'],
                ],
                'sections' => ['equipment_type' => 'Equipment', 'ownership' => 'Held by', 'date_acquired' => 'Acquisition', 'remarks' => 'Notes'],
                'list'    => ['equipment_type', 'description', 'quantity', 'item_condition', 'computed:holder', 'barangay_id'],
                'search'  => ['description', 'source', 'profile_id'],
                'filters' => ['barangay_id', 'equipment_type', 'item_condition'],
                'order'   => 'id DESC',
            ],

            'organizations' => [
                'table' => 'organizations', 'label' => 'Organizations', 'singular' => 'Organization', 'icon' => 'building', 'sector' => null,
                'fields' => [
                    'name'               => ['label' => 'Organization name', 'type' => 'text', 'required' => true, 'max' => 150, 'col' => 'col-md-8'],
                    'org_type'           => ['label' => 'Type', 'type' => 'select', 'required' => true, 'options' => [
                        'association' => 'Farmers association', 'cooperative' => 'Cooperative', 'irrigators' => 'Irrigators association',
                        'fisherfolk' => 'Fisherfolk association', 'rural_women' => 'Rural women group', 'youth' => 'Youth (4-H) club'], 'col' => 'col-md-4'],
                    'barangay_id'        => ['label' => 'Barangay', 'type' => 'barangay', 'col' => 'col-md-4'],
                    'registration_no'    => ['label' => 'Registration no.', 'type' => 'text', 'max' => 60, 'col' => 'col-md-4'],
                    'registering_agency' => ['label' => 'Registered with', 'type' => 'select', 'options' => ['CDA' => 'CDA', 'DOLE' => 'DOLE', 'SEC' => 'SEC', 'DA' => 'DA', 'LGU' => 'LGU'], 'col' => 'col-md-4'],
                    'date_established'   => ['label' => 'Date established', 'type' => 'date', 'not_future' => true, 'col' => 'col-md-4'],
                    'contact_no'         => ['label' => 'Contact number', 'type' => 'text', 'max' => 20, 'col' => 'col-md-4'],
                    'status'             => ['label' => 'Status', 'type' => 'select', 'required' => true, 'options' => ['active' => 'Active', 'inactive' => 'Inactive', 'dissolved' => 'Dissolved'], 'col' => 'col-md-4', 'default' => 'active'],
                    'members'            => ['label' => 'Members', 'type' => 'members', 'pivot' => ['table' => 'organization_members', 'fk' => 'organization_id'], 'col' => 'col-12',
                                             'help' => 'Pick from registered profiles. Hold Ctrl (Cmd on Mac) to select more than one.'],
                    'remarks'            => ['label' => 'Remarks', 'type' => 'textarea', 'col' => 'col-12'],
                ],
                'sections' => ['name' => 'Organization', 'registration_no' => 'Registration and contact', 'members' => 'Members', 'remarks' => 'Notes'],
                'list'    => ['name', 'org_type', 'barangay_id', 'registering_agency', 'members', 'status'],
                'search'  => ['name', 'registration_no'],
                'filters' => ['barangay_id', 'org_type', 'status'],
                'order'   => 'name ASC',
            ],
        ],
    ],

    // ======================================================================
    // 4.0 Monitor Agricultural Activities (D4)
    // ======================================================================
    'monitoring' => [
        'title'    => 'Monitoring',
        'subtitle' => 'Production, damage and pest incidents, and trainings, linked to registered profiles and farms.',
        'path'     => '/monitoring',
        'overview' => true,   // first tab: summary of all monitoring records
        'tabs'     => [

            'production' => [
                'table' => 'production_records', 'label' => 'Production', 'singular' => 'Production Record', 'icon' => 'graph-up-arrow', 'sector' => 'crops',
                'fields' => [
                    'profile_id'        => ['label' => 'Farmer', 'type' => 'profile', 'required' => true, 'col' => 'col-md-6'],
                    'farm_id'           => ['label' => 'Farm / parcel', 'type' => 'farm', 'col' => 'col-md-6'],
                    'barangay_id'       => ['label' => 'Barangay', 'type' => 'barangay', 'required' => true, 'col' => 'col-md-4'],
                    'commodity'         => ['label' => 'Commodity', 'type' => 'select', 'required' => true, 'options' => $commodities, 'col' => 'col-md-4'],
                    'season'            => ['label' => 'Cropping season', 'type' => 'select', 'required' => true, 'options' => ['wet' => 'Wet season', 'dry' => 'Dry season'], 'col' => 'col-md-4'],
                    'period_month'      => ['label' => 'Reporting month', 'type' => 'month', 'required' => true, 'col' => 'col-md-4'],
                    'period_half'       => ['label' => 'Reporting period', 'type' => 'select', 'required' => true, 'options' => ['1' => '1st half (1-15)', '2' => '2nd half (16-end)'], 'col' => 'col-md-4'],
                    'date_monitored'    => ['label' => 'Date monitored', 'type' => 'date', 'required' => true, 'not_future' => true, 'col' => 'col-md-4'],
                    'area_planted_ha'   => ['label' => 'Area planted (ha)', 'type' => 'number', 'step' => '0.0001', 'col' => 'col-md-3'],
                    'area_harvested_ha' => ['label' => 'Area harvested (ha)', 'type' => 'number', 'step' => '0.0001', 'col' => 'col-md-3'],
                    'volume'            => ['label' => 'Volume produced', 'type' => 'number', 'step' => '0.01', 'col' => 'col-md-3'],
                    'unit'              => ['label' => 'Unit', 'type' => 'select', 'options' => ['mt' => 'Metric tons', 'kg' => 'Kilograms', 'cavans' => 'Cavans', 'sacks' => 'Sacks'], 'col' => 'col-md-3'],
                    'farm_gate_price'   => ['label' => 'Farm gate price (PHP per unit)', 'type' => 'number', 'step' => '0.01', 'col' => 'col-md-4'],
                    'monitored_by'      => ['label' => 'Monitored by', 'type' => 'text', 'max' => 120, 'col' => 'col-md-8'],
                    'remarks'           => ['label' => 'Remarks', 'type' => 'textarea', 'col' => 'col-12'],
                ],
                'sections' => ['profile_id' => 'Farmer and farm', 'commodity' => 'Crop and reporting period', 'area_planted_ha' => 'Harvest', 'monitored_by' => 'Monitoring'],
                'list'    => ['computed:period', 'profile_id', 'barangay_id', 'commodity', 'area_harvested_ha', 'computed:volume', 'computed:yield'],
                'search'  => ['profile_id', 'monitored_by'],
                'filters' => ['barangay_id', 'commodity', 'season'],
                'order'   => 'period_month DESC, period_half DESC, id DESC',
                'yield_preview' => true,   // live "Yield: x per ha" under the form
            ],

            'damage' => [
                'table' => 'damage_reports', 'label' => 'Damage & Pest', 'singular' => 'Damage Report', 'icon' => 'bug', 'sector' => 'crops',
                'fields' => [
                    'date_occurred'    => ['label' => 'Date occurred', 'type' => 'date', 'required' => true, 'not_future' => true, 'col' => 'col-md-4'],
                    'cause_type'       => ['label' => 'Cause', 'type' => 'select', 'required' => true, 'options' => [
                        'pest' => 'Pest', 'disease' => 'Disease', 'typhoon' => 'Typhoon', 'flood' => 'Flood', 'drought' => 'Drought', 'other' => 'Other'], 'col' => 'col-md-4'],
                    'calamity_name'    => ['label' => 'Pest / calamity name', 'type' => 'text', 'max' => 120, 'col' => 'col-md-4', 'help' => 'e.g. Fall armyworm, Typhoon Odette.'],
                    'barangay_id'      => ['label' => 'Barangay', 'type' => 'barangay', 'required' => true, 'col' => 'col-md-4'],
                    'profile_id'       => ['label' => 'Affected farmer', 'type' => 'profile', 'col' => 'col-md-4', 'help' => 'Leave blank for an area-wide report.'],
                    'farm_id'          => ['label' => 'Farm / parcel', 'type' => 'farm', 'col' => 'col-md-4'],
                    'commodity'        => ['label' => 'Commodity', 'type' => 'select', 'required' => true, 'options' => $commodities, 'col' => 'col-md-3'],
                    'affected_area_ha' => ['label' => 'Affected area (ha)', 'type' => 'number', 'step' => '0.0001', 'col' => 'col-md-3'],
                    'estimated_loss'   => ['label' => 'Estimated loss (PHP)', 'type' => 'number', 'step' => '0.01', 'col' => 'col-md-3'],
                    'severity'         => ['label' => 'Severity', 'type' => 'select', 'required' => true, 'options' => ['low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High', 'total' => 'Total loss'], 'col' => 'col-md-3'],
                    'action_taken'     => ['label' => 'Action taken / response', 'type' => 'textarea', 'col' => 'col-md-6'],
                    'remarks'          => ['label' => 'Observations / remarks', 'type' => 'textarea', 'col' => 'col-md-6'],
                ],
                'sections' => ['date_occurred' => 'Incident', 'barangay_id' => 'Affected farm', 'commodity' => 'Extent of damage', 'action_taken' => 'Response and observations'],
                'list'    => ['date_occurred', 'cause_type', 'calamity_name', 'barangay_id', 'commodity', 'affected_area_ha', 'computed:loss', 'severity'],
                'search'  => ['calamity_name', 'profile_id'],
                'filters' => ['barangay_id', 'cause_type', 'severity'],
                'order'   => 'date_occurred DESC, id DESC',
            ],

            'training' => [
                'table' => 'trainings', 'label' => 'Trainings', 'singular' => 'Training / Activity', 'icon' => 'calendar-event', 'sector' => null,
                'fields' => [
                    'title'           => ['label' => 'Title', 'type' => 'text', 'required' => true, 'max' => 150, 'col' => 'col-md-8'],
                    'activity_type'   => ['label' => 'Type', 'type' => 'select', 'required' => true, 'options' => [
                        'training' => 'Training', 'seminar' => 'Seminar', 'technical_assistance' => 'Technical assistance',
                        'field_day' => 'Field day', 'meeting' => 'Meeting'], 'col' => 'col-md-4'],
                    'date_held'       => ['label' => 'Date held', 'type' => 'date', 'required' => true, 'col' => 'col-md-4'],
                    'barangay_id'     => ['label' => 'Barangay', 'type' => 'barangay', 'col' => 'col-md-4'],
                    'venue'           => ['label' => 'Venue', 'type' => 'text', 'max' => 150, 'col' => 'col-md-4'],
                    'topic'           => ['label' => 'Topic', 'type' => 'text', 'max' => 150, 'col' => 'col-md-6'],
                    'resource_person' => ['label' => 'Resource person / personnel', 'type' => 'text', 'max' => 120, 'col' => 'col-md-6'],
                    'participants'    => ['label' => 'Participants', 'type' => 'members', 'pivot' => ['table' => 'training_participants', 'fk' => 'training_id'], 'col' => 'col-12',
                                          'help' => 'Pick from registered profiles. Hold Ctrl (Cmd on Mac) to select more than one.'],
                    'remarks'         => ['label' => 'Remarks', 'type' => 'textarea', 'col' => 'col-12'],
                ],
                'sections' => ['title' => 'Activity details', 'participants' => 'Participants', 'remarks' => 'Notes'],
                'list'    => ['date_held', 'title', 'activity_type', 'barangay_id', 'resource_person', 'participants'],
                'search'  => ['title', 'topic', 'resource_person', 'venue'],
                'filters' => ['barangay_id', 'activity_type'],
                'order'   => 'date_held DESC, id DESC',
            ],
        ],
    ],
];
