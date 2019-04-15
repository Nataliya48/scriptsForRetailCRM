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
    $response = $client->request->ordersList(['createdAtFrom' => '2019-04-01'], null, 20);
} catch (\RetailCrm\Exception\CurlException $e) {
    echo "Connection error: " . $e->getMessage();
}

if ($response->isSuccessful()) {
    $totalPageCount = $response->pagination['totalPageCount'];

    $ordersList = [];
    for ($page = 1; $page <= $totalPageCount; $page++) {
        $responseOrdersList = $client->request->ordersList(['createdAtFrom' => '2019-04-01'], $page, 20);
        foreach ($responseOrdersList->orders as $order) {
            $ordersList[] = $order['id'];
        }
    }
    file_put_contents('ordersId.log', json_encode(['DATE' => date('Y-m-d H:i:s'), 'ordersLd' => $ordersList], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);

    for ($page = 1; $page <= $totalPageCount; $page++) {
        foreach ($ordersList as $id) {
            $responseOrdersHistory = $client->request->ordersHistory(['orderId' => $id]);
            file_put_contents('history.log', json_encode(['DATE' => date('Y-m-d H:i:s'), 'orderId' => $id, 'history' => $responseOrdersHistory['history'], 'response' => [$responseOrdersHistory->getStatusCode(), $responseOrdersHistory->isSuccessful(), isset($responseOrdersHistory['errorMsg']) ? $responseOrdersHistory['errorMsg'] : 'not errors']], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
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
