<?php

require_once "vendor/autoload.php";

$receiver = new DataReceiver();


//Ставим ключ с таймаутом
$receiver->set('key','test_value',2);
$start = microtime(true);


while(microtime(true) - $start < 10){
    if(time() % 3 == 0){
        $v = mt_rand(0,100);
        print_r("new value assigned to redis = $v<br>");

        //Насильно пишем в редис
        $receiver->getClient()->set('key',$v);
    }

    print_r(round(microtime(true) - $start,4)." -  ".$receiver->get('key'));
    print_r("<br>");
}




