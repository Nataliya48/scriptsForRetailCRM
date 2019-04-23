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

$id = 1106;

try {
    $response = $client->request->ordersHistory(['orderId' => $id], null, 20);
} catch (\RetailCrm\Exception\CurlException $e) {
    echo "Connection error: " . $e->getMessage();
}

if ($response->isSuccessful()) {
    $totalPageCount = $response->pagination['totalPageCount'];
    $order = $response->history[0]['order'];
    $ordersList = [];
    $count = 0;
    for ($page = 1; $page <= $totalPageCount; $page++) {
        $responseOrdersHistory = $client->request->ordersHistory(['orderId' => $id], $page, 20);
        foreach ($responseOrdersHistory->history as $history) {
            $historyList[] = $history;
        }
    }
    print_r(['order' => $order]);
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

    foreach ($mapping as $field){
        foreach ($historyList as $history) {
            if ($history['field'] == $field->attributes()['id']){
                $responseOrdersEdit = $client->request->ordersEdit([$field->__toString() => $history['newValue'], 'id' => $id], 'id');
                file_put_contents('response.log', json_encode(['DATE' => date('Y-m-d H:i:s'), 'fieldHistory' => $history['field'], 'fieldAttributes' => $field->attributes()['id'], 'newValue' => $history['newValue'], 'fieldCRM' => $field->__toString()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
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
