<?php
// Require ODR API demo class
require_once '../Api/Odr.php';

// Configuration array, with user API Keys
$config = array(
    'api_key'    => '#API_KEY#',
    'api_secret' => '#API_SECRET#',
);

// ID of billing plan you want to know how to register
// If ID = NULL, current user's billing plan ID will be used
$billingPlanId = null;

// Create new instance of API demo class
$demo = new Api_Odr($config);

// Login into API
$demo->login();

$loginResult = $demo->getResult();

if ($loginResult['status'] === Api_Odr::STATUS_ERROR) {
    echo 'Can\'t login, reason - '. $loginResult['response'];

    exit(1);
}

if ($billingPlanId === null) {
    $demo->custom('/user');

    $user = $demo->getResult();

    $billingPlanId = $user['response']['billing_plan_id'];
}

if ($billingPlanId === null) {
    echo 'Unknown billing plan ID, please contact administration team';

    exit(1);
}

// Request information about domain registration
$demo->custom('/billing-plan/' . $billingPlanId . '/items/');

// Get result of request
$result = $demo->getResult();

if ($result['status'] !== Api_Odr::STATUS_SUCCESS) {
    echo 'Following error occurred: '. (is_array($result['response']) ? $result['response']['message'] : $result['response']);

    if (!empty($result['response']['data'])) {
        foreach ($result['response']['data'] as $name => $error) {
            echo "\r\n\t{$name}: {$error}";
        }
    }

    exit(1);
}

$result = $result['response'];

$registerPrices = array();

foreach ($result['items'] as $tld => $ops) {
    if (empty($ops['create_domain'])) {
        continue;
    }

    echo 'Price for .' . $tld . ' - ' . $ops['create_domain'] . '' . PHP_EOL; // This is just a demonstration

    $registerPrices[$tld] = $ops['create_domain'];
}

// $registerPrices is key-value, where key is a TLD and value is a price for registering domain within TLD
print_r($registerPrices);