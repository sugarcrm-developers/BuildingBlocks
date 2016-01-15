<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

$manifest = array(
    'acceptable_sugar_flavors' => array('PRO','ENT','ULT'),
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(),
        'regex_matches' => array('7\\..*$'),
    ),
    'author' => 'SugarCRM',
    'name' => 'Script Loader',
    'description' => 'Loading custom JavaScript into Sugar made easy.',
    'is_uninstallable' => true,
    'type' => 'module',
    'version' => 1,
);


$installdefs = array(
    //You should use a unique value here for each package
    'id' => 'BuildingBlock_ScriptLoader',
    'beans' => array(),
    'layoutdefs' => array(),
    'relationships' => array(),
    'copy' => array(
        array(
            'from' => '<basepath>/custom',
            'to' => 'custom',
        ),
    ),
    'post_execute' => array(
        '<basepath>/scripts/cleanup.php',
    ),
    'post_uninstall' => array(
        '<basepath>/scripts/cleanup.php',
    ),
);
