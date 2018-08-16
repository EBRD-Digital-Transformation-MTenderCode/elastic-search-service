<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-backend',
    'language'=>'ru',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'backend\controllers',
    'components' => [
        'request' => [
            'enableCsrfValidation' => false,
            'csrfParam' => '_csrf-backend',
            'baseUrl' => '',
            'class' => 'common\components\urlManager\LangRequest',
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
            'errorAction' => 'site/error',
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],

            'class'=>'common\components\urlManager\LangUrlManager',
            'languages'=>['ru','en','uk'],
            'default_language'=>'ru',
            'langParam'=>'language',
        ],

    ],
    'modules' => [
        'swagger' => [
            'class' => 'gbksoft\modules\swagger\Module',
            'swaggerPath' => __DIR__ . '/../web/json/swagger.json',
            'on beforeJson' => function($event) {},
        ],
    ],

    'params' => $params,
];
