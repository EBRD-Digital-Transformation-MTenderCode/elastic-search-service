<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

class DeleteTablesController extends Controller {

    public function actionInit() {

        if(Console::confirm('Вы точно хотите удалить все таблицы в базе?')) {

            Yii::$app->db->createCommand("SET foreign_key_checks = 0")->execute();
            $tables = Yii::$app->db->schema->getTableNames();
            //print_r($tables);die;

            foreach ($tables as $table) {

                Yii::$app->db->createCommand()->dropTable($table)->execute();

                $this->stdout($table . " удалена!\n", Console::FG_GREEN);

            }
            Yii::$app->db->createCommand("SET foreign_key_checks = 1")->execute();
            $this->stdout("Все таблицы удалены!\n", Console::BOLD);
        }
    }
}