<?php
require __DIR__.'/utils.php';

if(!empty($argv[1])){
	$btc = get_intended_balance();
} else {
	$btc = get_real_balance();
}

$row = json_decode(file_get_contents('https://api.hitbtc.com/api/2/public/ticker/BTCUSD'),true);

echo 'BTC: '.$btc.PHP_EOL;
echo 'USD: '.($btc*$row['last']).PHP_EOL;

