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
    private $index;
    private $elastic_url;

    /**
     * Elastic constructor.
     * @throws ForbiddenHttpException
     */
    public function __construct()
    {
        $this->index = Yii::$app->params['elastic_index'] ?? "";
        $this->elastic_url = Yii::$app->params['elastic_url'] ?? "";
        if (!$this->index || !$this->elastic_url) {
            throw new ForbiddenHttpException("Elastic params not set.");
        }
    }

    /**
     * @param $jsonMap
     * @param $type
     * @return array
     * @throws \yii\web\HttpException
     */
    public function mapping($jsonMap, $type)
    {
        $elastic_url = Yii::$app->params['elastic_url'] ?? "";
        $elastic_request_url = $this->elastic_url . "/" . $this->index;
        $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];
        // try to create index
        $result = Curl::sendRequest($elastic_request_url, "PUT", "", $curl_options);
        // mapping
        $elastic_request_url = $elastic_url . "/" . $this->index . "/_mapping/" . $type;
        $result = Curl::sendRequest($elastic_request_url, "PUT", $jsonMap, $curl_options);
        return $result;
    }

    /**
     * @param $type
     * @param $docArr
     * @return array
     * @throws HttpException
     */
    public function indexDoc($type, $docArr)
    {
        $elastic_url = Yii::$app->params['elastic_url'] ?? "";
        $elastic_request_url = $elastic_url . "/" . $this->index . "/" . $type . "/";

        $data_string = json_encode($docArr);
        $curl_options = ['HTTPHEADER' => ['Content-Type:application/json']];
        $result = Curl::sendRequest($elastic_request_url . $docArr['ocid'], "POST", $data_string, $curl_options);
        return $result;
    }

    /**
     * @return array
     * @throws \yii\web\HttpException
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