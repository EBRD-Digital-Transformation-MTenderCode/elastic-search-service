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
    const CHAR_LIMIT = 2;
    const MATCHED_FIELDS = [
        'buyersRegions'                => 'buyerRegion',
        'proceduresTypes'              => 'procedureType',
        'proceduresStatuses'           => 'procedureStatus',
        'periodPublished'              => 'publishedDate',
        'periodTender'                 => 'periodTenderFrom',
        'buyersIdentifiers'            => 'buyerIdentifier',
        'buyersTypes'                  => 'buyerType',
        'buyersMainGeneralActivities'  => 'buyerMainGeneralActivity',
        'buyersMainSectoralActivities' => 'buyerMainSectoralActivity',
    ];
    const PERIOD_FILEDS = [
        'periodDelivery',
        'periodEnquiry',
        'periodOffer',
        'periodAuction',
        'periodAward',
    ];

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
                $matchedKey = self::getMatchedKey($key);

                if (in_array($key, $this->fieldsFullText())) {
                    //json list to string convert
                    if (is_array($this->{$key})) {
                        $value = implode(' ', $this->{$key});
                    }

                    //  если выбрано строгое соответствие
                    $strict_mode = isset($this->{$key . self::STRICT_SUFFIX}) && ($this->{$key . self::STRICT_SUFFIX} == 'true');
                    if ($strict_mode) {
                        if (mb_strlen($value) > self::CHAR_LIMIT) {
                            $mustItems[] = '{"match_phrase":{"' . $matchedKey . self::STRICT_SUFFIX . '":"' . $value . '"}}';
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

                        $mustItems[] = '{"match":{"' . $matchedKey . '":"' . implode(' ', $filteredWords) . '"}}';
                    }
                } elseif (in_array($key, $this->fieldsRange())) {
                    $from = strpos($key, self::FROM_SUFFIX);
                    $to = strpos($key, self::TO_SUFFIX);
                    $period = in_array($key, self::PERIOD_FILEDS);

                    if ($from) {
                        $filterRangeItems[substr($matchedKey, 0, $from)]['from'] = $value;
                    }

                    if ($to) {
                        $filterRangeItems[substr($matchedKey, 0, $to)]['to'] = $value;
                    }

                    if (is_array($this->{$key}) && count($this->{$key}) == 2) {
                        if (!empty($this->{$key}[0])) {
                            $filterRangeItems[($period ? $matchedKey . self::FROM_SUFFIX : $matchedKey)]['from'] = $this->{$key}[0];
                        }

                        if (!empty($this->{$key}[1])) {
                            $filterRangeItems[($period ? $matchedKey . self::TO_SUFFIX : $matchedKey)]['to'] = $this->{$key}[1];
                        }
                    }
                } elseif (!in_array($key, $this->fieldsSystem())) {
                    if (is_array($this->{$key})) {
                        $filterItems[] = '{"terms":{"' . $matchedKey . '":["' . implode('", "', $this->{$key}) . '"]}}';
                    } else {
                        $filterItems[] = '{"term":{"' . $matchedKey . '":"' . $value . '"}}';
                    }
                }
            }

            if (!empty($filterRangeItems)) {
                foreach ($filterRangeItems as $key => $params) {
                    $rangeConditions = [];

                    if (isset($params['from']) && $params['from']) {
                        $rangeConditions[] = '"gte":"' . $params['from'] . '"';
                    }

                    if (isset($params['to']) && $params['to']) {
                        $rangeConditions[] = '"lte":"' . $params['to'] . '"';
                    }

                    $filterItems[] = '{"range":{"' . $key . '":{' . implode(',', $rangeConditions) . '}}}';
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

    /**
     * Get matched field name
     * @param $key
     * @return mixed
     */
    protected static function getMatchedKey($key)
    {
        if (isset(self::MATCHED_FIELDS[$key])) {
            return self::MATCHED_FIELDS[$key];
        } else {
            return $key;
        }
    }
}