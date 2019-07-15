<?php
// Require ODR API demo class
require_once '../Api/Odr.php';

// Configuration array, with user API Keys
$config = array(
    'api_key'    => '#API_KEY#',
    'api_secret' => '#API_SECRET#',
);

$configDomainId = 12345;

// Create new instance of API demo class
$demo = new Api_Odr($config);

// Login into API
$demo->login();

$loginResult = $demo->getResult();

if ($loginResult['status'] === Api_Odr::STATUS_ERROR) {
    echo 'Can\'t login, reason - ' . $loginResult['response'];

    exit(1);
}

$updateArray = [
    'records' => [
        [
            'name' => 'test',
            'type' => 'A',
            'content' => '123.123.123.123',
        ],
        [
            'type' => 'SRV',
            'service' => '_sip',
            'domain' => '@',
            'protocol' => 'tcp',
            'priority' => '100',
            'weight' => '1',
            'port' => '5061',
            'target' => 'sipfed.online.lync.com.',
        ]
    ]
];


// Update existing dns, by passing request data
$demo->updateDNS($configDomainId, $updateArray);

// Get result of request
$result = $demo->getResult();


if ($result['status'] !== Api_Odr::STATUS_SUCCESS) {
    echo 'Following error occurred: ' . (is_array($result['response']) ? $result['response']['message'] : $result['response']);

    if (!empty($result['response']['data'])) {
        foreach ($result['response']['data'] as $name => $error) {
            echo "\r\n\t{$name}: {$error}";
        }
    }

    exit(1);
}

$result = $result['response'];

// DNS successfully updated, sploosh!
echo 'DNS "' . $configDomainId . '" updated';