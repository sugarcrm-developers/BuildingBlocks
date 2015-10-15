<?php
// Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

$manifest = array(
    'acceptable_sugar_flavors' => array('PRO','ENT','ULT'),
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(),
        'regex_matches' => array('7\\..*$'),
    ),
    'author' => 'SugarCRM',
    'name' => 'Hello World Dashlet',
    'description' => 'Package for a basic Sugar Dashlet',
    'is_uninstallable' => true,
    'type' => 'module',
    'version' => 1,
);


$installdefs = array(
    'id' => 'BuildingBlock_HelloWorldDashlet',
    'beans' => array(),
    'layoutdefs' => array(),
    'relationships' => array(),
    'copy' => array(
        array(
            'from' => '<basepath>/custom',
            'to' => 'custom',
        ),
    ),
);
