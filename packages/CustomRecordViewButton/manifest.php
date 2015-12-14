<?php
// Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

$manifest = array(
    'acceptable_sugar_flavors' => array('PRO','ENT','ULT'),
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(),
        'regex_matches' => array('7\\..*$'),
    ),
    'author' => 'SugarCRM',
    'name' => 'Custom Button on the Record View',
    'description' => 'Package that shows how you can add a custom Button to a module\'s Record View using Extensions Framework.',
    'is_uninstallable' => true,
    'type' => 'module',
    'version' => 1,
);


$installdefs = array(
    //You should use a unique value here for each package
    'id' => 'BuildingBlock_CustomRecordViewButton',
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
