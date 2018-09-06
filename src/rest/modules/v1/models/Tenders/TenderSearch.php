<?php
namespace rest\modules\v1\models\Tenders;

use Yii;
use ustudio\service_mandatory\ServiceException;
use rest\components\dataProviders\ArrayWithoutSortDataProvider;
use yii\httpclient\Exception;
use yii\httpclient\Client;

/**
 * Class TenderSearch
 * @package common\models
 */
class TenderSearch extends Tender
{
    const FULL_TEXT_ATTRIBUTES = ['search'];
    const STRICT_SUFFIX = '_strict';
    const CHAR_LIMIT = 2;

    public $ocid;
    public $title;
    public $description;
    public $search;
    public $pageSize;
    public $page;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ocid', 'title', 'description', 'search'], 'string'],
            [['pageSize', 'page'], 'integer'],
        ];
    }

    /**
     * @param $params
     * @return \rest\components\dataProviders\ArrayWithoutSortDataProvider
     * @throws ServiceException
     */
    public function search($params)
    {
        $this->setAttributes($params);

        if (!$this->validate()) {
            throw new ServiceException('Data Validation Failed', 400, [
                'model_errors' => $this->getErrors(),
            ]);
        }

        $searchAttributes = [];

        if ($this->ocid) {
            $searchAttributes['ocid'] = $this->ocid;
        }

        if ($this->title) {
            $searchAttributes['title'] = $this->title;
        }

        if ($this->description) {
            $searchAttributes['description'] = $this->description;
        }

        if ($this->search) {
            $searchAttributes['search'] = $this->search;
        }

        return $this->elasticSearch(
            $searchAttributes,
            $params,
            Yii::$app->request->getQueryParam('page'),
            Yii::$app->request->getQueryParam('pageSize')
        );
    }

    /**
     * @param $searchAttributes
     * @param array $params
     * @param int $page
     * @param null $pageSize
     * @return ArrayWithoutSortDataProvider
     * @throws ServiceException
     */
    private function elasticSearch($searchAttributes, $params = [], $page = 1, $pageSize = null)
    {
        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
        $url = Yii::$app->params['elastic_url'] . DIRECTORY_SEPARATOR
            . Yii::$app->params['elastic_tenders_index'] . DIRECTORY_SEPARATOR
            . Yii::$app->params['elastic_tenders_type'] . DIRECTORY_SEPARATOR . '_search';

        if (!empty($searchAttributes)) {
            $matches = [];

            foreach ($searchAttributes as $key => $value) {
                if (in_array($key, self::FULL_TEXT_ATTRIBUTES) && isset($params[$key . self::STRICT_SUFFIX]) && $params[$key . self::STRICT_SUFFIX]) {
                    if (mb_strlen($value) > self::CHAR_LIMIT) {
                        $matches[] = '{"match_phrase":{"' . $key . '":"' . $value . '"}}';
                    }
                } else {
                    $words = explode(' ', $value);
                    $filteredWords = [];

                    foreach ($words as $word) {
                        if (mb_strlen($word) > self::CHAR_LIMIT) {
                            $filteredWords[] = $word;
                        }
                    }

                    $matches[] = '{"match":{"' . $key . '":"' . implode(' ', $filteredWords) . '"}}';
                }
            }

            $query = '{"bool":{"must":[' . implode(',', $matches) . ']}}';
        } else {
            $query = '{}';
        }

        $pageSize = $pageSize ? $pageSize : Yii::$app->params['elastic_page_size'];
        $page = $page ? $page : 1;
        $pagination = '"from":' . ($page * $pageSize - $pageSize) . ',"size":' . $pageSize . ',';

        $response = $client->createRequest()
            ->setMethod('GET')
            ->setUrl($url)
            ->setContent('{' . $pagination . '"query":' . $query . '}')
            ->setOptions(['HTTPHEADER' => ['Content-Type:application/json']]);

        try {
            $result = $response->send();
        } catch (Exception $exception) {
            throw new ServiceException($exception->getMessage(), 500);
        }

        $data = json_decode($result->getContent(), true);
        $result = [];
        $totalCount = 0;

        if (isset($data['hits'])) {
            $totalCount = $data['hits']['total'];
            foreach ($data['hits']['hits'] as $hit) {
                $result[] = $hit['_source'];
            }
        }

        return new ArrayWithoutSortDataProvider([
            'allModels'  => $result,
            'pagination' => [
                'pageSize' => $pageSize,
                'totalCount' => $totalCount,
            ],
        ]);
    }

}