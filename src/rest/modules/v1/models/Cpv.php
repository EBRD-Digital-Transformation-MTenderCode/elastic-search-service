<?php

namespace rest\modules\v1\models;

use Yii;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use rest\components\dataProviders\ArrayWithoutSortDataProvider;
use yii\base\Model;
use ustudio\service_mandatory\ServiceException;

/**
 * Class Cpv
 * @package rest\modules\v1\models
 */
class Cpv extends Model
{
    public $pageSize;
    public $page;

    public $language;
    public $id;
    public $name;

    protected $index;
    protected $type;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['language', 'required'],
            ['name', 'string', 'min' => 3],
            ['id', 'string', 'min' => 3],
            [['pageSize', 'page'], 'integer', 'min' => 1],
        ];
    }

    /**
     * Search in elastic by attributes
     * @param $searchAttributes
     * @return ArrayWithoutSortDataProvider
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

            if (!empty($this->name)) {
                $mustItems[] = '{"match": {"name.' . $this->language . '" : "' . $this->name .'"}}';
            }
            if (!empty($this->id)) {
                $mustItems[] = '{"match": {"id" : "' . $this->id .'"}}';
            }
            $query = '{"bool":{"must":[' . implode(',', $mustItems) . ']}}';
        }

        // пагинация
        $pageSize = $this->pageSize ?? Yii::$app->params['elastic_page_size'];
        $page = $this->page ?? 1;
        $pagination = '"from":' . ($page * $pageSize - $pageSize) . ',"size":' . $pageSize . ',';
        $data_string = '{' . $pagination . '"query":' . $query . '}';

        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->setContent($data_string)
            ->setOptions(['HTTPHEADER' => ['Content-Type:application/json']]);

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

        return new ArrayWithoutSortDataProvider([
            'allModels' => $result,
            'pagination' => [
                'pageSize' => $pageSize,
                'totalCount' => $totalCount,
            ],
        ]);
    }
}