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
        $advice[$k] = $v;
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

