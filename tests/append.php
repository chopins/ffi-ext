<?php

use Toknot\FFIExtend;
include_once __DIR__ . '/../src/FFIExtend.php';

if(PHP_ZTS) {
    define('PHP_FFI_EXTEND_APPEND_CDEF', 'void *tsrm_get_ls_cache(void);');
    $c = new FFIExtend;
    var_dump($c->getffi()->tsrm_get_ls_cache());
} else {
    define('PHP_FFI_EXTEND_APPEND_CDEF','HashTable* ZEND_FASTCALL _zend_new_array_0(void);');
    $c = new FFIExtend;
    var_dump($c->getffi()->_zend_new_array_0()->nNumOfElements);
}