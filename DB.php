<?php


class DB
{
    private static $host = 'localhost';
    private static $db = 'mob_api';
    private static $user = 'root';
    private static $pass = '123456789';
    private static $charset = 'utf8';
    public static $debug = 0;

    public static function fast_query($query)
    {

        $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
        $pdo = new PDO($dsn, self::$user, self::$pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (self::$debug) {
            try {

                $result = $pdo->query($query);

                if (stripos($query, 'INSERT INTO') === false && stripos($query, 'UPDATE') === false) {
                    $result = $result->fetchAll(PDO::FETCH_ASSOC);
                    return $result;
                }
            } catch (PDOException $e) {
                $return = $query. " Your fail message: " . $e->getMessage();
                print_r($return);
                echo '<br>';
            }

        } else {
            $result = $pdo->query($query)->fetchAll();
            return $result;
        }


    }

}