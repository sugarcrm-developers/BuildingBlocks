<?php
/*
 * Your installation or use of this SugarCRM file is subject to the applicable
 * terms available at
 * http://support.sugarcrm.com/Resources/Master_Subscription_Agreements/.
 * If you do not agree to all of the applicable terms or do not have the
 * authority to bind the entity as an authorized representative, then do not
 * install or use this SugarCRM file.
 *
 * Copyright (C) SugarCRM Inc. All rights reserved.
 */
foreach ($js_groupings as $key => $groupings) {
    foreach ($groupings as $file => $target) {
        //if the target grouping is found
        if ($target == 'include/javascript/pmse.designer.min.js') {
            //append the custom JavaScript file to existing grouping
            $js_groupings[$key]['custom/include/javascript/pmse/CustomWebhookAction.js'] = 'include/javascript/pmse.designer.min.js';
        }
        break;
    }
}
