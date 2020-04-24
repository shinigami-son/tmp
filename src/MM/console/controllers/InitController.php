<?php
/**
 * Created by PhpStorm.
 * User: max18
 * Date: 13.03.2020
 * Time: 10:54
 */

namespace console\controllers;


use MM\bot\models\ImageTokens;
use MM\bot\models\SoundTokens;
use MM\bot\models\UsersData;

class InitController
{
    public function createImageTokensTable()
    {
        return (new ImageTokens())->createTable();
    }

    public function createSoundTokensTable()
    {
        return (new SoundTokens())->createTable();
    }

    public function createUserDataTable()
    {
        return (new UsersData())->createTable();
    }

    public function dropImageTokensTable()
    {
        return (new ImageTokens())->dropTable();
    }

    public function dropSoundTokensTable()
    {
        return (new SoundTokens())->dropTable();
    }

    public function dropUserDataTable()
    {
        return (new UsersData())->dropTable();
    }

    public function init()
    {
        if ($this->createImageTokensTable()) {
            printf("Таблица \"\s\" успешно создана!\n", ImageTokens::TABLE_NAME);
        } else {
            printf("Не удалось создать таблицу \"\s\"!\n", ImageTokens::TABLE_NAME);
        }
        if ($this->createSoundTokensTable()) {
            printf("Таблица \"\s\" успешно создана!\n", SoundTokens::TABLE_NAME);
        } else {
            printf("Не удалось создать таблицу \"\s\"!\n", SoundTokens::TABLE_NAME);
        }
        if ($this->createUserDataTable()) {
            printf("Таблица \"\s\" успешно создана!\n", UsersData::TABLE_NAME);
        } else {
            printf("Не удалось создать таблицу \"\s\"!\n", UsersData::TABLE_NAME);
        }
    }
}
