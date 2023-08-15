create table test (
    t timestamp not null default current_timestamp comment 'время в очереди',
    len integer not null default 0 comment 'Длина контента',
    index (len),
    index (t)
);