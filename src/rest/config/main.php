<?php

use rest\components\api\UrlRule;

$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'), 
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'), 
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-api',
    'language' => 'en',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
            'acceptParams' => ['version' => 'v1']
        ],
        'request' => [
            'enableCsrfValidation' => false,
            'csrfParam' => '_csrf-api',
            'baseUrl' => '/rest-api',
            //'class' => 'common\components\urlManager\LangRequest',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'class'=>'ustudio\service_mandatory\ExceptionHandler',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => UrlRule::class,
                    'controller' => [
                        'v1/example'
                    ],
                    'extraPatterns' => [
                        'GET search' => 'search',
                    ],
                    'pluralize' => false
                ],
            ],
//            'class' => 'common\components\urlManager\LangUrlManager',
//            'languages' => ['en', 'uk', 'ru'],
//            'default_language' => 'en',
//            'langParam' => 'language',
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'fileMap' => [
                        'app/error' => 'error.php',
                    ],
                ],
            ],
        ],
    ],
    'controllerMap' => [
        'health' => [
            'class' => 'ustudio\service_mandatory\HealthController',
        ],
    ],
    'modules' => [
        'v1' => [
            'class' => 'rest\modules\v1\Module',
        ],
    ],
    'params' => $params,
];

