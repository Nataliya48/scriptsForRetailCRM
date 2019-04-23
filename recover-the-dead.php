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

$id = 1109;

try {
    $response = $client->request->ordersHistory(['orderId' => $id], null, 20);
} catch (\RetailCrm\Exception\CurlException $e) {
    echo "Connection error: " . $e->getMessage();
}

if ($response->isSuccessful()) {
    $totalPageCount = $response->pagination['totalPageCount'];
    $order = $response->history[0];
    $ordersList = [];
    $count = 0;
    for ($page = 1; $page <= $totalPageCount; $page++) {
        $responseOrdersHistory = $client->request->ordersHistory(['orderId' => $id], $page, 20);
        foreach ($responseOrdersHistory->history as $history) {
            $historyList[] = $history;
        }
    }
    $responseOrdersCreate = $client->request->ordersCreate($order);
    file_put_contents('history.log', json_encode(['DATE' => date('Y-m-d H:i:s'), 'history' => $historyList], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
    unset($historyList[0]);

    $xml = simplexml_load_file('objects.xml');
    $fieldCreate = [];
    foreach ($xml->children() as $child) {
        $fieldCreate[] = $child;
    }
    $mapping = [];
    foreach ($xml->children()->children() as $child) {
        $mapping[] = $child;
    }

    foreach ($historyList as $history) {
        foreach ($mapping as $field){
            if ($history['field'] == $field->attributes()['id']){
                $responseOrdersEdit = $client->request->ordersEdit([$field[0] => $history['newValue'], 'id' => $id], 'id');
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
