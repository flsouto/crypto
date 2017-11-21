<?php
require __DIR__.'/utils.php';

$currency = $argv[1] ?? 'BTC';

echo get_balance($currency);
echo PHP_EOL;