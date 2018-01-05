<?php

require 'utils.php';

$balance = get_intended_balance();
if(!empty($argv[1]) && $argv[1]!='*'){
	$profit_btc = $balance - $argv[1];
} else {
	$profit_btc = $balance;
}

$profit_btc -= .0007;

if(!empty($argv[2])){
	$row['last'] = $argv[2];
} else {
	$row = json_decode(file_get_contents('https://api.hitbtc.com/api/2/public/ticker/BTCUSD'),true);
}

$profit_usd = $row['last'] * $profit_btc;

echo 'BTC: '.sprintf('%.9F',$profit_btc).PHP_EOL;
echo 'USD: '.sprintf('%.9F',$profit_usd).PHP_EOL;

$brl_rate = json_decode(file_get_contents("https://api.fixer.io/latest?symbols=BRL&base=USD"),true)['rates']['BRL'];

$profit_brl = $profit_usd * $brl_rate;
$profit_brl *= 0.95;
$profit_brl -= 15;

echo 'BRL: '.sprintf('%.9F',$profit_brl).PHP_EOL;
