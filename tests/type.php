<?php

use Toknot\PhpApi;

include_once __DIR__ . '/../src/PhpApi.php';
$c = new PhpApi;
$rt = $c->argsPtr(3, ['a===', 'bcs', 'esfsa']);
$type2 = FFI::typeof($rt);
var_dump($type2);
var_dump($c->getCTypeName($type2));