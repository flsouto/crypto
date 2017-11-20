<?php

require __DIR__.'/utils.php';

$status = 'buy';
$buy_at = null;
$sell_at = null;
$since = null;
$profit = 0;
$min_profit = .1;
$order_b = [];
$order_s = [];

file_put_contents(__DIR__."/simul.txt","");

while(true){

    if($status=='buy'){

        if(!$buy_at){

            // STEP 1 - PLACE BUY ORDER
            
            $advice = get_advice();
            
            // TOO MANY FALLS
            if($advice['score'] < 0){
                echo '!!!!!!!! ('.$advice['score'].')'.PHP_EOL;
                sleep(10);
                continue;
            }

            // NOT WORTH IT
            if($advice['profit'] < $min_profit){
                echo "<<<<< $min_profit (".$advice['profit'].')'.PHP_EOL;
                sleep(10);
                continue;
            }

            // ADVICE OK
            if(!empty($advice['avg_l']) && !empty($advice['avg_h'])){
                
                // ADD ORDER
                $order_b = add_order('buy',$advice['amount'],$buy_at);
                if(!empty($order_b['clientOrderId'])){
                    $buy_at = $advice['avg_l'];
                    $sell_at = $advice['avg_h'];
                    $profit = $advice['profit'];
                    $since = time();
                    echo '+++ '.$buy_at.PHP_EOL;
                } else {
                    echo 'Could not place b order. Output was: '.print_r($order_b,1).PHP_EOL;
                    sleep(10);
                    continue;
                }

            }
            
        } else {

            // STEP 2 - CHECK BUY ORDER IS FILLED
            
            // TOO LONG WATING...
            if((time()-$since) >= 60){

                // CANCEL, GO TO PREVIOUS STEP
                cancel_order($order_b['clientOrderId']);
                $buy_at = null;
                $sell_at = null;
                continue;
            }
            
            $last = get_last();
            
            // LAST PRICE IS AS EXPECTED?
            if($last <= $buy_at){

                // HAS THE ORDER BEEN FILLED ?            
                $check = get_order($order_b['clientOrderId']);
                if($check['status']=='filled'){
                    
                    // ADD SELL ORDER
                    $order_s = add_order('sell',$advice['amount'],$sell_at);
                    if(!empty($order_s['clientOrderId'])){
                        
                        // GO TO STEP 3
                        $status = 'sell';
                        $since = time();
                        
                    } else {
                        
                        echo 'Could not place s order. Output was: '.print_r($order_s,1);
                        sleep(10);
                        continue;
                    }
                }

            }
            
        }
        

    } else if($status=='sell') {
        
        // STEP 3 - CHECK SELL ORDER IS FILLED

        // TOO LONG WATING?
        if(time()-$since >= 60){

            // TRY NEW ADVICE
            $advice = get_advice();

            // BUT ONLY IF STILL REASONABLE/PROFITABLE
            if($advice['avg_h'] < $sell_at && $advice['avg_h'] > $buy_at && calc_profit($buy_at, $advice['avg_h']) >= $min_profit){

                // ORDER STATUS HASNT CHANGED?
                $check = get_order($order_s['clientOrderId']);
                if($check['status']=='new'){

                    // CANCEL & PLACE NEW ONE
                    cancel_order($order_s['clientOrderId']);
                    echo 'cancelled '.PHP_EOL;
                    $sell_at = $advice['avg_h'];
                    $since = time();
                    $profit = calc_profit($buy_at, $sell_at);
                    $tmp = add_order('sell', $advice['amount'], $sell_at)
                    if(!empty($tmp['clientOrderId'])){
                        // FINE
                        $order_s = $tmp;
                        echo 'replaced '.PHP_EOL;
                    } else {
                        echo 'Could not replace. Output was: '.print_r($tmp,1).PHP_EOL;
                    }
                }
            }
        }

        // PRINT OUT STATUS
        $last = get_last();
        echo '... '.$sell_at.' (since '.date('H:i',$since).' - current: '.$last.') '.PHP_EOL;

        // IS CURRENT PRICE WITHIN EXPECTATIONS?
        if($last >= $sell_at){

            // CHECK IF ORDER HAS FILLED
            $check = get_order($order_s['clientOrderId']);

            if($check['status']=='filled'){

                // DONE!!
                echo '--- '.$sell_at.PHP_EOL;

                // GOT BACK TO STEP 1
                $status = 'buy';
                $buy_at = null;
                $order_s = [];
                $order_b = [];

                file_put_contents(__DIR__."/balance.txt",get_balance('BTC').PHP_EOL,FILE_APPEND);

            } else {
                sleep(5);
            }
            
        } else {
            sleep(5);
        }
        
    }


}