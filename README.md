# ocds-index-updater
Данный сервис служит для обновления/реиндексации данных в Elastic из базы данных postgres.
Для запуска скрипта необходимо выполнить одну из команд: 

./yii reindex-elastic/all
./yii reindex-elastic/budgets
./yii reindex-elastic/tenders
./yii reindex-elastic/plans

./yii mapping-elastic/all
./yii mapping-elastic/budgets
./yii mapping-elastic/tenders
./yii mapping-elastic/plans