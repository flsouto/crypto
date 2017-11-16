<?php

require __DIR__.'/utils.php';

$config = get_config();

assert_snap_is_running();

$symbol = $config['symbol'];

$array = [];

foreach(glob(__DIR__."/snaps/$symbol/*.txt") as $file){
    if(strstr($file,'last.txt')){
        continue;
    }
    $array[filemtime($file)] = $file;
}

krsort($array);

$file = current($array);

$interval = $config['advisor_interval'];

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

if(empty($values)){
    die("Not enough data has been captured yet.\n");
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

if(empty($high)){
    die("Not enough data has been captured yet.\n");
}

if(empty($low)){
    die("Not enough data has been captured yet.\n");
}


$avg_h = array_sum($high) / count($high);
$avg_l = array_sum($low) / count($low);

$funds = $config['funds'];
$fees = $funds * .1 / 100;
$amount = $funds / $avg_l;
$profit = $amount * ($avg_h-$avg_l);
$fees += $profit * .1 / 100;
$profit -= $fees;
$profit *= 7000;


$rise = 0;
$fall = 0;
$len = count($values);
for($i=1;$i<$len;$i++){
    if($diff = $values[$i] - $values[$i-1]){
        if($diff<0){
            $fall += abs($diff);
        } else {
            $rise += $diff;
        }
    }
}

$score = $rise - $fall;

foreach(['avg_l','avg','avg_h','amount','rise','fall','score','profit'] as $k){
    echo $k.': '.sprintf('%.'.$config['tick_size'].'F',$$k).PHP_EOL;
}