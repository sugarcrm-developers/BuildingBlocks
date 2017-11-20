<?php
/**
 * Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
 */

// Insert our custom panel definition into extra-info section of the Record layout

if(!is_array($viewdefs['Opportunities']['base']['layout']['extra-info']['components'])){
    $viewdefs['Opportunities']['base']['layout']['extra-info']['components'] = array();
}
$viewdefs['Opportunities']['base']['layout']['extra-info']['components'][] =
    array(
        'view' => 'my-panel',
    );
