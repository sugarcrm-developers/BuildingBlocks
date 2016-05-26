<?php


$viewdefs['base']['layout']['iframe-drawer'] = array(
    'type' => 'iframe-drawer',
    'name' => 'base',
    'span' => 12,
    'components' =>
        array(
            array(
                'view' => 'iframe-drawer-header',
            ),
            array(
                'view' => 'iframe-drawer-view',
            ),
        ),
);
