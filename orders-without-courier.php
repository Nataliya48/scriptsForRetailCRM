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
    $response = $client->request->ordersList([], null, 20);
} catch (\RetailCrm\Exception\CurlException $e) {
    echo "Connection error: " . $e->getMessage();
}

if ($response->isSuccessful()) {
    $totalPageCount = $response->pagination['totalPageCount'];
    $ordersList = [];
    for ($page = 1; $page <= $totalPageCount; $page++) {
        $responseOrdersList = $client->request->ordersList([], $page, 20);
        foreach ($responseOrdersList->orders as $order) {
            $ordersList[] = $order['id'];
        }
    }
    file_put_contents(__DIR__ . '/ordersId.log', json_encode([
        'date' => date('Y-m-d H:i:s'),
        'ordersId' => $ordersList
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);

    $idsCouriers = [];
    $responseCouriersList = $client->request->couriersList();
    foreach ($responseCouriersList->couriers as $courier){
        $idsCouriers[] = $courier['id'];
    }
    file_put_contents(__DIR__ . '/idsCouriers.log', json_encode([
        'date' => date('Y-m-d H:i:s'),
        'idsCouriers' => $idsCouriers
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);

    $responseOrdersListCouriers = $client->request->ordersList(['couriers' => $idsCouriers], null, 20);
    $totalPageCountCouriers = $responseOrdersListCouriers->pagination['totalPageCount'];
    for ($page = 1; $page <= $totalPageCountCouriers; $page++) {
        $responseOrdersListCouriers2 = $client->request->ordersList(['couriers' => $idsCouriers], $page, 20);
        foreach ($responseOrdersListCouriers2->orders as $order) {
            $ordersListCouriers[] = $order['id'];
        }
    }
    file_put_contents(__DIR__ . '/ordersListCouriers.log', json_encode([
        'date' => date('Y-m-d H:i:s'),
        'idsOrdersCouriers' => $ordersListCouriers
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);

    $result = array_diff($ordersList, $ordersListCouriers);
    file_put_contents(__DIR__ . '/resultListOrders.log', json_encode([
        'date' => date('Y-m-d H:i:s'),
        'ordersId' => $result
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
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
