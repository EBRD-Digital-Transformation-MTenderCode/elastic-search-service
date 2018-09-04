<?php
namespace rest\components\dataProviders;

use yii\data\ArrayDataProvider;

/**
 * Class ArrayWithoutSortDataProvider
 * @package common\components\dataProviders
 */
class ArrayWithoutSortDataProvider extends ArrayDataProvider
{
    /**
     * @inheritdoc
     */
    protected function prepareModels()
    {
        if (($models = $this->allModels) === null) {
            return [];
        }

        return $models;
    }

    /**
     * @inheritdoc
     */
    protected function prepareTotalCount()
    {
        $count = 0;

        if (($pagination = $this->getPagination()) !== false) {
            $count = $pagination->totalCount;
        }

        return $count;
    }
}