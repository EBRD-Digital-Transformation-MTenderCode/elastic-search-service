<?php
use yii\db\Connection;

return [
    'components' => [
        'db' => [
            'class' => Connection::class,
            'dsn' => 'pgsql:host={{environments_DB_HOST}};port=5432;dbname={{environments_DB_NAME}}',
            'username' => '{{environments_DB_USERNAME}}',
            'password' => '{{environments_DB_PASSWORD}}',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
    ],
];
