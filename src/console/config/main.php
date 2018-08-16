<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php'),
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/params-local.php')
);

return [
    'id' => 'app-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'console\controllers',
    'controllerMap' => [
        'fixture' => [
            'class' => 'yii\console\controllers\FixtureController',
            'namespace' => 'common\fixtures',
          ],
        'config-service' => [
            'class' => 'ustudio\service_mandatory\ConfigServiceController',
        ]
    ],
    'components' => [
        'log' => [
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => 'ustudio\service_mandatory\components\ConsoleLog',
                    'categories' => ['console-msg'],
                    'exportInterval' => 1,
                    'logVars' => [],
                ],
            ],
        ],
        'errorHandler' => [
            'class'=>'ustudio\service_mandatory\ExceptionHandler',
        ],
    ],
    'params' => $params,

];
