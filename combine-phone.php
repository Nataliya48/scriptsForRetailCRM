<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();
$urlCrm = getenv('URL_CRM');
$apiKey = getenv('API_KEY');

$client = new \RetailCrm\ApiClient(
    $urlCrm,
    $apiKey,
    \RetailCrm\ApiClient::V5
);

try {
    $response = $client->request->customersList(['dateFrom' => '2016-01-01', 'dateTo' => '2017-12-31'], null, 20);
} catch (\RetailCrm\Exception\CurlException $e) {
    echo "Connection error: " . $e->getMessage();
}

if ($response->isSuccessful()) {
    $totalPageCount = $response->pagination['totalPageCount'];

    $customerList = [];
    for ($page = 1; $page <= $totalPageCount; $page++) {
        $responseCustomersList = $client->request->customersList(['dateFrom' => '2016-01-01', 'dateTo' => '2017-12-31'], $page, 20);
        foreach ($responseCustomersList->customers as $customer) {
            foreach ($customer['phones'] as $phone) {
                $customerList[$phone['number']] = $customer;
            }
        }
    }
    file_put_contents('email.log', json_encode(['DATE' => date('Y-m-d H:i:s'), 'customerList' => $customerList], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);

    for ($page = 1; $page <= $totalPageCount; $page++) {
        foreach ($customerList as $key => $custom) {
            $responseCustomersList = $client->request->customersList(['name' => $key], $page, 20);
            foreach ($responseCustomersList->customers as $customer) {
                $responseСustomersCombine = $client->request->customersCombine([$customer], $custom);
                file_put_contents('response.log', json_encode(['DATE' => date('Y-m-d H:i:s'), 'customerId' => $customer['id'], 'response' => [$responseСustomersCombine->getStatusCode(), $responseСustomersCombine->isSuccessful(), isset($responseСustomersCombine['errorMsg']) ? $responseСustomersCombine['errorMsg'] : 'not errors']], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
            }
        }
    }
} else {
    echo sprintf(
        "Error: [HTTP-code %s] %s",
        $response->getStatusCode(),
        $response->getErrorMsg()
    );
    if (isset($response['errors'])) {
        print_r($response['errors']);
    }
}
