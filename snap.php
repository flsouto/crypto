<?php

require __DIR__.'/utils.php';

$config = get_config();

$last_time = null;

$symbol = $config["symbol"];

if(!is_dir($dir=__DIR__."/snaps/$symbol/")){
    mkdir($dir);
}

while(true){

    $row = json_decode(file_get_contents('https://api.hitbtc.com/api/2/public/ticker/'.$symbol),true);

    $time = strtotime($row['timestamp']);

    if($last_time!=$time){

        $day = date('Y-m-d',$time);

        file_put_contents("$dir/$day.txt",$row['last']."|".$time.PHP_EOL,FILE_APPEND);
        file_put_contents("$dir/last.txt",$row['last']);

    }

    sleep(10);

}