<?php
require 'utils.php';

$highest_symb = "";
$highest_val = -999;

foreach(scandir(__DIR__.'/snaps') as $symbol){
    $adv = get_advice($symbol);
    if(empty($adv['profit'])){
        continue;
    }
    if($adv['profit'] > $highest_val){
        $highest_symb = $symbol;
        $highest_val = $adv['profit'];
    }
}

echo $highest_symb.': '.$highest_val.PHP_EOL;