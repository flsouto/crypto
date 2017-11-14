<?php
class Advisor{

    var $buy_at;
    var $sell_at;
    var $amount;

    function refresh(){

    }

    function expired(){

    }



}

class Order{

    function cancel(){

    }

}

function add_order(){}
function get_order(){}
function get_balance(){}
function get_last(){}

if($o = get_order()){
    if($o->type=='buy'){
        $o->cancel();
    } else {
        die('trying to sell at ... since .... current: ....');
    }
}

$advisor = new Advisor();

while(true){

    $advisor->refresh();

    if($o = add_order('buy', $advisor->buy_at, $advisor->amount)){

        echo '+++ '.$advisor->buy_at."\n";

        while(true){

            if($advisor->expired()){
                $o->cancel();
                continue 2;
            }

            $balance = get_balance('XEM');

            if($balance < $advisor->amount){
                sleep(3);
                continue;
            }

            break;

        }

        $since = time();

        if(add_order('sell', $advisor->sell_at, $advisor->amount)){

            echo '... '.$advisor->sell_at.' (since '.date('H:i',$since).' - current: '.get_last().') '."\n";

            while(true){
                if(!get_balance('XEM')){
                    echo '--- '.$advisor->amount;
                    break;
                }
                sleep(5);
            }

        }

    }

}


/*





*/