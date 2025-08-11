<?php

require 'clients/base/filters/operators/operators.php';

$viewdefs['base']['filter']['operators']['Highlightfield'] = array(
    '$contains' => 'LBL_HIGHLIGHTFIELD_OPERATOR_CONTAINS',
    '$not_contains' => 'LBL_HIGHLIGHTFIELD_OPERATOR_NOT_CONTAINS',
    '$starts' => 'LBL_HIGHLIGHTFIELD_OPERATOR_STARTS_WITH',
);
