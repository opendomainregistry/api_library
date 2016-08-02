<?php
// Require ODR API demo class
require_once '../Api/Odr.php';

// Configuration array, with user API Keys
$config = array(
    'api_key'    => '#API_KEY#',
    'api_secret' => '#API_SECRET#',
);

// Domain name you want to know how to register
$domainName = 'test.nl';

// Create new instance of API demo class
$demo = new Api_Odr($config);

// Login into API
$demo->login();

$loginResult = $demo->getResult();

if ($loginResult['status'] === Api_Odr::STATUS_ERROR) {
    echo 'Can\'t login, reason - '. $loginResult['response'];

    exit(1);
}

// Request information about domain registration
$demo->infoRegisterDomain($domainName);

// Get result of request
$result = $demo->getResult();

if ($result['status'] !== Api_Odr::STATUS_SUCCESS) {
    echo 'Following error occurred: '. $result['response'];

    exit(1);
}

$result = $result['response'];

// Output what we get
print_r($result);