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

$id = 1118;

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
    $responseOrdersCreate = $client->request->ordersCreate($order);
    $idNewOrder = $responseOrdersCreate['id'];
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
            if ($history['field'] == $field->attributes()['id'] && $history['newValue'] !== null){
                $responseOrdersEdit = $client->request->ordersEdit([$field->__toString() => $history['newValue'], 'id' => $idNewOrder], 'id');
                file_put_contents('edit.log', json_encode(
                    ['DATE' => date('Y-m-d H:i:s'),
                        'fieldHistory' => $history['field'],
                        'fieldAttributes' => $field->attributes()['id'],
                        'newValue' => $history['newValue'],
                        'fieldCRM' => $field->__toString()
                    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
                file_put_contents('responce.log', json_encode([
                    'DATE' => date('Y-m-d H:i:s'),
                    'orderId' => $idNewOrder,
                    'response' => [$responseOrdersEdit->getStatusCode(),
                        $responseOrdersEdit->isSuccessful(),
                        isset($responseOrdersEdit['errorMsg']) ? $responseOrdersEdit['errorMsg'] : 'not errors']
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), FILE_APPEND);
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
