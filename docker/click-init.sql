create database db;
create table db.test (
    t DateTime not null default now(),
    len UInt32 not null default 0
) ENGINE = TinyLog();