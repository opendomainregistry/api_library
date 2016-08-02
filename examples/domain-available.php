<?php
// Require ODR API demo class
require_once '../Api/Odr.php';

// Configuration array, with user API Keys
$config = array(
    'api_key'    => '#API_KEY#',
    'api_secret' => '#API_SECRET#',
);

// Domain name you want to check
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

// Check if target domain is available for registration or not
$demo->checkDomain($domainName);

// Get result of request
$result = $demo->getResult();

if ($result['status'] !== Api_Odr::STATUS_SUCCESS) {
    echo 'Following error occurred: '. $result['response'];

    exit();
}

$result = $result['response'];

if ($result['available'] === true) {
    // Domain is available for registration
    echo 'Domain "'. $domainName .'" is available';

    // Do something with available domain

    exit(1);
}

// D'oh, someone already took this domain!
echo 'Domain "'. $domainName .'" is not available';