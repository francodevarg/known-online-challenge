<?php

namespace App\Tasks;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Utils\GuzzleAPI;
use Illuminate\Support\Facades\Log;

class TaskStoreVTEXOrdersReadyForHandling
{
    const LOG_CHANNEL = 'app:store_vtex_orders_ready_for_handling';

    //CredentialsVTEX
    const X_VTEX_API_AppKey = 'VTEX_API_APP_KEY';
    const X_VTEX_API_AppToken = 'VTEX_API_APP_TOKEN';
    const VTEX_ENVIRONMENT = 'VTEX_ENVIRONMENT';
    const VTEX_ACCOUNT_NAME = 'VTEX_ACCOUNT_NAME';


    /**
     * Fetch Orders from VTEX and save them in DB.
     */
    public static function process()
    {
        $URI = static::getOrderURI();
        $option_header = static::getHeaderWithAuthentication();

        $totalOrdersProcessed = 0;
        $currentPage = 1;
        $totalPages = 999999;

        while ($currentPage <= $totalPages) {

            $queryParams = static::setCustomQueryParams($currentPage);

            try {
                //Fetch /orders API
                $dataOrders = GuzzleAPI::get($URI, $option_header, $queryParams);

                $totalPages = $dataOrders['paging']['pages'];
                $totalOrdersVTEX = $dataOrders['paging']['total'];
                
                $ordersList = $dataOrders['list'];
                foreach ($ordersList as $order) {
                    $orderID = $order['orderId'];
                    
                    $URIsingleOrder = static::getOrderURI($orderID);

                    // Fetch Single Order /orders/{order_id} API
                    $dataSingleOrder = GuzzleAPI::get($URIsingleOrder, $option_header, null);

                    // Structuring Data
                    $clientObject = static::parseToObjectOrderClient($dataSingleOrder);
                    $orderArray = static::convertArrayOrder($orderID,$order['paymentNames'],$order['totalValue']);
                    $itemsProducts = static::getArrayItemsFromOrder($dataSingleOrder['items']);
                    
                    //Log
                    static::logProcessedOrder($orderArray, $clientObject); 
                    
                    //Storage In DB.
                    static::saveInDB($clientObject, $orderArray, $itemsProducts);


                    $totalOrdersProcessed++;
                }

                //Only one Page => 15 per page default API
                die;

                //NextPage
                $currentPage++;
            } catch (\Exception $ex) {
                throw new \Exception($ex->getMessage());
            }
        }
    }

    /**
     * @return string URI to get Orders from VTEX Store, that 
     *         were previously set in .env file.
     */
    private static function getOrderURI($orderID = null)
    {
        $accountName = env(self::VTEX_ACCOUNT_NAME);
        $environment = env(self::VTEX_ENVIRONMENT);
        if (is_null($orderID)) {
            return "https://{$accountName}.{$environment}.com.br/api/oms/pvt/orders/";
        }
        return "https://{$accountName}.{$environment}.com.br/api/oms/pvt/orders/{$orderID}";
    }

    /**
     * @return array Custom Headers with VTEX App Auth.
     */
    private static function getHeaderWithAuthentication()
    {
        return [
            'headers' => [
                'Content-Type'  => 'application/json',
                'X-VTEX-API-AppKey'  => env(self::X_VTEX_API_AppKey),
                'X-VTEX-API-AppToken' => env(self::X_VTEX_API_AppToken),
            ],
        ];
    }

    /**
     * @return array Custom Query Params with: +page, 
     *               +f_status, +f_creationDate
     */
    private static function setCustomQueryParams($page = 1)
    {

        $startDateTime = '2023-01-01T00:00:00.000Z'; //From Jan 2023;
        $finalDateTime = date('Y-m-d\TH:i:s');

        $status = 'ready-for-handling';

        return [
            'page' => $page,
            'f_creationDate' => [$startDateTime . ' TO ' . $finalDateTime],
            'f_status' => $status
        ];
    }


    /**
     * @param array $order Single array order data.
     * @return object parse single data Client given order.
     */
    private static function parseToObjectOrderClient($order)
    {
        if (gettype($order) == "array") {
            $data = null;
            try {
                $data = (object)[
                    'firstName' => $order['clientProfileData']['firstName'] ?: null,
                    'lastName' => $order['clientProfileData']['lastName'] ?: null,
                    'email' => $order['clientProfileData']['email'] ?: null,
                    'document' => $order['clientProfileData']['document'] ?: null,
                ];
            } catch (\Exception $ex) {
                Log::channel(self::LOG_CHANNEL)->error('parseToObjectOrderClient: ' . $ex->getMessage());
                throw new \Exception($ex->getMessage());
            }
            return $data;
        }
    }

    /**
     * @param array $itemsList Single array order data.
     * @return array items of and Order.
     */
    private static function getArrayItemsFromOrder($itemsList)
    {
        if (gettype($itemsList) == "array") {
            $data = [];
            try {
                foreach ($itemsList as $item) {
                    $singleItem = [
                        'id' => $item['id'] ?: null,
                        'refId' => $item['refId'] ?: null,
                        'name' => $item['name'] ?: null,
                        'quantity' => $item['quantity'] ?: null,
                    ];
                    array_push($data, $singleItem);
                }
            } catch (\Exception $ex) {
                Log::channel(self::LOG_CHANNEL)->error('getArrayItemsFromOrder: ' . $ex->getMessage());
                throw new \Exception($ex->getMessage());
            }
            return $data;
        }
    }

    private static function logProcessedOrder($orderArray, $clientObject)
    {
        $message = 'VTEX_OrderID: ' . $orderArray['vtex_order_id'] . '|' . ' ClientFirstName: ' .
            $clientObject->firstName . '|' . ' ClientLastName: '
            . $clientObject->lastName . '|' . ' PaymentMethod: '
            . $orderArray['paymentMethod'] . '|' . ' TotalAmount: '
            . $orderArray['totalAmount'];

        echo $message . PHP_EOL;
        Log::channel(self::LOG_CHANNEL)->info($message);
    }

    private static function convertArrayOrder($orderID,$paymentMethod,$totalAmountOrder){
        return [
            'vtex_order_id' => $orderID,
            'paymentMethod' => $paymentMethod,
            'totalAmount' => $totalAmountOrder,
        ];
    }

    private static function saveInDB($client, $currentOrder, $items)
    {

        try {
            $client = Client::firstOrCreate(
                ['email' => $client->email],
                [
                    'firstName' => $client->firstName,
                    'lastName' => $client->lastName
                ]
            );

            $order = Order::firstOrCreate(
                ['vtex_order_id' => $currentOrder['vtex_order_id']],
                [
                    'client_id' => $client->id,
                    'vtex_order_id' => $currentOrder['vtex_order_id'],
                    'totalAmount' => $currentOrder['totalAmount'],
                    'paymentMethod' => $currentOrder['paymentMethod'],
                ]
            );
            $currentProduct = null;
            foreach ($items as $product) {
                $currentProduct = Product::firstOrCreate(
                    [
                        'order_id' => $order->id,
                        'vtex_product_id' => $product['id'],
                    ],
                    [
                        'order_id' => $order->id,
                        'vtex_product_id' => $product['id'],
                        'refId' => $product['refId'],
                        'name' => $product['name'],
                        'quantity' => $product['quantity'],
                    ]
                );
                static::logSaveInDB($order,$client,$currentProduct);
            }
        } catch (\Exception $ex) {
            Log::channel(self::LOG_CHANNEL)->error('saveInDB: ' . $ex->getMessage());
        }
    }


    private static function logSaveInDB($order, $client,$product)
    {
        $message = 'VTEX_OrderID: ' . $order['vtex_order_id'] . '|' . ' ClientFirstName: ' .
            $client['firstName'] . '|' . ' ClientLastName: '
            . $client['lastName']. '|' . ' PaymentMethod: '
            . $order['paymentMethod'] . '|' . ' TotalAmount: '
            . $order['totalAmount']. '|' . ' ProductName: '
            . $product['name'].'|'. ' ProductRefId: '
            . $product['refId']
            ;
        Log::channel('VTEX_Orders_Ready_For_Handling_In_MySQL')->info($message);
    }

}
