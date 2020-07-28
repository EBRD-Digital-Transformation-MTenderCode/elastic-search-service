<?php

namespace rest\modules\v1\controllers;

use rest\components\api\Controller;
use rest\modules\v1\controllers\actions\Tenders\ElasticSearchAction;

class TendersController extends Controller
{

    public function actions()
    {
        return [
            'search' => [
                'class' => ElasticSearchAction::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return [
        ];
    }

}