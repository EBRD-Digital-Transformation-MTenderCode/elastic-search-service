<?php
namespace rest\components\api;

use yii\rest\Serializer as BaseSerializer;

/**
 * Class Serializer
 */
class Serializer extends BaseSerializer
{

    /**
     * @inheritdoc
     */
    public $collectionEnvelope = 'items';

    /**
     * @inheritdoc
     */
    public function serialize($data)
    {
        $data = parent::serialize($data);

        $dataResult = [
            'code' => $this->response->getStatusCode(),
            'status' => $this->response->statusText,
            'data' => $data,
        ];

        if (is_array($data) && isset($data[$this->collectionEnvelope])) {
            $dataResult['data'] = $data[$this->collectionEnvelope];
        }

        return $dataResult;
    }
}