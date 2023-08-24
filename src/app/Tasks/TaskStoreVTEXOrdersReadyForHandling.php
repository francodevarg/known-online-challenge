<?php

namespace App\Tasks;

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

        while ($currentPage <= $totalPages){

            $queryParams = static::setCustomQueryParams($currentPage);

            try {
                $dataOrders = GuzzleAPI::get($URI,$option_header,$queryParams);

                
                $totalPages = $dataOrders['paging']['pages'];
                $totalOrders = $dataOrders['paging']['total'];

                $orderList = $dataOrders['list'];
                
                foreach ($orderList as $order) {
                    $orderID = $order['orderId'];
                    $URI_singleOrder = static::getOrderURI($order['orderId']);
                    
                    // Fetch Every Single Order
                    $dataSingleOrder = GuzzleAPI::get($URI_singleOrder,$option_header,null);
                    
                    // Structuring Data
                    $clientObject = static::parseToObjectOrderClient($dataSingleOrder);
                    // $items = static::getArrayItemsFromOrder($dataSingleOrder['items']);
                    
                    $totalAmountOrder = $order['totalValue'];
                    $totalItemsOrder = $order['totalItems'];
                    
                    $paymentMethod = $order['paymentNames'];

                    static::logCurrentOrder($orderID,$clientObject,$paymentMethod,$totalAmountOrder);


                    //TODO: Save Data at DB.

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
    private static function getOrderURI($orderID =null){
        $accountName= env(self::VTEX_ACCOUNT_NAME);
        $environment= env(self::VTEX_ENVIRONMENT);
        if(is_null($orderID)){
            return "https://{$accountName}.{$environment}.com.br/api/oms/pvt/orders/";
        }
        return "https://{$accountName}.{$environment}.com.br/api/oms/pvt/orders/{$orderID}";
    }

    /**
     * @return array Custom Headers with VTEX App Auth.
     */
    private static function getHeaderWithAuthentication(){
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
    private static function setCustomQueryParams($page = 1){
        
        $startDateTime = '2023-01-01T00:00:00.000Z'; //From Jan 2023;
        $finalDateTime = date('Y-m-d\TH:i:s');

        $status = 'ready-for-handling';

        return [
            'page' => $page,     
            'f_creationDate' => [$startDateTime .' TO '.$finalDateTime],
            'f_status' => $status
        ];
    }


    /**
     * @param array $order Single array order data.
     * @return object parse single data Client given order.
     */
    private static function parseToObjectOrderClient($order){
        if(gettype($order) == "array"){
            $data = null;
            try {
                $data = (object)[
                        'firstName' => $order['clientProfileData']['firstName'] ?: null,
                        'lastName' => $order['clientProfileData']['lastName'] ?: null,
                        'email' => $order['clientProfileData']['email'] ?: null,
                        'document' => $order['clientProfileData']['document'] ?: null,
                ];
            } catch (\Exception $ex) {
                Log::channel(self::LOG_CHANNEL)->error('parseToObjectOrderClient: '.$ex->getMessage());
                throw new \Exception($ex->getMessage());
            }
            return $data;
        }
    }

    /**
     * @param array $itemsList Single array order data.
     * @return array items of and Order.
     */
    // private static function getArrayItemsFromOrder($itemsList){
    //     if(gettype($itemsList) == "array"){
    //         $data = [];
    //         try {
    //             foreach ($itemsList as $item) {
    //                 $singleItem = [
    //                     'id' => $item['id'] ?: null,
    //                     'refId' => $item['refId'] ?: null,
    //                     'name' => $item['name'] ?: null,
    //                     'quantity' => $item['quantity'] ?: null,
    //                 ];
    //                 array_push($data,$singleItem);
    //             }
    //         } catch (\Exception $ex) {
    //             Log::channel(self::LOG_CHANNEL)->error('getArrayItemsFromOrder: '.$ex->getMessage());
    //             throw new \Exception($ex->getMessage());
    //         }
    //         return $data;
    //     }
    // }

    private static function logCurrentOrder($orderID,$clientObject,$paymentMethod =null,$totalAmount=null){
        $message = 'OrderID: '. $orderID . '|'. ' ClientFirstName: '. 
                    $clientObject->firstName. '|'. ' ClientLastName: '
                    .$clientObject->lastName. '|'. ' PaymentMethod: '
                    .$paymentMethod . '|' . ' TotalAmount: '
                    .$totalAmount;

        echo $message.PHP_EOL;
        Log::channel(self::LOG_CHANNEL)->info($message);
    }

    //TODO: Save Data at DB.
}
