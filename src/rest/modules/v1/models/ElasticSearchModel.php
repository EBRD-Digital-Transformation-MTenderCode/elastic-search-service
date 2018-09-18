<?php
namespace rest\modules\v1\models;

use Yii;
use yii\base\Model;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use rest\components\dataProviders\ArrayWithoutSortDataProvider;
use ustudio\service_mandatory\ServiceException;

/**
 * Class ElasticSearchModel
 * @package rest\modules\v1\models
 */
class ElasticSearchModel extends Model
{
    const STRICT_SUFFIX = 'Strict';
    const FROM_SUFFIX = 'From';
    const TO_SUFFIX = 'To';
    const PERIOD_FROM_SUFFIX = 'PeriodFrom';
    const PERIOD_TO_SUFFIX = 'PeriodTo';
    const CHAR_LIMIT = 2;

    public $pageSize;
    public $page;

    protected $index;
    protected $type;

    /**
     * Get fulltext search attributes
     * @return array
     */
    public static function fieldsFullText()
    {
        return [];
    }

    /**
     * Get range search attributes
     * @return array
     */
    public static function fieldsRange()
    {
        return [];
    }

    /**
     * Get disabled for search attributes
     * @return array
     */
    public static function fieldsSystem()
    {
        return ['page', 'pageSize'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
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
        if (!empty($searchAttributes)) {
            $mustItems = [];
            $filterItems = [];
            $filterRangeItems = [];

            foreach ($searchAttributes as $key => $value) {
                if (in_array($key, $this->fieldsFullText())) {
                    //json list to string convert
                    if (is_array($this->{$key})) {
                        $value = implode(' ', $this->{$key});
                    }

                    //  если выбрано строгое соответствие
                    $strict_mode = isset($this->{$key . self::STRICT_SUFFIX}) && ($this->{$key . self::STRICT_SUFFIX} == 'true');
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
                } elseif (in_array($key, $this->fieldsRange())) {
                    $from = strpos($key, self::FROM_SUFFIX);
                    $to = strpos($key, self::TO_SUFFIX);
                    $periodFrom = strpos($key, self::PERIOD_FROM_SUFFIX);
                    $periodTo = strpos($key, self::PERIOD_TO_SUFFIX);

                    if ($periodFrom) {
                        $filterRangeItems[$key]['from'] = $value;
                    }

                    if ($periodTo) {
                        $filterRangeItems[$key]['to'] = $value;
                    }

                    if ($from && !$periodFrom) {
                        $filterRangeItems[substr($key, 0, $from)]['from'] = $value;
                    }

                    if ($to && !$periodTo) {
                        $filterRangeItems[substr($key, 0, $to)]['to'] = $value;
                    }
                } elseif (!in_array($key, $this->fieldsSystem())) {
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
                        $rangeConditions[] = '"gte":"' . $params['from'] . '"';
                    }

                    if (isset($params['to']) && $params['to']) {
                        $rangeConditions[] = '"lte":"' . $params['to'] . '"';
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