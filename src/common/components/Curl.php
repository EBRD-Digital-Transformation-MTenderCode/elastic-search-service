<?php
namespace common\components;
use yii\web\HttpException;

/**
 * Class Curl
 * @package console\models
 */
class Curl
{
    /**
     * CURL coverage function
     * @param string $url
     * @param string $method
     * @param string $data_string
     * @param array $curlOptions
     * @return array
     * @throws HttpException
     */
    public static function sendRequest(string $url, string $method, $data_string = "", $curlOptions = [])
    {
        $result = ['code' => 0, 'headers' => [], 'body' => ''];
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HEADER, true);
        // Gather additional optional options:
        foreach ($curlOptions as $key => $value) {
            curl_setopt($curl, constant('CURLOPT_' . $key), $value);
        }
        // Executing!
        $content = curl_exec($curl); //false or string

        if(curl_errno($curl)){
            throw new HttpException(500, 'Request Error: ' . curl_strerror(curl_errno($curl)), curl_errno($curl));
        }

        if ($content === false) {
            return ($result);
        }
        // Put response headers and body into two array elements: headers to [0], body to [1]:
        $split = explode("\n\r", $content, 2);
        // Parse response http code from headers:
        preg_match('/.+(\d{3})/', $split[0], $code);

        $result['code'] = (int)$code[1];
        $curlOptions = explode("\n", $split[0]);
        foreach ($curlOptions as $part) {
            $middle = explode(":", $part);
            if (count($middle) <= 1) {
                $result['headers'][] = $part;
                continue;
            }
            $result['headers'][trim($middle[0])] = trim($middle[1]);
        }

        if (count($split) > 1) {
            $result['body'] = $split[1];
        }

        curl_close($curl);
        return ($result);
    }
}