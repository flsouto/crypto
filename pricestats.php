<?php

require 'utils.php';

$conf = get_config();

$symbol = $conf['symbol'];

$files = [date("Y-m-d").".txt", date('Y-m-d',strtotime('-1 day')).".txt"];

$values = [];
foreach($files as $file){
	$content = file_get_contents(__DIR__."/snaps/$symbol/$file");
	foreach(explode("\n", $content) as $line){
		if(empty($line)){
			continue;
		}
		$parts = explode("|", $line);
		if(empty($parts[1])){
			continue;
		}
		$values[] = $parts[0];
	}
}

$avg = array_sum($values) / count($values);
$last = get_last();

echo 'avg: '.sprintf("%.".$conf['tick_size']."f", $avg).PHP_EOL;
echo 'cur: '.$last.PHP_EOL;
echo 'dif: '.sprintf("%.".$conf['tick_size']."f", abs($avg-$last)).PHP_EOL;


