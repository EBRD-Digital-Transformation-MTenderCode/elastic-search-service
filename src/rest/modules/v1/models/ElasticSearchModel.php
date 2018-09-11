<?php
namespace rest\modules\v1\models;

use Yii;
use yii\base\Model;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use common\components\validators\JsonListValidator;
use rest\components\dataProviders\ArrayWithoutSortDataProvider;
use ustudio\service_mandatory\ServiceException;

/**
 * Class ElasticSearchModel
 * @package rest\modules\v1\models
 */
class ElasticSearchModel extends Model
{
    const FIELDS_FULLTEXT = ['search', 'title', 'description'];
    const FIELDS_RANGE = ['budget_from', 'budget_to'];
    const STRICT_SUFFIX = '_strict';
    const CHAR_LIMIT = 2;

    public $tender_id;
    public $pageSize;
    public $page;
    public $search_strict;
    public $buyer_region;
    public $procedure_number;
    public $procedure_type;
    public $procedure_status;
    public $budget_from;
    public $budget_to;
    public $classification;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pageSize', 'page'], 'integer', 'min' => 1],
            [['search_strict'], 'boolean'],
            [['search_strict'], 'default', 'value' => 0],
            [[
                'buyer_region',
                'procedure_type',
                'procedure_status',
                'classification'
            ], JsonListValidator::className(), 'skipOnEmpty' => true],
        ];
    }

    /**
     * @param array $searchAttributes
     * @param string $index
     * @param string $type
     * @return ArrayWithoutSortDataProvider
     * @throws ServiceException
     */
    public function search(array $searchAttributes, string $index, string $type)
    {
        $this->setAttributes($searchAttributes);

        if (!$this->validate()) {
            throw new ServiceException('Data Validation Failed', 400, [
                'model_errors' => $this->getErrors(),
            ]);
        }

        $url = Yii::$app->params['elastic_url'] . DIRECTORY_SEPARATOR
            . $index . DIRECTORY_SEPARATOR
            . $type . DIRECTORY_SEPARATOR . '_search';

        // формирование json для эластик
        if (!empty($searchAttributes)) {
            $mustItems = [];
            $filterItems = [];
            $filterRangeItems = [];

            foreach ($searchAttributes as $key => $value) {
                if (in_array($key, self::FIELDS_FULLTEXT)) {
                    //  если выбрано строгое соответствие
                    $strict_mode = isset($this->{$key . self::STRICT_SUFFIX}) && $this->{$key . self::STRICT_SUFFIX};
                    if ($strict_mode) {
                        if (mb_strlen($value) > self::CHAR_LIMIT) {
                            $mustItems[] = '{"match_phrase":{"' . $key . '":"' . $value . '"}}';
                        }
                        //  не строгое
                    } else {
                        $words = explode(' ', $value);
                        $filteredWords = [];

                        // "отсечение" коротких слов
                        foreach ($words as $word) {
                            if (mb_strlen($word) > self::CHAR_LIMIT) {
                                $filteredWords[] = $word;
                            }
                        }

                        $mustItems[] = '{"match":{"' . $key . '":"' . implode(' ', $filteredWords) . '"}}';
                    }
                } elseif (in_array($key, self::FIELDS_RANGE)) {
                    $fieldData = explode('_', $key);
                    $filterRangeItems[$fieldData[0]][$fieldData[1]] = $value;
                } else {
                    if (is_array($this->{$key})) {
                        $filterItems[] = '{"terms":{"' . $key . '":["' . implode('", "', $this->{$key}) . '"]}}';
                    } else {
                        $filterItems[] = '{"term":{"' . $key . '":"' . $value . '"}}';
                    }
                }
            }

            if (!empty($filterRangeItems)) {
                foreach ($filterRangeItems as $field => $params) {
                    $rangeConditions = [];

                    if (isset($params['from']) && $params['from']) {
                        $rangeConditions[] = '"gte":' . $params['from'];
                    }

                    if (isset($params['to']) && $params['to']) {
                        $rangeConditions[] = '"lte":' . $params['to'];
                    }

                    $filterItems[] = '{"range":{"' . $field . '":{' . implode(',', $rangeConditions) . '}}}';
                }
            }

            $query = '{"bool":{"must":[' . implode(',', $mustItems) . '], "filter":[' . implode(',', $filterItems) . ']}}';
        } else {
            $query = '{}';
        }

        // пагинация
        $pageSize = $this->pageSize ?? Yii::$app->params['elastic_page_size'];
        $page = $this->page ??  1;
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
                $result[] = $item;
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