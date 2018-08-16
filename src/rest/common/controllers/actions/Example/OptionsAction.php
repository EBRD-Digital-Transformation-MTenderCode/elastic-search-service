<?php

namespace rest\common\controllers\actions\Example;

use yii\rest\OptionsAction as BaseExampleOptionsAction;

/**
 * Class OptionsAction
 */
class OptionsAction extends BaseExampleOptionsAction
{
    /**
     * @inheritdoc
     */
    public $collectionOptions = ['POST', 'GET', 'DELETE', 'PATCH'];

    /**
     * @inheritdoc
     */
    public $resourceOptions = [];
}
