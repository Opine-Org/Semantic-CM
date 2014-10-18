<?php
return function ($args, $named) {
    $markup = $args[0];
    $required = false;
    if (in_array('required', $named)) {
        $required = true;
        $args = array_diff($args, array('required'));
    }
    $label = false;
    $labeled = '';
    if (in_array('label', $named)) {
        $label = $named['label'];
        $labeled = ' labeled ';
    }
    if (!isset($named['field'])) {
        $named['field'] = '';
    }

    return '
        <div class="field" data-field="' . $named['field'] . '">' .
            ($label !== false ? '<label>' . $label . '</label>' : '') .
            '<div class="ui left ' . $labeled . ' input">' .
                $markup .
                ($required !== false ? '<div class="ui corner label"><i class="icon asterisk"></i></div>' : '') . 
            '</div>
        </div>';
};