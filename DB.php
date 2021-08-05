<?php


class DB
{

    private static $host = 'localhost';
    private static $db = 'mob_api';
    private static $user = 'root';
    private static $pass = '123456789';
    private static $charset = 'utf8';

    public static function fast_query($query){
        $dsn = "mysql:host=".self::$host.";dbname=".self::$db.";charset=".self::$charset;
        $pdo = new PDO($dsn, self::$user, self::$pass);
        return $pdo->query($query)->fetchAll();
    }

}