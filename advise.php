<?php

$symbol = "XEMBTC";

$array = [];

foreach(glob(__DIR__."/snaps/$symbol/*.txt") as $file){
    if(strstr($file,'last.txt')){
        continue;
    }
    $array[filemtime($file)] = $file;
}

krsort($array);

$file = current($array);

$interval = 60 * 5;

$values = [];

foreach(file($file) as $line){
    $line = trim($line);
    if(empty($line)){
        continue;
    }

    list($value, $time) = explode("|",$line);

    if(time()-$time <= $interval){
        $values[] = $value;
    }

}

$avg = array_sum($values) / count($values);

$high = [];
$low = [];

foreach($values as $value){
    if($value > $avg){
        $high[] = $value;
    }
}

foreach($values as $value){
    if($value < $avg){
        $low[] = $value;
    }
}

$avg_h = array_sum($high) / count($high);
$avg_l = array_sum($low) / count($low);

$funds = .027;
$amount = $funds / $avg_l;
$profit = $amount * ($avg_h-$avg_l) * 7000;

foreach(['avg_l','avg','avg_h','amount','profit'] as $k){
    echo $k.': '.sprintf('%.9F',$$k).PHP_EOL;
}