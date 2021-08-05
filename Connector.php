<?php

include 'DB.php';
DB::$debug = 1; // on/off db_debug_mode

class Connector
{

    private static $connect_url = 'http://api.oppo-gdu.ru/'; //точка входа
//    private static $sections = ['news', 'photo-albums']; // категории для списков и обновления данных
    private static $sections = ['news'];


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

    //обновление данных в БД
    public static function reload_section_data()
    {
        //для результатов обновления
        $status = [];

        //идем по категориям
        foreach (self::$sections as $section) {

            $data = self::curl_get_contents(self::$connect_url . $section);

            if ($data['output'] && $data['info']['http_code'] == '200') {

                //тут получаем нужные поля для загрузки из БД
                $need_section_items = [];
                $db_fields = DB::fast_query('DESCRIBE ' . $section);

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

                    //проверяем есть ли такая запись в базе
                    $in_base = DB::fast_query('SELECT * FROM ' . $section . ' WHERE id =' . $section_item['id']);

                    //или добовляем или обновляем
                    if ($in_base) {

                        unset($prepare_data_arr['id']);//убираем id

                        $upd_query = '';

                        foreach ($prepare_data_arr as $name => $val) {

                            $upd_query .= $name . '=' . $val . ',';

                        }
                        $upd_query = substr($upd_query,0,-1);

                        $upd_query = "UPDATE $section SET $upd_query WHERE id = $section_item[id]";
                        DB::fast_query($upd_query);


                    } else {

                        $names = implode(' , ', array_keys($prepare_data_arr)); //имена полей
                        $vals = implode(' , ', array_values($prepare_data_arr)); // их значения

                        $ins_query = "INSERT INTO $section ($names) VALUES ($vals)";
                        DB::fast_query($ins_query);
                    }

                }

                $status[] = [$section => 'Обновлено успешно'];
            } else {

                $status[] = [$section => 'Ошибка обновления-code:' . $data['info']['http_code']];

            }


        }

        return $status;

    }

}