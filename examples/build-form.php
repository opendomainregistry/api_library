<?php
// Require ODR API demo class
require_once '../Api/Odr.php';

// Configuration array, with user API Keys
$config = array(
    'api_key'    => '#API_KEY#',
    'api_secret' => '#API_SECRET#',
);

// Create new instance of API demo class
$demo = new Api_Odr($config);

// Login into API
$demo->login();

$loginResult = $demo->getResult();

if ($loginResult['status'] === Api_Odr::STATUS_ERROR) {
    echo 'Can\'t login, reason - '. $loginResult['response'];

    exit(1);
}

if (empty($_REQUEST['__sent'])) {
    $demo->info('/domain/test.eu/', Api_Odr::METHOD_POST);

    $info = $demo->getResult();

    $form = '<form action="" method="post">';

    foreach ($info['response']['fields'] as $name => $field) {
        $form .= '<div style="margin-bottom: 10px;">' . fieldToHtml($name, $field) . '</div>';
    }

    $form .= '<button type="submit" name="__sent">Submit generated form</button>';

    $form .= '</form>';

    echo $form;
} else {
    // Submit the data, it's located in the $_POST variable
}

/**
 * Converts info response to HTML input
 * Notice! This is a bare-bone function, it doesn't wrap generated input or display help or anything
 * It just builds input
 *
 * @param string $name
 * @param array  $field
 *
 * @return string
 */
function fieldToHtml($name, array $field)
{
    $html = '<input type="#TYPE#" name="#NAME#"#REQUIRED##READONLY##CLASSNAME##TITLE#>';

    $r = array(
        '#TYPE#'      => $field['class'] === 'String_Email' ? 'email' : 'text',
        '#NAME#'      => $name,
        '#REQUIRED#'  => empty($field['is_required']) ? '' : ' required',
        '#READONLY#'  => empty($field['is_readonly']) ? '' : ' readonly',
        '#CLASSNAME#' => empty($field['classname']) ? '' : ' class="' . $field['classname'] . '"',
        '#TITLE#'     => empty($field['title']) ? ' placeholder="' . $name . '"' : ' title="' . $field['title'] . '" placeholder="' . $field['title'] . '"',
    );

    if (strpos($field['class'], 'String_Textarea') === 0) {
        $html = '<textarea name="#NAME#"#REQUIRED##READONLY##CLASSNAME##TITLE#></textarea>';
    } elseif ($field['class'] === 'String_Nameserver') {
        $html = '<input type="#TYPE#" name="#NAME#[host]"#REQUIRED##READONLY##CLASSNAME##TITLE#>';
    } elseif (strpos($field['class'], 'Checkbox') === 0) {
        $html = '<label><input type="checkbox" value="1"#REQUIRED##READONLY##CLASSNAME#>' . (!empty($field['title']) ? $field['title'] : $name) . '</label>';
    } elseif (strpos($field['class'], 'Select') === 0) {
        $html = '<select name="#NAME#"#REQUIRED##READONLY##CLASSNAME##TITLE#>#OPTIONS#</select>';

        $r['#OPTIONS#'] = '<option value="">Select...</option>';

        foreach ($field['select_options'] as $optionName => $optionValue) {
            $r['#OPTIONS#'] .= '<option value="'. $optionName .'">' . $optionValue . '</option>';
        }
    }

    $html = str_replace(array_keys($r), array_values($r), $html);

    return $html;
}