<?php

/**
 * класс работы с базой Mysql
 */
class Mysql extends DbBase
{
    /**
     * объект PDO
     */
    private $pdo;

    public function __construct()
    {
        $this->pdo = new PDO('mysql:host=' . getenv('MYSQL_HOST') . ';dbname=' . getenv('MYSQL_DBNAME'), getenv('MYSQL_USER'), getenv('MYSQL_PASS'));
    }

    /**
     * преобразование набора данных
     *
     * @param      array  $arr    Данные для вставки в таблицу
     *
     * @return     array  Преобразованный набор даннных
     */
    private function prepareData($arr)
    {
        $arr = array_map('array_values', $arr);
        $arr = call_user_func_array('array_merge', $arr);
        return $arr;
    }

    /**
     *
     * Добавление данных в таблицу
     *
     * @param      array  $arr    набор даннных ..
     */
    public function addRows($arr)
    {
        $arr = $this->prepareData($arr);
        $q = 'insert into test (t, len) values ' . implode(', ', array_fill(0, count($arr) / 2, '(?, ?)')) ;
        $stmt = $this->pdo->prepare($q);
        $stmt->execute($arr);
    }

    /**
     * Выборка статистики по таблице
     */
    public function getStatictic()
    {
        $stmt = $this->pdo->query(static::QUERY);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}