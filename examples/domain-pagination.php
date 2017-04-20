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

// Get headers result of request
$headers = $demo->getResultHeaders();

$info = <<<INFO
First: {$headers['x-pagination-first']}
Prev:  {$headers['x-pagination-prev']}
Next:  {$headers['x-pagination-next']}
Last:  {$headers['x-pagination-last']}

This Page:      {$headers['x-pagination-currentpage']}
Items Per Page: {$headers['x-pagination-perpage']}
Total Pages:    {$headers['x-pagination-totalpages']}
INFO;

echo $info;