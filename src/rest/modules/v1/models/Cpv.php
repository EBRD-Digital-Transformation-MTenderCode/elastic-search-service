<?php
namespace rest\modules\v1\models;

use Yii;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\base\Model;
use ustudio\service_mandatory\ServiceException;

/**
 * Class Cpv
 * @package rest\modules\v1\models
 */
class Cpv extends Model
{
    public $language;
    public $idOrName;

    protected $index;
    protected $type;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['language', 'idOrName'], 'required'],
            ['idOrName', 'string', 'min' => 3]
        ];
    }

    /**
     * Search in elastic by attributes
     * @param $searchAttributes
     * @return array|\yii\httpclient\Response
     * @throws ServiceException
     */
    public function search($searchAttributes)
    {
        $this->index = Yii::$app->params['elastic_cpv_index'];
        $this->type = Yii::$app->params['elastic_cpv_type'];

        $this->setAttributes($searchAttributes);

        if (!$this->validate()) {
            throw new ServiceException('Data Validation Failed', 400, [
                'model_errors' => $this->getErrors(),
            ]);
        }

        $url = Yii::$app->params['elastic_url'] . DIRECTORY_SEPARATOR
            . $this->index . DIRECTORY_SEPARATOR
            . $this->type . DIRECTORY_SEPARATOR . '_search';

        // формирование json для эластик
        $query = '{}';
        if (!empty($searchAttributes)) {
            $mustItems = [];
            $shouldItems = [];

            if (!empty($this->idOrName)) {
                $mustItems[] = '{"match": {"idOrName.' . $this->language . '":"' . $this->idOrName .'"}}';
                $shouldItems[] = '{"match": {"idOrNameStrict.' . $this->language . '":"' . $this->idOrName .'"}}';
            }

            $should = '"should":[' . implode(',', $shouldItems) . ']';
            $must = '"must":[' . implode(',', $mustItems) . ']';
            $query = '{"bool":{' . $should . ',' . $must . '}}';
        }

        // пагинация
        $pageSize = Yii::$app->params['elastic_page_size'];
        $page = 1;
        $pagination = '"from":' . ($page * $pageSize - $pageSize) . ',"size":' . $pageSize . ',';
        $data_string = '{' . $pagination . '"query":' . $query . '}';

        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->setContent($data_string)
            ->setHeaders(['content-type' => 'application/json']);

        try {
            $result = $response->send();
        } catch (Exception $exception) {
            throw new ServiceException($exception->getMessage(), 500);
        }

        // формирование результата поиска
        $data = json_decode($result->getContent(), true);

        $result = [];
        $totalCount = 0;

        if (isset($data['hits'])) {
            $totalCount = $data['hits']['total'];
            foreach ($data['hits']['hits'] as $hit) {
                $item = $hit['_source'];
                $item['_score'] = $hit['_score'] ?? 0;
                $item['name'] = (!empty($item['name'][$this->language])) ? $item['name'][$this->language] : '';
                $result[] = $item;
            }
        }

        return $result;
    }
}