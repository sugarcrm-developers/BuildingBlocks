<?php
// Copyright 2020 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

//  Append new View to SidebarNav layout's list of components
$viewdefs['base']['layout']['sidebar-nav']['components'][] = array (
    'view' => [
        'name' => 'click-to-call',
        'type' => 'click-to-call',
        'icon' => 'sicon-bell-lg',
        'label' => 'Click to Call',
        'template' => 'sidebar-nav-item',
    ],
);
