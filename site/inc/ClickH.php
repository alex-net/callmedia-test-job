<?php

use ClickHouseDB\Client;

/**
 * класс для работы с базой ClickHouse
 */
class ClickH extends DbBase
{
    private $db;

    public function __construct()
    {
        $this->db = new Client([
            'host' => getenv('CLICK_HOST'),
            'port' => getenv('CLICK_PORT'),
            'username' => getenv('CLICK_USER'),
            'password' => getenv('CLICK_PASS')
        ]);

        $this->db->database(getenv('CLICK_DBNAME'));
    }

    /**
     *
     * Добавление данных в таблицу
     *
     * @param      array  $arr    набор даннных ..
     */
    public function addRows($arr)
    {
        $this->db->insert('test', $arr, ['t', 'len']);
    }

    /**
     * Выборка статистики по таблице
     */
    public function getStatictic()
    {
        $stmt = $this->db->select(static::QUERY);
        return $stmt->rows();
    }
}