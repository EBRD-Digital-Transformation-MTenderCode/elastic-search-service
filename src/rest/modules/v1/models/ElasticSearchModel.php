<?php


namespace rest\modules\v1\models;

use rest\components\dataProviders\ArrayWithoutSortDataProvider;
use yii\base\Model;
use yii\httpclient\Exception;
use yii\httpclient\Client;
use ustudio\service_mandatory\ServiceException;
use Yii;

class ElasticSearchModel extends Model
{
    const STRICT_SUFFIX = '_strict';
    const CHAR_LIMIT = 2;

    public $pageSize;
    public $page;
    public $search_strict;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pageSize', 'page'], 'integer', 'min' => 1],
            [['search_strict'], 'boolean'],
            [['search_strict'], 'default', 'value' => 0],
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
        $this->setAttributes(Yii::$app->request->get());

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
            $matches = [];

            foreach ($searchAttributes as $key => $value) {
                //  если выбрано строгое соответствие
                $strict_mode = isset($this->{$key . self::STRICT_SUFFIX}) && $this->{$key . self::STRICT_SUFFIX};
                if ($strict_mode) {
                    if (mb_strlen($value) > self::CHAR_LIMIT) {
                        $matches[] = '{"match_phrase":{"' . $key . '":"' . $value . '"}}';
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

                    $matches[] = '{"match":{"' . $key . '":"' . implode(' ', $filteredWords) . '"}}';
                }
            }

            $query = '{"bool":{"must":[' . implode(',', $matches) . ']}}';
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