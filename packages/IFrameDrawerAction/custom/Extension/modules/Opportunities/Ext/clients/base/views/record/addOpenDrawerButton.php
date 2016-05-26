<?php
/**
 * Copyright 2016 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
 */

//Insert our custom button definition into existing Record View Buttons array for Accounts module before the sidebar toggle button
array_unshift($viewdefs['Opportunities']['base']['view']['record']['buttons'],
    array(
        'name' => 'custom_button',
        'type' => 'button',
        'label' => 'Open Drawer',
        'css_class' => 'btn-primary',
        //Set target object for Sidecar Event.
        //By default, button events are triggered on current Context but can optionally set target to 'view' or 'layout'
        //'target' => 'context'
        'events' => array(
            // custom Sidecar Event to trigger on click.  Event name can be anything you want.
            'click' => 'button:open_drawer:click',
        )
    )
);
