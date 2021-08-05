<?php

include 'Connector.php';


if ($_GET){


    switch ($_GET['metod']) {

        case 'update_data':
            $res = Connector::update_all_information();
            echo '<pre>';
            print_r($res);
            break;
        case 'show_contacts':
            $res = Connector::show_contacts();
            print_r($res);
            break;

    }

}










?>
