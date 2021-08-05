<?php

include 'DB.php';
DB::$debug = 1; // on/off db_debug_mode

class Connector
{

    private static $connect_url = 'http://api.oppo-gdu.ru/'; //точка входа
    private static $sections = ['news', 'photo-albums']; // категории для списков и обновления данных


    // для получения данных траницы
    private static function curl_get_contents($url)
    {
        $response = [];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, '1');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, '1');

        // Execute the curl session
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);

        $response['output'] = $output;
        $response['info'] = $info;

        return $response;

    }

    //для вызова обновления всей информаци
    public static function update_all_information(){

        $result = [];

        $result[] = self::reload_section_data();
        $result[] = self::reload_news_content();
        $result[] = self::reload_photo();

        return $result;

    }


    //обновление данных категорий в БД
    private static function reload_section_data()
    {
        //для результатов обновления
        $status = [];

        //идем по категориям
        foreach (self::$sections as $section) {

            $data = self::curl_get_contents(self::$connect_url . $section);

            if ($data['output'] && $data['info']['http_code'] == '200') {

                //тут получаем нужные поля для загрузки из БД
                $need_section_items = [];
                $db_fields = DB::fast_query('DESCRIBE `' . $section . '`');

                foreach ($db_fields as $db_field) {

                    $need_section_items[] = $db_field['Field'];

                }

                //то что загрузили по категории
                $loaded_section_items = json_decode($data['output'], true)['data'];

                //идем по каждому элементу раздела
                foreach ($loaded_section_items as $section_item) {

                    $prepare_data_arr = []; // массив для загрузки в БД

                    //собираем нужные поля
                    foreach ($need_section_items as $num => $property) {

                        $prepare_data_arr[$property] = '\'' . $section_item[$property] . '\'';

                    }

                    $pre_query = '';

                    foreach ($prepare_data_arr as $name => $val) {

                        $pre_query .= $name . '=' . $val . ',';

                    }

                    $pre_query = substr($pre_query, 0, -1);

                    $names = implode(' , ', array_keys($prepare_data_arr)); //имена полей
                    $vals = implode(' , ', array_values($prepare_data_arr)); // их значения

                    $res_query = "INSERT INTO `$section` ($names) VALUES ($vals) ON DUPLICATE KEY UPDATE $pre_query";

                    DB::fast_query($res_query);

                }

                $status[] = [$section => 'Обновлено успешно'];

            } else {

                $status[] = [$section => 'Ошибка обновления-code:' . $data['info']['http_code']];

            }


        }

        return $status;

    }


    //обновление контента новостей
    private static function reload_news_content()
    {
        //для результатов обновления
        $status = [];

        //получаем все id новостей из БД
        $ids_news = DB::fast_query("SELECT `id` FROM `news`");

        foreach ($ids_news as $id_news) {

            $data = self::curl_get_contents(self::$connect_url . 'news/' . $id_news['id']);

            if ($data['output'] && $data['info']['http_code'] == '200') {
                $news_content = json_decode($data['output'], true)['data']['content'];

                DB::fast_query("INSERT INTO `news_content` (`news_id`,`content`) VALUES ($id_news[id],'$news_content') ON DUPLICATE KEY UPDATE content='$news_content'");

                $status[] = ['id новости - ' . $id_news['id'] => 'Обновлено успешно'];

            } else {
                $status[] = ['id новости - ' . $id_news['id'] => 'Ошибка обновления-code:' . $data['info']['http_code']];
            }

        }

        return $status;

    }

    //обновление фотографий
    private static function reload_photo()
    {
        //для результатов обновления
        $status = [];

        //получаем все id альбомов из БД
        $ids_albums = DB::fast_query("SELECT `id` FROM `photo-albums`");

        foreach ($ids_albums as $id_album) {

            $data = self::curl_get_contents(self::$connect_url . 'photo-albums/' . $id_album['id']);

            if ($data['output'] && $data['info']['http_code'] == '200') {
                $photo_content = json_decode($data['output'], true)['data']['photos'];

                foreach ($photo_content as $photo) {

                    DB::fast_query("INSERT INTO `photos` (`id`,`image`,`thumb`,`created_at`,`albums_id`) VALUES ('$photo[id]','$photo[image]','$photo[thumb]','$photo[created_at]','$id_album[id]') ON DUPLICATE KEY UPDATE `image`='$photo[image]',`thumb`='$photo[thumb]',`created_at`='$photo[created_at]'");

                }

                $status[] = ['id альбома - ' . $id_album['id'] => 'Обновлено успешно'];

            } else {

                $status[] = ['id альбома - ' . $id_album['id'] => 'Ошибка обновления-code:' . $data['info']['http_code']];

            }

        }

        return $status;


    }


    //получение контактных данных
    public static function show_contacts(){

        return (self::curl_get_contents(self::$connect_url.'contacts')['output']);

    }

}