<?php

namespace rest\modules\v1;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'rest\modules\v1\controllers';
    public static $version = 'v1';
    public $controllerMap = [
        'default'    => 'ustudio\swagger_parser\SwaggerController',
    ];

    public function init()
    {
        parent::init();
        // custom initialization code goes here
    }


}