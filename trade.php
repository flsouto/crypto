<?php

require __DIR__.'/utils.php';

function log_msg($msg){
    $msg .= '|'.date('Y-m-d H:i');
    file_put_contents(__DIR__."/logs.txt",$msg.PHP_EOL,FILE_APPEND);
}

function sell_remainder(){
    static $last_call = null;

    if($last_call && (time()-$last_call) < 180){
        return;
    }

    $last_call = time();

    $config = get_config();
    $currency = $config['currency'];
    $balance = get_balance($currency);
    if($balance){
        $highest = get_highest_price(60*60);
        if(!$highest){
            return false;
        }
        $o = add_order('sell',$balance,$highest);
        $msg = 'Selling remainder: '.$balance.' '.$currency.' at '.$highest;
        echo $msg.PHP_EOL;
        log_msg($msg);
        return $o;
    }
    return false;
}

function log_balance(){
    
    static $last_call = null;

    if($last_call && (time()-$last_call) < 60*30){
        return;
    }

    $last_call = time();

    $balance = get_intended_balance().'|'.date('Y-m-d H:i:s');

    file_put_contents(__DIR__.'/balance.txt',$balance.PHP_EOL,FILE_APPEND);

}

init:

$status = 'buy';
$buy_at = null;
$sell_at = null;
$since = null;
$profit = 0;
$min_profit = .001;
$order_b = [];
$order_s = [];

while(true){

    sell_remainder();
    log_balance();

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

                if($range = check_slot_taken($advice['avg_h'])){
                    $msg = "range is taken: ".$range." (value attempted: ".$advice['avg_h'].") ";
                    echo $msg.PHP_EOL;
                    log_msg($msg);
                    sleep(10);
                    continue;
                }


                // ADD ORDER
                $order_b = add_order('buy',$advice['amount'],$advice['avg_l']);
                if(!empty($order_b['clientOrderId'])){
                    $buy_at = $advice['avg_l'];
                    $sell_at = $advice['avg_h'];
                    $profit = $advice['profit'];
                    $since = time();
                    echo '+++ '.$buy_at.PHP_EOL;
                } else {
                    echo 'Could not place buy order. Output was: '.print_r($order_b,1).PHP_EOL;
                    sleep(10);
                    continue;
                }

            }
            
        } else {

            // STEP 2 - CHECK BUY ORDER IS FILLED
            
            // TOO LONG WATING...
            if((time()-$since) >= 60){

                $output = cancel_order($order_b['clientOrderId']);
                if(isset($output['error'])){
                    goto try_sell;
                } else {
                 // CANCELED, GO TO PREVIOUS STEP
                    $buy_at = null;
                    $sell_at = null;
                    continue;

                }
            }
            
            $last = get_last();
            
            // LAST PRICE IS AS EXPECTED?
            if($last <= $buy_at){

                // HAS THE ORDER BEEN FILLED ?   
                if(is_order_filled($order_b['clientOrderId'])){
                    try_sell:                
                    // ADD SELL ORDER
                    $order_s = add_order('sell',$advice['amount'],$sell_at);
                    if(!empty($order_s['clientOrderId'])){
                        
                        // GO TO STEP 3
                        $status = 'sell';
                        $since = time();
                        
                    } else {
                        
                        echo 'Could not place sell order. Output was: '.print_r($order_s,1);
                        goto init;
                    }
                }

            }
            
        }
        

    } else if($status=='sell') {
        
        // STEP 3 - CHECK SELL ORDER IS FILLED

        // TOO LONG WATING?
        if(time()-$since >= 600){
            // LEAVE IT
            $status = 'buy';
            $buy_at = null;
            $order_s = [];
            $order_b = [];
            continue;
        }

        if(time()-$since >= 60){

            // TRY NEW ADVICE
            $advice = get_advice();

            // BUT ONLY IF STILL REASONABLE/PROFITABLE
            if($advice['avg_h'] < $sell_at && $advice['avg_h'] > $buy_at && calc_profit($buy_at, $advice['avg_h']) >= $min_profit){

                // ORDER STATUS HASNT CHANGED?
                if(is_order_idle($order_s['clientOrderId'])){

                    // CANCEL & PLACE NEW ONE
                    cancel_order($order_s['clientOrderId']);
                    echo 'cancelled '.PHP_EOL;
                    $sell_at = $advice['avg_h'];
                    $since = time();
                    $profit = calc_profit($buy_at, $sell_at);
                    $tmp = add_order('sell', $order_s['quantity'], $sell_at);
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
            if(is_order_filled($order_s['clientOrderId'])){

                // DONE!!
                echo '--- '.$sell_at.PHP_EOL;

                // GOT BACK TO STEP 1
                $status = 'buy';
                $buy_at = null;
                $order_s = [];
                $order_b = [];

            } else {
                sleep(5);
            }
            
        } else {
            sleep(5);
        }
        
    }


}