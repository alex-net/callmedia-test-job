<?php

/**
 * базовый класс работы с базой данных
 */
abstract class DbBase
{
    /**
     * текст запроса статистики..
     *
     * @var        string
     */
    const QUERY = 'select distinct
        minute(t) as minute,
        count(*) over w as col,
        round(avg(len) over w, 3) as avglen,
        first_value(t) over w as firstmsg,
        last_value(t) over w as lastmsg
        from test
        window w as (partition by minute(t))
        order by t asc';

    /**
     *
     * Добавление данных в таблицу
     *
     * @param      array  $arr    набор даннных ..
     */
    abstract public function addRows($arr);

    /**
     * Выборка статистики по таблице
     */
    abstract public function getStatictic();
}