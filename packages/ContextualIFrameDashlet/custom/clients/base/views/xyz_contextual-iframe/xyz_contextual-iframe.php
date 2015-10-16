<?php
/**
 * Copyright 2015 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license. 
 */
$viewdefs['base']['view']['xyz_contextual-iframe'] = array(
    //Dashlet metadata
    'dashlets' => array(
        array(
            'label' => 'LBL_XYZ_IFRAME_DASHLET_LABEL',
            'description' => 'LBL_XYZ_IFRAME_DASHLET_DESCRIPTION',
            'config' => array(
                // Default config values
                'url' => '//httpbin.org/get',
                'frameHeight' => '100%',
            ),
            'preview' => array(),
            'filter' => array(),
        ),
    ),
    //View metadata for Dashlet Config page
    'config' => array(
        'fields' => array(
            array(
                'name' => 'url',
                'label' => 'LBL_XYZ_IFRAME_DASHLET_URL_LABEL',
                'type' => 'text',
            ),
            array(
                'name' => 'frameHeight',
                'label' => 'LBL_XYZ_IFRAME_DASHLET_IFRAME_HEIGHT_LABEL',
                'type' => 'text',
            ),
        ),
    ),
);
