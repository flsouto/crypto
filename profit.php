<?php

if(empty($argv[1])){
	die("Usage: <command> hours\n");
}

$hours = $argv[1];
if(strstr($hours,'*')){
	$hours = explode('*', $hours);
	$hours = $hours[0] * $hours[1];
}

$from_date = date('Y-m-d H:i:s',strtotime('- '.$hours.' hours'));

$first_value = null;
$last_value = null;

foreach(file(__DIR__."/balance.txt") as $line){
	$line = trim($line);
	if(empty($line)){
		continue;
	}
	list($value, $datetime) = explode("|",$line);
	if(empty($value)){
		continue;
	}
	if($datetime >= $from_date){
		if(!$first_value){
			$first_value = $value;
		} else {
			$last_value = $value;
		}
	}
}

$profit_btc = $last_value - $first_value;
$row = json_decode(file_get_contents('https://api.hitbtc.com/api/2/public/ticker/BTCUSD'),true);
$profit_usd = $row['last'] * $profit_btc;

echo 'BTC: '.sprintf('%.9F',$profit_btc).PHP_EOL;
echo 'USD: '.sprintf('%.9F',$profit_usd).PHP_EOL;

$brl_rate = json_decode(file_get_contents("https://api.fixer.io/latest?symbols=BRL&base=USD"),true)['rates']['BRL'];

$profit_brl = $profit_usd * $brl_rate;
echo 'BRL: '.sprintf('%.9F',$profit_brl).PHP_EOL;







