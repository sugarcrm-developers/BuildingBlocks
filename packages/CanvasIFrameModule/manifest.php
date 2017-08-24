<?php
// Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

$manifest = array(
    'acceptable_sugar_flavors' => array('PRO','ENT','ULT'),
    'acceptable_sugar_versions' => array(
        'exact_matches' => array(),
        'regex_matches' => array('7\\..*$'),
    ),
    'author' => 'SugarCRM',
    'name' => 'Canvas iFrame Module Example',
    'description' => 'Canvas iFrame Module Example',
    'is_uninstallable' => true,
    'type' => 'module',
    'version' => 1,
    'remove_tables' => 'prompt',
);


$installdefs = array(
  //You should use a unique value here for each package
  'id' => 'BuildingBlock_CanvasIFrameModule',
  'beans' => array (
      array (
        'module' => 'test_Test',
        'class' => 'test_Test',
        'path' => 'modules/test_Test/test_Test.php',
        'tab' => true,
      ),
  ),
  'layoutdefs' => array (),
  'relationships' => array (),
  'image_dir' => '<basepath>/icons',
  'copy' => array (
      array (
        'from' => '<basepath>/SugarModules/modules/test_Test',
        'to' => 'modules/test_Test',
      ),
      array (
          'from' => '<basepath>/custom',
          'to' => 'custom',
      ),
    ),
  'language' => array (
      array (
        'from' => '<basepath>/SugarModules/language/application/en_us.lang.php',
        'to_module' => 'application',
        'language' => 'en_us',
      ),
  ),
   'post_execute' => array(
       '<basepath>/scripts/cleanup.php',
   ),
   'post_uninstall' => array(
       '<basepath>/scripts/cleanup.php',
   ),
);
