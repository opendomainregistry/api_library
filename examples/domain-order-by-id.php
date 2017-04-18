<?php
// Require ODR API demo class
require_once '../Api/Odr.php';

// Configuration array, with user API Keys
$config = array(
    'api_key'    => '#API_KEY#',
    'api_secret' => '#API_SECRET#',
);

// Page number you want to request
$page = 1;

// Create new instance of API demo class
$demo = new Api_Odr($config);

// Login into API
$demo->login();

$loginResult = $demo->getResult();

if ($loginResult['status'] === Api_Odr::STATUS_ERROR) {
    echo 'Can\'t login, reason - '. $loginResult['response'];

    exit(1);
}

$parameters = array(
    'o[id]' => 'ASC',
    'page'  => $page,
);

$demo->getDomains($parameters);

// Get result of request
$result = $demo->getResult();

if ($result['status'] !== Api_Odr::STATUS_SUCCESS) {
    echo 'Following error occurred: '. (is_array($result['response']) ? $result['response']['message'] : $result['response']);

    if (!empty($result['response']['data'])) {
        foreach ($result['response']['data'] as $name => $error) {
            echo "\r\n\t{$name}: {$error}";
        }
    }

    exit();
}

foreach ($result['response'] as $domain) {
    echo '[' . $domain['id'] . '] ' . $domain['name'] . '.' . $domain['tld'] . "\r\n";
}

echo "\r\n";

echo 'Total domains: ' . count($result['response']);