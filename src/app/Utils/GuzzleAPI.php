<?php
namespace App\Utils;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

final class GuzzleAPI {

    const LOG_CHANNEL = 'GuzzleAPI';
    
    /**
     * 
     *  Return json_decode array only if Status Code == 200,
     *  otherwise return []
     *  @param string $uri
     *  @param array $option_header asocc 'headers' array
     *  @param array $queryParams
     *  @return array $returnData
     */
    public static function get(string $uri, $option_header = null, $queryParams = null) {
        try {
            $returnData = [];

            if(is_null($option_header)) {
                $option_header = [  
                    'headers' => [
                        'Content-Type'  => 'application/json',
                    ], 
                    'verify'  => false,
                    'responseJsonToArray' => false,
                    'acceptCharset'       => 'UTF-8',
                    'returnTransfer'      => true,
                    'sslVerifypeer'       => false,
                    'sslVerifyhost'       => false,
                ];    
            }

            if(!is_null($queryParams)) $option_header['query'] = $queryParams;
            
            $client = new Client();

            $response = $client->request('GET', $uri, $option_header);
    
            if($response->getStatusCode() == 200) {
                $returnData = json_decode($response->getBody()->getContents(),
                                         JSON_OBJECT_AS_ARRAY);
            }
            
            return $returnData;
        } catch (\Exception $ex) {
            $message = "Error at get() method: ( class GuzzleAPI )- Exception: ";
            $messageAndError = $message . $ex->getMessage();
            Log::channel(self::LOG_CHANNEL)->error($messageAndError);
            echo($messageAndError);
        }
	}

}