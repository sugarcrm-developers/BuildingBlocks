<?php
$viewdefs['pmse_Inbox']['base']['view']['process-management-dashlet'] = [
    'template' => 'process-management-dashlet',
    'dashlets' => [
        [
            'label' => 'LBL_PROCESS_MANAGEMENT_DASHLET_TITLE',
            'description' => 'LBL_PROCESS_MANAGEMENT_DASHLET_DESCRIPTION',
            'config' => [
                'module' => 'pmse_Inbox',
                'label' => 'LBL_PROCESS_MANAGEMENT_DASHLET',
            ],
            'preview' => [
                'module' => 'pmse_Inbox',
                'label' => 'LBL_PROCESS_MANAGEMENT_DASHLET',
            ],
            'filter' => [
                'view' => 'record',
            ],
        ],
    ],
    'panels' => [
        [
            'name' => 'panel_body',
            'columns' => 2,
            'labelsOnTop' => true,
            'placeholders' => true,
            'fields' => [
                [
                    'name' => 'display_columns',
                    'label' => 'LBL_COLUMNS',
                    'type' => 'enum',
                    'isMultiSelect' => true,
                    'ordered' => true,
                    'span' => 12,
                    'hasBlank' => false,
                ], [
                    'name' => 'limit',
                    'label' => 'LBL_DASHLET_CONFIGURE_DISPLAY_ROWS',
                    'type' => 'enum',
                    'options' => 'dashlet_limit_options',
                ]
            ],
        ],
    ],
    'fields' => [
        [
            'name' => 'cas_id',
            'label' => 'LBL_CAS_ID',
            'default' => true,
            'enabled' => true,
            'link' => false,
        ],
        [
            'name' => 'pro_title',
            'label' => 'LBL_PROCESS_DEFINITION_NAME',
            'type' => 'pmse-link',
            'default' => true,
            'enabled' => true,
            'link' => true,
        ],
        [
            'name' => 'cas_status',
            'label' => 'LBL_STATUS',
            'type' => 'event-status-pmse',
            'enabled' => true,
            'default' => true,
        ],
        [
            'name' => 'prj_run_order',
            'label' => 'LBL_PROJECT_RUN_ORDER',
            'default' => true,
            'enabled' => true,
        ],
        [
            'label' => 'LBL_DATE_ENTERED',
            'enabled' => true,
            'default' => true,
            'name' => 'cas_create_date',
            'readonly' => true,
        ],
        [
            'label' => 'LBL_OWNER',
            'enabled' => true,
            'default' => true,
            'name' => 'assigned_user_full_name',
            'readonly' => true,
            'link' => false,
        ],
        [
            'name' => 'cas_user_id_full_name',
            'label' => 'LBL_ACTIVITY_OWNER',
            'default' => true,
            'enabled' => true,
            'link' => false,
        ],
        [
            'name' => 'prj_user_id_full_name',
            'label' => 'LBL_PROCESS_OWNER',
            'default' => true,
            'enabled' => true,
            'link' => false,
        ],
        [
            'name' => 'date_entered',
            'label' => 'LBL_DATE_ENTERED'
        ],
        [
            'name' => 'date_modified',
            'label' => 'LBL_DATE_MODIFIED'
        ]
    ],
    'orderBy' => [
        'field' => 'cas_status',
        'direction' => 'desc',
    ],
    'rowactions' => [
        'actions' => [
            [
                'type' => 'cancelcasebutton',
                'name' => 'cancelButton',
                'label' => 'Cancel Process',
                'icon' => 'sicon-remove',
                'event' => 'list:cancelCase:fire',
                'css_class'=>'overflow-visible',
                'tooltip'=> 'Cancel Process',
            ], [
                'type' => 'rowaction',
                'label' => 'Preview Chart',
                'icon' => 'sicon-nodes',
                'event' => 'case:preview:chart',
                'css_class'=>'overflow-visible',
                'tooltip'=> 'Preview Chart',
            ], [
                'type' => 'rowaction',
                'name' => 'History',
                'icon' => 'sicon-message',
                'label' => 'LBL_PMSE_LABEL_HISTORY',
                'event' => 'case:history',
                'css_class'=>'overflow-visible',
                'tooltip'=> 'History',
            ], [
                'type' => 'rowaction',
                'name' => 'viewNotes',
                'icon' => 'sicon-document',
                'label' => 'LBL_PMSE_LABEL_NOTES',
                'event' => 'case:notes',
                'css_class'=>'overflow-visible',
                'tooltip'=> 'Notes',

            ], [
                'type' => 'rowaction',
                'label' => 'ListView Preview',
                'icon' => 'sicon-preview',
                'event' => 'list:preview:fire',
                'css_class'=>'overflow-visible',
                'tooltip'=> 'Preview',
                'hideOnFocusDrawer' => true
            ],
        ],
        'css_class'=>'overflow-visible actionmenu',
    ],
];