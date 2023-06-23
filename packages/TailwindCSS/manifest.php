<?php

$manifest = [
    'acceptable_sugar_flavors' => ['ENT'],
    'acceptable_sugar_versions' => [
        'exact_matches' => [],
        'regex_matches' => ['(.*?)\\.(.*?)\\.(.*?)$'],
    ],
    'author' => 'SugarCRM',
    'description' => 'Copy style files to custom/themes',
    'icon' => '',
    'is_uninstallable' => true,
    'name' => 'TailwindCSS customization guide',
    'published_date' => '2023-06-23 00:00:00',
    'type' => 'module',
    'version' => '',
];

$installdefs = [
    'id' => 'package_...',
    'copy' => [
        0 => [
            'from' => '<basepath>/Files/custom/themes/custom.less',
            'to' => 'custom/themes/custom.less'
        ],
    ]
];