<?php
/**
 * Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
 */
if (! defined('sugarEntry') || ! sugarEntry) die('Not A Valid Entry Point');
require_once("modules/Administration/QuickRepairAndRebuild.php");
$randc = new RepairAndClear();
//Rebuild extensions then clear include/javascript files
$randc->repairAndClearAll(array('clearAll'), array(translate('LBL_ALL_MODULES')), true, false, '');