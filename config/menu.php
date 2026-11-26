<?php
/**
 * Sidebar: one link per module, record types are tabs inside each page
 * (checklist: "Resources, GIS, Monitoring, and Programs/Assistance may each be
 * implemented as one page with tabs or filters").
 *
 * 'link' => ['type','label','path','icon', 'match'?, 'roles'?, 'section'?]
 *   match   extra path prefixes that keep this link highlighted (sub-pages)
 *   roles   only these roles see it
 *   section small heading printed above the entry
 * Forms, edit and detail pages are NOT listed here; they open from the pages.
 */
return [
    ['type' => 'link', 'label' => 'Dashboard',  'path' => '/home',       'icon' => 'grid-1x2'],
    // 1.0 Registry: list + Register / View / Edit / Delete modals
    ['type' => 'link', 'label' => 'Registry',   'path' => '/farmers',    'icon' => 'people',  'match' => ['/profiles']],
    // 2.0 Tabs: Farms, Fishery, Livestock, Equipment, Organizations
    ['type' => 'link', 'label' => 'Resources',  'path' => '/resources',  'icon' => 'basket'],
    // 3.0 One map with layers and filters
    ['type' => 'link', 'label' => 'GIS Map',    'path' => '/map',        'icon' => 'geo-alt'],
    // 4.0 Tabs: Overview, Production, Damage & Pest, Trainings
    ['type' => 'link', 'label' => 'Monitoring', 'path' => '/monitoring', 'icon' => 'activity'],
    // 5.0 Tabs: Programs, Distribution, History (+ program detail pages)
    ['type' => 'link', 'label' => 'Programs',   'path' => '/programs',   'icon' => 'hand-thumbs-up', 'match' => ['/assistance', '/beneficiaries']],
    // 6.0 Reports Center + one report template
    ['type' => 'link', 'label' => 'Reports',    'path' => '/reports',    'icon' => 'bar-chart-line'],
    // System: Users, Access, Audit Log, Backup, Settings (Agriculturist only)
    ['type' => 'link', 'section' => 'Other', 'label' => 'System', 'path' => '/system', 'icon' => 'gear', 'roles' => ['agriculturist']],
];
