<?php

namespace console\models;
use common\components\Curl;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;

/**
 * Class Elastic
 * @package console\models
 */
Class Elastic
{
    private $url;
    private $index;
    private $type;

    /**
     * Elastic constructor.
     * @param $elastic_url
     * @param $elastic_index
     * @param $elastic_type
     * @throws ForbiddenHttpException
     */
    public function __construct($elastic_url, $elastic_index, $elastic_type)
    {
        if (!$elastic_url || !$elastic_index || !$elastic_type) {
            throw new ForbiddenHttpException("Elastic params not set.");
        }
        $this->url = $elastic_url;
        $this->index = $elastic_index;
        $this->type = $elastic_type;
    }

    /**
     * @param $jsonMap
     * @return array
     * @throws HttpException
     */
    public function mapping($jsonMap)
    {
        $elastic_request_url = $this->url . "/" . $this->index;
        $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];
        // try to create index
        $result = Curl::sendRequest($elastic_request_url, "PUT", "", $curl_options);
        // mapping
        $elastic_request_url = $this->url . "/" . $this->index . "/_mapping/" . $this->type;
        $result = Curl::sendRequest($elastic_request_url, "PUT", $jsonMap, $curl_options);
        return $result;
    }

    /**
     * @param $docArr
     * @return array
     * @throws HttpException
     */
    public function indexBudget($docArr)
    {
        $elastic_url = Yii::$app->params['elastic_url'] ?? "";
        $elastic_request_url = $elastic_url . "/" . $this->index . "/" . $this->type . "/";
        $data_string = json_encode($docArr);
        $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];
        $result = Curl::sendRequest($elastic_request_url . $docArr['ocid'], "POST", $data_string, $curl_options);
        return $result;
    }

    /**
     * @param $docArr
     * @return array
     * @throws HttpException
     */
    public function indexTender($docArr)
    {
        $elastic_url = Yii::$app->params['elastic_url'] ?? "";
        $elastic_request_url = $elastic_url . "/" . $this->index . "/" . $this->type . "/";
        $data_string = json_encode($docArr);
        $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];
        $result = Curl::sendRequest($elastic_request_url . $docArr['tenderId'], "POST", $data_string, $curl_options);
        return $result;
    }

    /**
     * @param $docArr
     * @return array
     * @throws HttpException
     */
    public function indexPlan($docArr)
    {
        $elastic_url = Yii::$app->params['elastic_url'] ?? "";
        $elastic_request_url = $elastic_url . "/" . $this->index . "/" . $this->type . "/";
        $data_string = json_encode($docArr);
        $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];
        $result = Curl::sendRequest($elastic_request_url . $docArr['id'], "POST", $data_string, $curl_options);
        return $result;
    }

    /**
     * @return array
     * @throws HttpException
     */
    public function dropIndex() {

        Yii::info("Deleting index: " . $this->index, 'console-msg');
        $elastic_url = Yii::$app->params['elastic_url'] ?? "";
        $elastic_request_url = $elastic_url . "/" . $this->index;
        $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];
        $result = Curl::sendRequest($elastic_request_url, "DELETE", "", $curl_options);
        return $result;
    }

}