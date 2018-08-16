<?php

namespace rest\common\controllers;

use Yii;
use rest\components\api\Controller;
use rest\common\controllers\actions\Example\ViewAction;
use rest\common\controllers\actions\Example\ViewAllAction;
use rest\common\controllers\actions\Example\UpdateAction;
use rest\common\controllers\actions\Example\CreateAction;
use rest\common\controllers\actions\Example\DeleteAction;

class ExampleController extends Controller
{

    public function actions()
    {
        return [
            'view' => [
                'class' => ViewAction::class,
            ],
            'index' => [
                'class' => ViewAllAction::class,
            ],
            'update' => [
                'class' => UpdateAction::class,
            ],
            'create' => [
                'class' => CreateAction::class,
            ],
            'delete' => [
                'class' => DeleteAction::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function verbs()
    {
        return [
            'view' => ['GET'],
            'index' => ['GET'],
            'update' => ['PATCH'],
            'create' => ['POST'],
            'delete' => ['DELETE']
        ];
    }

}