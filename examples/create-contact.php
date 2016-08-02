<?php
// Require ODR API demo class
require_once '../Api/Odr.php';

// Configuration array, with user API Keys
$config = array(
    'api_key'    => '#API_KEY#',
    'api_secret' => '#API_SECRET#',
);

// We assume that user already sent all the data to us through request
$data = $_REQUEST;

// Create new instance of API demo class
$demo = new Api_Odr($config);

// Login into API
$demo->login();

$loginResult = $demo->getResult();

if ($loginResult['status'] === Api_Odr::STATUS_ERROR) {
    echo 'Can\'t login, reason - '. $loginResult['response'];

    exit(1);
}

// Create new unified contact, by passing request data
$demo->createContact($data);

// Get result of request
$result = $demo->getResult();

if ($result['status'] !== Api_Odr::STATUS_SUCCESS) {
    echo 'Following error occurred: '. $result['response'];

    if (!empty($result['data'])) {
        foreach ($result['data'] as $name => $error) {
            echo "\r\n\t{$name}: {$error}";
        }
    }

    exit(1);
}

$result = $result['response'];

// Contact successfully created, yay!
echo 'Contact "'. $result['full_name'] .'" created';