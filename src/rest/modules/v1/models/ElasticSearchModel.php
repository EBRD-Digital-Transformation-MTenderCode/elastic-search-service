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
    const FROM_SUFFIX   = 'From';
    const TO_SUFFIX     = 'To';
    const CHAR_LIMIT    = 2;
    const DEBUG_DIVIDER = '__';

    const MATCHED_FIELDS = [
        'buyersRegions'                => 'buyerRegion',
        'proceduresOwnerships'         => 'procedureOwnership',
        'proceduresTypes'              => 'procedureType',
        'proceduresStatuses'           => 'procedureStatus',
        'budgetStatuses'               => 'budgetStatus',
        'periodPublished'              => 'publishedDate',
        'periodTender'                 => 'periodTenderFrom',
        'periodModification'           => 'modificationDate',
        'buyersIdentifiers'            => 'buyerIdentifier',
        'buyersTypes'                  => 'buyerType',
        'buyersMainGeneralActivities'  => 'buyerMainGeneralActivity',
        'buyersMainSectoralActivities' => 'buyerMainSectoralActivity',
        'pins'                         => 'pin',
    ];
    const PERIOD_FILEDS = [
        'periodDelivery',
        'periodEnquiry',
        'periodOffer',
        'periodAuction',
        'periodAward',
        'periodPlanning',
    ];
    const DEBUG_FIELDS = [
        'titlesOrDescriptions',
        'titlesOrDescriptionsStrict',
        'deliveriesRegions',
        'classifications',
        'publishedDate',
        'periodDeliveryFrom',
        'periodDeliveryTo',
        'periodEnquiryFrom',
        'periodEnquiryTo',
        'periodOfferFrom',
        'periodOfferTo',
        'periodAwardFrom',
        'periodAwardTo',
        'periodAuctionFrom',
        'periodAuctionTo',
        'buyersNames',
        'buyerIdentifier',
        'buyerType',
        'buyerMainGeneralActivity',
        'buyerMainSectoralActivity',
    ];

    public $pageSize;
    public $page;
    public $debug;

    protected $index;
    protected $type;
    protected $sortAttribute = 'modifiedDate';
    protected $sortOrder = 'desc';

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
        return ['page', 'pageSize', 'debug'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['pageSize', 'integer', 'min' => 1, 'max' => 100],
            ['page', 'integer', 'min' => 1],
            ['debug', 'boolean', 'trueValue' => 'true', 'falseValue' => 'false', 'strict' => true],
            ['debug', 'default', 'value' => 'false'],
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

        $sort = '"sort":[{"' . $this->sortAttribute . '":{"order": "' . $this->sortOrder . '"}}],';

        // формирование json для эластик
        if (!empty($searchAttributes)) {
            $mustItems = [];
            $shouldItems = [];
            $filterItems = [];
            $filterRangeItems = [];

            foreach ($searchAttributes as $key => $value) {
                $matchedKey = self::getMatchedKey($key);

                //полнотекстовый поиск
                if (in_array($key, $this->fieldsFullText())) {
                    $sort = '';

                    //json list to string convert
                    if (is_array($this->{$key})) {
                        $value = implode(' ', $this->{$key});
                    }

                    //  если выбрано строгое соответствие
                    $strict_mode = isset($this->{$key . self::STRICT_SUFFIX}) && ($this->{$key . self::STRICT_SUFFIX} == 'true');
                    if ($strict_mode) {
                        if (mb_strlen($value) > self::CHAR_LIMIT) {
                            //поиск по строке без умлаутов
                            $filteredUmlautsValue = self::filterUmlauts($value);

                            if (!$filteredUmlautsValue) {
                                $mustItems[] = '{"match_phrase":{"' . $matchedKey . self::STRICT_SUFFIX . '":"' . $value . '"}}';
                            } else {
                                $mustItems[] = '{"bool":{"should":[{"match_phrase":{"' . $matchedKey . self::STRICT_SUFFIX . '":"' . $value . '"}},{"match_phrase":{"' . $matchedKey . self::STRICT_SUFFIX . '":"' . $filteredUmlautsValue . '"}}]}}';
                            }
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

                        $value = implode(' ', $filteredWords);
                        $filteredUmlautsValue = self::filterUmlauts($value);

                        if ($filteredUmlautsValue) {
                            $value .= ' ' . $filteredUmlautsValue;
                        }

                        $mustItems[] = '{"match":{"' . $matchedKey . '":"' . $value . '"}}';

                        //поиск наилучшего совпадения
                        if (isset($this->{$key . self::STRICT_SUFFIX})) {
                            $shouldItems[] = '{"match":{"' . $matchedKey . self::STRICT_SUFFIX . '":"' . $value . '"}}';
                        }
                    }
                //поиск в диапазоне
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
                // поиск строк
                } elseif (!in_array($key, $this->fieldsSystem())) {
                    if (isset($this->{$key})) {
                        $terms = [];
                        $filteredUmlautsTerms = [];

                        if (is_array($this->{$key})) {
                            $terms = $this->{$key};
                        } else {
                            $terms[] = $value;
                        }

                        //поиск по строкам без умлаутов
                        foreach ($terms as $term) {
                            $filteredUmlautsTerm = self::filterUmlauts($term);

                            if ($filteredUmlautsTerm) {
                                $filteredUmlautsTerms[] = $filteredUmlautsTerm;
                            }
                        }

                        $filterItems[] = '{"terms":{"' . $matchedKey . '":["' . implode('", "', array_merge($terms, $filteredUmlautsTerms)) . '"]}}';
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

            $must = '"must":[' . implode(',', $mustItems) . ']';
            $should = '"should":[' . implode(',', $shouldItems) . ']';
            $filter = '"filter":[' . implode(',', $filterItems) . ']';
            $query = '{"bool":{' . $must . ',' . $should . ',' . $filter . '}}';
        } else {
            $query = '{"match_all":{}}';
        }

        // пагинация
        $pageSize = (int) ($this->pageSize ?? Yii::$app->params['elastic_page_size']);
        $page = (int) ($this->page ??  1);
        $pagination = '"from":' . ($page * $pageSize - $pageSize) . ',"size":' . $pageSize . ',';
        $data_string = '{' . $sort . $pagination . '"query":' . $query . '}';

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
                $item = [];

                if (is_array($hit['_source']) && !empty($hit['_source'])) {
                    foreach ($hit['_source'] as $fieldKey => $fieldValue) {
                        if (in_array($fieldKey, self::DEBUG_FIELDS)) {
                            if ($this->debug === 'true') {
                                $item[self::DEBUG_DIVIDER . $fieldKey] = $fieldValue;
                            }
                        } else {
                            $item[$fieldKey] = $fieldValue;
                        }
                    }
                }

                if ($this->debug === 'true') {
                    $item[self::DEBUG_DIVIDER . 'debug'] = true;
                    $item[self::DEBUG_DIVIDER . 'score'] = $hit['_score'] ?? 0;
                }

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

    /**
     * Make words without romanian umlauts
     *
     * Ă=A, ă=a
     * Â=I, â=i (исключение: слова которые содержат "român", тогда Â=A, â=a)
     * Î=I, î=i
     * Ș=S, ș=s ş=s
     * Ț=T, ț=t ţ=t
     *
     * @param $value
     * @return string
     */
    private static function filterUmlauts($value)
    {
        $filteredValue = str_replace([
            'Român',
            'român',
            'Ă',
            'Â',
            'Î',
            'Ș',
            'Ş',
            'Ț',
            'Ţ',
            'ă',
            'â',
            'î',
            'ș',
            'ş',
            'ț',
            'ţ',
        ], [
            'Roman',
            'roman',
            'A',
            'I',
            'I',
            'S',
            'S',
            'T',
            'T',
            'a',
            'i',
            'i',
            's',
            's',
            't',
            't',
        ], $value);

        if ($filteredValue != $value) {
            return $filteredValue;
        } else {
            return '';
        }
    }
}