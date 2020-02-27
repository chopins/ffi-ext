<?php

use Toknot\PhpApi;
use Toknot\ReflectionCFunction;
include_once __DIR__ . '/../src/PhpApi.php';
include_once __DIR__ . '/../src/ReflectionCFunction.php';

$ffi = FFI::cdef("int printf(const char *format, ...);
    typedef unsigned int time_t;
    typedef unsigned int suseconds_t;
 
    typedef struct timeval {
        time_t      tv_sec;
        suseconds_t tv_usec;
    } te;
 
    struct timezone {
        int tz_minuteswest;
        int tz_dsttime;
    };
 
    int gettimeofday(struct timeval *tv, struct timezone *tz); ", "libc.so.6");

$c = new PhpApi;
$r = $c->hasCFunc($ffi, 'printf');
var_dump('==== has printf():', $r);

$r = $c->hasCFunc($ffi, 'gettimeofday');
var_dump('==== has gettimeofday():', $r);

$rf = new ReflectionCFunction($ffi, 'gettimeofday');

$n = $rf->getNumberOfParameters();
var_dump('==== gettimeofday() num args:', $n);

var_dump('==== gettimeofday() is variadic:', $rf->isVariadic());

$type = $rf->getReturnType();
var_dump('==== gettimeofday() return type:',$type);

var_dump($rf->getParameters());

$te = $ffi->type('te***');

var_dump('==== te type:', $c->getCTypeName($te));
