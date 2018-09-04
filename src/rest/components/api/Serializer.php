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
    public $meta = '_meta';

    /**
     * @inheritdoc
     */
    public function serialize($data) {
        $serializer = new BaseSerializer();
        $serializer->collectionEnvelope = $this->collectionEnvelope;

        if (isset($data->query) && isset($data->query->indexBy) && $data->query->indexBy) {
            $serializer->preserveKeys = true;
        }

        $data = $serializer->serialize($data);

        $dataResult = [
            'code' => $this->response->getStatusCode(),
            'status' => $this->response->statusText,
            'data' => $data,
        ];

        if (is_array($data) && isset($data[$this->collectionEnvelope])) {
            $dataResult['data'] = $data[$this->collectionEnvelope] ?? null;
            $dataResult['_meta'] = $data[$this->meta] ?? null;
        }

        return $dataResult;
    }
}