<?php

require_once "vendor/autoload.php";

$receiver = new DataReceiver();


$receiver->getClient()->set('two','my two second key');
$receiver->setInterval('two',2);
$start = microtime(true);

for($i = 0; $i < 10; $i++){
    var_dump(microtime(true) - $start);
    var_dump($receiver->get('two'));
    sleep(1);
}




