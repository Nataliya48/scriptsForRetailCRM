<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::create(__DIR__);
$dotenv->load();
$urlCrm = getenv('URL_CRM');
$apiKey = getenv('API_KEY');

$client = new \RetailCrm\ApiClient(
    $urlCrm,
    $apiKey,
    \RetailCrm\ApiClient::V5
);

try {
    $response = $client->request->customersList([], null, 20);
} catch (\RetailCrm\Exception\CurlException $e) {
    echo "Connection error: " . $e->getMessage();
}

if ($response->isSuccessful()) {
    $totalPageCount = $response->pagination['totalPageCount'];
    $emailList = [];
    for ($page = 1; $page <= $totalPageCount; $page++) {
        $responseCustomersList = $client->request->customersList([], $page, 20);
        foreach ($responseCustomersList->customers as $customer) {
            if (!empty($customer['email'])) {
                $emailList[] = $customer['email'];
            }
            if (!empty($customer['phones'])) {
                foreach ($customer['phones'] as $phone) {
                    $phoneList[] = $phone['number'];
                }
            }
        }
    }
    file_put_contents(__DIR__ . '/dublicateList.txt', json_encode([
        'emailList' => $emailList,
        'phoneList' => $phoneList
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);

    $countEmail = array_count_values($emailList);
    $resultEmail = array_filter($countEmail, function($value) {
        return $value >= 2;
    });

    $resultEmail = array_keys($resultEmail);
    foreach ($resultEmail as $email) {
        $responseCustomersListEmail = $client->request->customersList(['email' => $email]);
        foreach ($responseCustomersListEmail->customers as $customer) {
            file_put_contents(__DIR__ . '/customersEmail.txt', json_encode([
                'id' => $customer['id'],
                'email' => $customer['email'],
                'firstName' => $customer['firstName'] ?? '',
                'lastName' => $customer['lastName'] ?? '',
                'patronymic' => $customer['patronymic'] ?? ''
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
        }
    }

    $countPhone = array_count_values($phoneList);
    $resultPhone = array_filter($countPhone, function($value) {
        return $value >= 2;
    });

    foreach ($resultPhone as $phone) {
        $responseCustomersListEmail = $client->request->customersList(['name' => $phone]);
        foreach ($responseCustomersListEmail->customers as $customer) {
            foreach ($customer['phones'] as $phone) {
                file_put_contents(__DIR__ . '/customersPhone.txt', json_encode([
                    'id' => $customer['id'],
                    'phone' => $phone,
                    'firstName' => $customer['firstName'] ?? '',
                    'lastName' => $customer['lastName'] ?? '',
                    'patronymic' => $customer['patronymic'] ?? ''
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
            }
        }
    }

    echo 'success';
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