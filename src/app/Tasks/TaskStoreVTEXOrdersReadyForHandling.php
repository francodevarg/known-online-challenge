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
    const ENVIRONMENT = 'VTEX_ENVIRONMENT';
    const ACCOUNT_NAME = 'VTEX_ACCOUNT_NAME';


    /**
     * Process.
     */
    public static function process()
    {
        $accountName= env(self::ACCOUNT_NAME);
        $environment= env(self::ENVIRONMENT);

        $uri = "https://{$accountName}.{$environment}.com.br/api/oms/pvt/orders/";

        $option_header = [  
            'headers' => [
                'Content-Type'  => 'application/json',
                'X-VTEX-API-AppKey'  => env(self::X_VTEX_API_AppKey),
                'X-VTEX-API-AppToken' => env(self::X_VTEX_API_AppToken),
            ],
        ];    

        $queryParams = [
            'page' => 1,        //From Jan 2023 to Today
            'f_creationDate' => ['2023-01-01T00:00:00.000Z' .' TO '.date('Y-m-d\TH:i:s')],
            'f_status' => 'ready-for-handling'
        ];

        //array $data
        $singleOrderUri = $uri . '1354670507840-01';
        // $data = GuzzleAPI::get($uri,$option_header,$queryParams);
        $data = GuzzleAPI::get($singleOrderUri,$option_header);
        Log::info('$data', [$data]);
        //TODO: Algoritmo
        // 
        // $currentPage = 1;
        // $totalPages = 999999;
        //while ($currentPage <= $totalPages){

            //Fetch Orders with status ready-for-handling
            //Hago la Peticion con Guzzle
            //$data = GuzzleAPI::get($uri,$option_header,$queryParams);


            // $totalPages = $data['paging']['pages'];

            // $orders = $data['list'];

            // foreach ($orders as $order) {
            //     # code...
            //     Fetch Every Single Order with Guzzle
            //   
            //     Save data Client
            //     Order
            //     Products
            // }

            // $currentPage++

        // }
    }
}
