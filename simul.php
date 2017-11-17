<?php

require __DIR__.'/utils.php';

$status = 'buy';
$buy_at = null;
$sell_at = null;
$since = null;
$profit = 0;

file_put_contents(__DIR__."/simul.txt","");

while(true){


    if($status=='buy'){

        if($buy_at){

            $last = get_last();

            if((time()-$since) >= 60){
                $buy_at = null;
                $sell_at = null;
                continue;
            }
            
            if($last <= $buy_at){
                $status = 'sell';
                $since = time();
            }
            
        } else {
            
            $advice = get_advice();
            if($advice['score'] < 0){
                echo '!!!!!!!! ('.$advice['score'].')'.PHP_EOL;
                sleep(10);
                continue;
            }
            
            if(!empty($advice['avg_l']) && !empty($advice['avg_h'])){
                $buy_at = $advice['avg_l'];
                $sell_at = $advice['avg_h'];
                $profit = $advice['profit'];
                $since = time();
                echo '+++ '.$buy_at.PHP_EOL;
            }
            
        }
        

    } else if($status=='sell') {

        $last = get_last();

        if(time()-$since >= 60){
            $advice = get_advice();
            if($advice['avg_h'] < $sell_at && $advice['avg_h'] > $buy_at){
                $sell_at = $advice['avg_h'];
                $profit = calc_profit($buy_at, $sell_at);
                $since = time();
            }
        }
        
        echo '... '.$sell_at.' (since '.date('H:i',$since).' - current: '.$last.') '.PHP_EOL;

        if($last >= $sell_at){
            
            echo '--- '.$sell_at.PHP_EOL;
            $status = 'buy';
            $buy_at = null;

            file_put_contents(__DIR__."/simul.txt",$profit.PHP_EOL,FILE_APPEND);
            
        } else {
            sleep(10);
        }
        
    }


}