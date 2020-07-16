# php FFI Extend
Call PHP C API

php DLL(php7.dll,php7ts) find order, if file exists loading:
  * like unix os, Usually does not require additional loading
  * windows,  
    * first, find user constant `PHP_DLL_FILE_PATH`
    * second, find directory of predefined constant `PHP_BINARY`
    * third, find php parent directory of ext directory

__Note:  constant `PHP_DLL_FILE_PATH` work for like unix OS
# Reference

### class `Toknot\FFIExtend` of methods
 1. `__construct()`
 2. `phpffi() : ffi` : get ffi
 3. `emalloc($size) : CData`, emalloc memory, and set 0
 4. `castSameType(FFI $ffi, &$arg)`   cast to same type
 5. `zvalValue(FFI\CData $zval) : CData`     get value of php C zval struct
 6. `zval($var): CData`  get C zval(CData) from php variable($var, php string,int ...)
 7. `ZSTR_VAL(CData $str) : CData`  php C `ZSTR_VAL` macro
 8. `ZSTR_LEN(CData $str) : int`  php C `ZSTR_LEN` macro
 9. `getCTypeName(CType $type):string`  get C type name
 10. `Z_TYPE(CData $type) : int` php C `Z_TYPE` macro
 11. `getZStr(CData $str) : string`  get string from string zval 
 12. `Z_OBJ_P(CData $obj)` php C `Z_OBJ_P` macro, `$obj` must be pointer
 13. `isNull($v) : bool`  check `$v` whether is `NULL` or C `NULL`
 14. `zend_hash_find_ptr(CData $zendArrayPtr, CData $name, string $type): CData` find a value from php C hash array, and cast to `$type`
 15. `zend_hash_num_elements(CData $array) : int` get number of php C HashTable
 16. `hasCFunc(FFI $ffi, string $name) : bool`  check given `$name` of function whether in given FFI `$ffi`
 17. `hasCVariable(FFI $ffi, string $name) : bool`  check given `$name` of variable  whether in given FFI `$ffi`
 18. `hasCType(FFI $ffi, string $name) : bool`  check given `$name` of c type  whether in given FFI `$ffi`
 19. `Z_PTR_P(CData $zval) : CData` php C `Z_PTR_P` macro, `$zval` must be pointer
 20. `ZEND_FFI_TYPE(CData $t) : CData`  php FFI of `ZEND_FFI_TYPE` macro
 21. `castAllSameType(FFI $ffi, array &$args)`   cast array of args to same type
 22. `iteratorZendArray(CData $hashTable, callable $callable)`  iterator a zend HastTable, `$hashTable` must be pointer, the `$callable` smailer to `function callback($key, $value)`
 23. `argsPtr(int $argc, array $argv): CData`  php `$argv` array to C `char**`
 24. `strToCharPtr(string $string): CData`    php string to C `char*`
 25. `strToCharArr(string $string): CData`  php string to C 'char[]`
### class `Toknot\ReflectionCFunction` of methods
 1. `__construct(FFI $ffi, string $name)` Reflection FFI `$ffi` of C function `$name`
 2. `getName()` get function name
 3. `getClosure() : Closure`
 4. `isVariadic() : bool`
 5. `getNumberOfParameters() : int`  get number of parameters
 6. `getReturnType(): string` get return type
 7. `getParameters(): array`  get parameters type list
