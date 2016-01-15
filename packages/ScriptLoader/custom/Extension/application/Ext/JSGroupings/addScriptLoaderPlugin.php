<?php
/**
 * Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
 */

//Loop through the groupings to find grouping file you want to append to
foreach ($js_groupings as $key => $groupings)
{
    foreach  ($groupings as $file => $target)
    {
        //if the target grouping is found
        if ($target == 'include/javascript/sugar_grp7.min.js')
        {
            //append the custom JavaScript file to existing grouping
            $js_groupings[$key]['custom/include/javascript/require.js'] = 'include/javascript/sugar_grp7.min.js';
            $js_groupings[$key]['custom/include/javascript/sugar7/plugins/ScriptLoader.js'] = 'include/javascript/sugar_grp7.min.js';
        }
        break;
    }
}
