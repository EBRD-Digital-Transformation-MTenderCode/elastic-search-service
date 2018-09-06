<?php
namespace rest\modules\v1\models\Budgets;

use Yii;
use ustudio\service_mandatory\ServiceException;
use rest\components\dataProviders\ArrayWithoutSortDataProvider;
use yii\httpclient\Exception;
use yii\httpclient\Client;

/**
 * Class BudgetsSearch
 * @package common\models\Budgets
 */
class BudgetSearch extends Budget
{
    public $ocid;
    public $title;
    public $description;
    public $pageSize;
    public $page;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ocid', 'title', 'description'], 'string'],
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

        return $this->elasticSearch(
            $searchAttributes,
            Yii::$app->request->getQueryParam('page'),
            Yii::$app->request->getQueryParam('pageSize')
        );
    }


    /**
     * @param $searchAttributes
     * @param int $page
     * @param null $pageSize
     * @return ArrayWithoutSortDataProvider
     * @throws ServiceException
     */
    private function elasticSearch($searchAttributes, $page = 1, $pageSize = null)
    {
        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
        $url = Yii::$app->params['elastic_url'] . DIRECTORY_SEPARATOR
            . Yii::$app->params['elastic_budgets_index'] . DIRECTORY_SEPARATOR
            . Yii::$app->params['elastic_budgets_type'] . DIRECTORY_SEPARATOR . '_search';

        if (!empty($searchAttributes)) {
            $matches = [];

            foreach ($searchAttributes as $key => $value) {
                $matches[] = '{"match":{"' . $key . '":"' . $value . '"}}';
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