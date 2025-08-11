<?php

require_once 'custom/modules/DynamicFields/templates/Fields/TemplateHighlightfield.php';

/**
 * Implement get_body function to correctly populate the template for the ModuleBuilder/Studio
 * Add field page.
 *
 * @param Sugar_Smarty $ss
 * @param array $vardef
 *
 */
function get_body(&$ss, $vardef)
{
    global $app_list_strings, $mod_strings;
    $vars = $ss->get_template_vars();
    $fields = $vars['module']->mbvardefs->vardefs['fields'];
    $fieldOptions = array();
    foreach ($fields as $id => $def) {
        $fieldOptions[$id] = $def['name'];
    }
    $ss->assign('fieldOpts', $fieldOptions);

    //If there are no colors defined, use black text on
    // a white background
    if (isset($vardef['backcolor'])) {
        $backcolor = $vardef['backcolor'];
    } else {
        $backcolor = '#ffffff';
    }
    if (isset($vardef['textcolor'])) {
        $textcolor = $vardef['textcolor'];
    } else {
        $textcolor = '#000000';
    }
    $ss->assign('BACKCOLOR', $backcolor);
    $ss->assign('TEXTCOLOR', $textcolor);

    $colorArray = $app_list_strings['highlightColors'];
    asort($colorArray);

    $ss->assign('highlightColors', $colorArray);
    $ss->assign('textColors', $colorArray);

    $ss->assign('BACKCOLORNAME', $app_list_strings['highlightColors'][$backcolor]);
    $ss->assign('TEXTCOLORNAME', $app_list_strings['highlightColors'][$textcolor]);

    return $ss->fetch('custom/modules/DynamicFields/templates/Fields/Forms/Highlightfield.tpl');
}
