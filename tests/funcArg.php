<?php

use Toknot\FFIExtend;
use Toknot\ReflectionCFunction;
include_once __DIR__ . '/../src/FFIExtend.php';
include_once __DIR__ . '/../src/ReflectionCFunction.php';

$ffi = FFI::cdef("int printf(const char *format, ...);
    typedef unsigned int time_t;
    typedef unsigned int suseconds_t;
    typedef int error;
    typedef struct timeval {
        time_t      tv_sec;
        suseconds_t tv_usec;
    } te;
 
    struct timezone {
        int tz_minuteswest;
        int tz_dsttime;
    };
 int errno;
 typedef enum _zend_ffi_symbol_kind {
	ZEND_FFI_SYM_TYPE,
	ZEND_FFI_SYM_CONST,
	ZEND_FFI_SYM_VAR,
	ZEND_FFI_SYM_FUNC
} zend_ffi_symbol_kind;
    int gettimeofday(struct timeval *tv, struct timezone *tz); ", "libc.so.6");

$c = new FFIExtend;
$r = $c->hasCFunc($ffi, 'printf');
var_dump('has printf():', $r);
echo '**********************' .PHP_EOL;
$r = $c->hasCFunc($ffi, 'printfs');
var_dump('has printfs():', $r);
echo '**********************' .PHP_EOL;
$r = $c->hasCFunc($ffi, 'gettimeofday');
var_dump('has gettimeofday():', $r);
echo '**********************' .PHP_EOL;
$var = $c->hasCVariable($ffi, 'errno');
var_dump('has variable `errno`:', $var);
echo '**********************' .PHP_EOL;
$var2 = $c->hasCVariable($ffi, 'error');
var_dump('has variable `error`:', $var2);
echo '**********************' .PHP_EOL;
$enum1 = $c->hasCEnum($ffi, 'ZEND_FFI_SYM_TYPE');
var_dump('has enum ZEND_FFI_SYM_TYPE:', $enum1);
echo '**********************' .PHP_EOL;
$rf = new ReflectionCFunction($ffi, 'gettimeofday');

$n = $rf->getNumberOfParameters();
var_dump('gettimeofday() num args:', $n);
echo '**********************' .PHP_EOL;
var_dump('gettimeofday() is variadic:', $rf->isVariadic());
echo '**********************' .PHP_EOL;
$type = $rf->getReturnType();
var_dump('gettimeofday() return type:',$type);
echo '**********************' .PHP_EOL;
var_dump($rf->getParameters());
echo '**********************' .PHP_EOL;
$te = $ffi->type('te***');

var_dump('te type:', $c->getCTypeName($te));

