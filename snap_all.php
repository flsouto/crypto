<?php

$last_time_arr = [];

while(true){

    $data = json_decode(file_get_contents("https://api.hitbtc.com/api/2/public/ticker"),true);

    foreach($data as $row){

        $symbol = $row['symbol'];

        if(!is_dir($dir=__DIR__."/snaps/$symbol/")){
            mkdir($dir);
        }

        $time = strtotime($row['timestamp']);

        $last_time = $last_time_arr[$symbol] ?? null;

        if($last_time!=$time){

            $day = date('Y-m-d',$time);

            file_put_contents("$dir/$day.txt",$row['last']."|".$time.PHP_EOL,FILE_APPEND);
            file_put_contents("$dir/last.txt",$row['last']);

            $last_time_arr[$symbol] = $time;

        }


    }

    sleep(10);

}
