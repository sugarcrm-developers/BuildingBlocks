<?php

array_unshift($viewdefs['Opportunities']['base']['view']['list-headerpane']['buttons'], array(
    'name' => 'custom_button',
    'type' => 'button',
    'label' => 'Go to Canvas IFrame',
    'css_class' => 'btn-link',
    'events' => array(
        'click' => 'button:custom_button:click',
    ),
));
