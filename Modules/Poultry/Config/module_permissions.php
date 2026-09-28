<?php

/*
 * Page permissions for the Poultry module.
 *
 * The Role and Permissions screen builds its checkbox list from each module's
 * Config/module_permissions.php. Every navigable GET route in this module has
 * one entry here. Ajax endpoints (/data, /options, /export) are deliberately
 * omitted - they are not screens a permission would sensibly guard.
 *
 * Ordinary configuration, safe to hand edit.
 */

return [
    ['key' => 'poultry.dashboard',        'label' => 'Dashboard',            'type' => 'page', 'source' => 'module_pages'],

    ['key' => 'poultry.batch.view',       'label' => 'Batches - View',       'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.batch.create',     'label' => 'Batches - Place',      'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.batch.edit',       'label' => 'Batches - Edit',       'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.batch.close',      'label' => 'Batches - Close',      'type' => 'page', 'source' => 'module_pages'],

    ['key' => 'poultry.daily.view',       'label' => 'Daily records - View', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.daily.create',     'label' => 'Daily records - Entry','type' => 'page', 'source' => 'module_pages'],

    ['key' => 'poultry.egg.view',         'label' => 'Egg collection - View','type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.egg.create',       'label' => 'Egg collection - Entry','type' => 'page','source' => 'module_pages'],

    ['key' => 'poultry.feed.view',        'label' => 'Feed - View',          'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.feed.create',      'label' => 'Feed - Issue',         'type' => 'page', 'source' => 'module_pages'],

    ['key' => 'poultry.health.view',      'label' => 'Health - View',        'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.health.create',    'label' => 'Health - Record',      'type' => 'page', 'source' => 'module_pages'],

    ['key' => 'poultry.harvest.view',     'label' => 'Harvest - View',       'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.harvest.create',   'label' => 'Harvest - Record',     'type' => 'page', 'source' => 'module_pages'],

    ['key' => 'poultry.hatchery.view',    'label' => 'Hatchery - View',      'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.hatchery.create',  'label' => 'Hatchery - Record',    'type' => 'page', 'source' => 'module_pages'],

    ['key' => 'poultry.master.view',      'label' => 'Masters - View',       'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.master.manage',    'label' => 'Masters - Manage',     'type' => 'page', 'source' => 'module_pages'],

    ['key' => 'poultry.report.view',      'label' => 'Reports',              'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'poultry.settings',         'label' => 'Settings',             'type' => 'page', 'source' => 'module_pages'],
];
