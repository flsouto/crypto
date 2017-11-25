<?php

function get_config(){
	static $config = [];
	if(empty($config)){
		$config = json_decode(file_get_contents(__DIR__."/config.json"),true);
	}
	return $config;
}

function assert_snap_is_running(){
	$config = get_config();
	$filemtime = filemtime("snaps/".$config['symbol']."/last.txt");
	if(time() - $filemtime > 15){
		die("Looks like the snap.php script is not running.\n");
	}
}

function get_last(){
	$config = get_config();
    do{
        $last = file_get_contents(__DIR__.'/snaps/'.$config['symbol'].'/last.txt');
    } while(empty($last));
    return $last;
}

function get_advice(){
    $output = `php advise.php`;
    $advice = [];
    foreach(explode("\n",$output) as $line){
        if(empty($line)){
            continue;
        }
        list($k,$v) = explode(":",$line);
        $advice[$k] = trim($v);
    }
    return $advice;
}

function calc_profit($low, $high){
    $config = get_config();
    $funds = $config['funds'];
    $fees = $funds * .1 / 100;
    $amount = $funds / $low;
    $profit = $amount * ($high-$low);
    $fees += $profit * .1 / 100;
    $profit -= $fees;
    $profit *= 7000;
    return $profit;
}

function get_balance($currency){
	$conf = get_config();
	$cmd = 'curl -X GET -u "'.$conf['api_key'].':'.$conf['secret_key'].'" "https://api.hitbtc.com/api/2/trading/balance"';
	$output = shell_exec($cmd);
	$data = json_decode($output,true);
	foreach($data as $row){
		if($row['currency']==$currency){
			return $row['available'];
		}
	}
}


function add_order($type, $amount, $price){
	$config = get_config();
	$api_key = $config['api_key'];
	$secret_key = $config['secret_key'];
	$symbol = $config['symbol'];

	$cmd = <<<CMD
curl -X POST -u "$api_key:$secret_key" "https://api.hitbtc.com/api/2/order" -d 'symbol=$symbol&side=$type&quantity=$amount&price=$price'
CMD;

	$output = shell_exec($cmd);
	return json_decode($output, true);
}

function get_order($oid){
	$config = get_config();
	$api_key = $config['api_key'];
	$secret_key = $config['secret_key'];

	$cmd = <<<CMD
	curl -X GET -u "$api_key:$secret_key" \
     "https://api.hitbtc.com/api/2/order/$oid"
CMD;
	$output = shell_exec($cmd);
	return json_decode($output, true);

}

function is_order_filled($oid){
	$result = get_order($oid);
	return !empty($result['error']) && $result['error']['code']=='20002';
}

function is_order_idle($oid){
	$result = get_order($oid);
	return isset($result['status']) && $result['status']=='new';
}

function cancel_order($oid){
	
	$conf = get_config();
	$api_key = $conf['api_key'];
	$secret_key = $conf['secret_key'];

	$cmd = <<<CMD
curl -X DELETE -u "$api_key:$secret_key" \
    "https://api.hitbtc.com/api/2/order/$oid"    
CMD;
	
	$output = shell_exec($cmd);

	return json_decode($output,true);
}

function get_orders(){
	$config = get_config();
	$api_key = $config['api_key'];
	$secret_key = $config['secret_key'];

	$cmd = <<<CMD
	curl -X GET -u "$api_key:$secret_key" \
     "https://api.hitbtc.com/api/2/order"
CMD;
	$output = shell_exec($cmd);
	return json_decode($output, true);

}

function get_intended_balance(){

	$orders = get_orders();
	$balance = get_balance('BTC');

	$last = get_last();

	foreach($orders as $o){
		/*
		if($o['side']!='sell'){
			continue;
		} */

		$value = ($o['quantity'] * $o['price']);
		$fee = $value * .1 /100;
		$balance += $value;
		$balance -= $fee;

	}

	return $balance;

}

function get_real_balance(){

	$orders = get_orders();
	$balance = get_balance('BTC');

	$last = get_last();

	foreach($orders as $o){
		if($o['side']!='sell'){
			continue;
		}
		$balance += ($o['quantity'] * $last);
	}

	return $balance;

}