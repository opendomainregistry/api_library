<?php
// Require ODR API demo class
require_once '../Api/Odr.php';

// Configuration array, with user API Keys
$config = array(
    'api_key'    => '#API_KEY#',
    'api_secret' => '#API_SECRET#',
);

// Either domain ID or domain name you want to get auth code for
$domainId = 'test.nl';

if (is_numeric($domainId) && $domainId <= 0) {
    throw new Api_Odr_Exception('Domain ID must be a numeric and bigger than zero');
}

// Create new instance of API demo class
$demo = new Api_Odr($config);

// Login into API
$demo->login();

$loginResult = $demo->getResult();

if ($loginResult['status'] === Api_Odr::STATUS_ERROR) {
    echo 'Can\'t login, reason - '. $loginResult['response'];

    exit(1);
}

$demo->custom('/domain/auth-code/'. $domainId .'/', Api_Odr::METHOD_GET);

// Get result of request
$result = $demo->getResult();

if ($result['status'] !== Api_Odr::STATUS_SUCCESS) {
    echo 'Following error occurred: '. $result['response'];

    exit();
}

$result = $result['response'];

if (!empty($result['auth_code'])) {
    echo 'Auth code for domain is "'. $result['auth_code'] .'"';

    // Do something with auth code

    exit(1);
}

echo 'No auth code received';