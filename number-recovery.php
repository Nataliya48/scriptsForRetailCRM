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
    $response = $client->request->ordersList(['createdAtFrom' => '2019-04-05 17:00', 'createdAtTo' => '2019-04-08 16:00'], null, 20);
} catch (\RetailCrm\Exception\CurlException $e) {
    echo "Connection error: " . $e->getMessage();
}

if ($response->isSuccessful()) {
    $totalPageCount = $response->pagination['totalPageCount'];
    for ($page = 1; $page <= $totalPageCount; $page++){
        $responseOrdersList = $client->request->ordersList(['createdAtFrom' => '2019-04-05 17:00', 'createdAtTo' => '2019-04-08 16:00'], $page, 20);
        foreach ($responseOrdersList->orders as $order) {
            if (empty($order['number'])){
                $ordersEmpty[] = $order['id'];
            }
        }
    }
    var_dump($ordersEmpty);

    foreach ($ordersEmpty as $id) {
        $responseOrdersHistory = $client->request->ordersHistory(['orderId' => $id]);
        foreach ($responseOrdersHistory->history as $history) {
            if (isset($history['order']['number'])) {
                $responseOrdersEdit = $client->request->ordersEdit(['number' => $history['order']['number'], 'id' => $history['order']['id']], 'id');
                var_dump($responseOrdersEdit);
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
