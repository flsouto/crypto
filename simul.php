<?php

$status = 'buy';
$buy_at = null;
$sell_at = null;
$since = null;
$profit = 0;

file_put_contents(__DIR__."/simul.txt","");

while(true){


    if($status=='buy'){

        if($buy_at){

            do{
                $last = file_get_contents(__DIR__.'/snaps/XEMBTC/last.txt');
            } while(empty($last));
            
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
            
            $output = `php advise.php`;
            $advice = [];
            foreach(explode("\n",$output) as $line){
                if(empty($line)){
                    continue;
                }
                list($k,$v) = explode(":",$line);
                $advice[$k] = $v;
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

        do{
            $last = file_get_contents(__DIR__.'/snaps/XEMBTC/last.txt');
        } while(empty($last));

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