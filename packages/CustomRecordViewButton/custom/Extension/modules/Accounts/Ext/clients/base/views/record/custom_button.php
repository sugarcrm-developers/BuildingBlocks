<?php
/**
 * Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.
 */

//Insert our custom button definition into existing Record View Buttons array for Accounts module before the sidebar toggle button
array_splice($viewdefs['Accounts']['base']['view']['record']['buttons'], -1, 0, array(
    array(
        'name' => 'custom_button',
        'type' => 'button',
        'label' => 'My Custom Button',
        'css_class' => 'btn-link',
        //Set target object for Sidecar Event.
        //By default, button events are triggered on current Context but can optionally set target to 'view' or 'layout'
        //'target' => 'context'
        'events' => array(
            // custom Sidecar Event to trigger on click.  Event name can be anything you want.
            'click' => 'button:custom_button:click',
        )
    ),
));
