<?php

use Toknot\FFIExtend;

include_once __DIR__ . '/../src/FFIExtend.php';

FFIExtend::$usePhpImplement= true;
$c = new FFIExtend;
$rt = $c->argsPtr(3, ['a===', 'bcs', 'esfsa']);
$type2 = FFI::typeof($rt);
var_dump($type2);
var_dump($c->getCTypeName($type2));

var_dump($c->is64());