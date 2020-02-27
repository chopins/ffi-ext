# php-capi
Call PHP C API

# Reference

* class `Toknot\PhpApi` of method
   1. `__construct()`
   2. `phpffi() : ffi` : get ffi
   3. `castSameType(FFI $ffi, &$arg)`   cast to same type
   4. `zvalValue(FFI\CData $zval) : CData`     get value of php C zval struct
   5. `phpVar($var): CData`  transform php value to php C zval
   6. `ZSTR_VAL(CData $str) : CData`  php C `ZSTR_VAL` macro
   7. `ZSTR_LEN(CData $str) : int`  php C `ZSTR_LEN` macro
   8. `getCTypeName(CType $type):string`  get C type name
   9. `Z_TYPE(CData $type) : int` php C `Z_TYPE` macro
   10. `getZStr(CData $str) : string`  get string from string zval 
   11. `Z_OBJ_P(CData $obj)` php C `Z_OBJ_P` macro, `$obj` must be pointer
   12. `isNull($v) : bool`  check `$v` whether is `NULL` or C `NULL`
   13. `zend_hash_find_ptr(CData $zendArrayPtr, CData $name, string $type): CData` find a value from php C hash array, and cast to `$type`
   14. `zend_hash_num_elements(CData $array) : int` get number of php C HashTable
   15. `hasCFunc(FFI $ffi, string $name) : bool`  check given function name whether in given FFI
   16. `Z_PTR_P(CData $zval) : CData` php C `Z_PTR_P` macro, `$zval` must be pointer
   17. `ZEND_FFI_TYPE(CData $t) : CData`  php FFI of `ZEND_FFI_TYPE` macro
   18. `castAllSameType(FFI $ffi, array &$args)`   cast array of args to same type
   19. `iteratorZendArray(CData $hashTable, callable $callable)`  iterator a zend HastTable, `$hashTable` must be pointer, the `$callable` smailer to `function callback($key, $value)`
   20. `argsPtr(int $argc, array $argv): CData`  php `$argv` array to C `char**`
   21. `strToCharPtr(string $string): CData`    php string to C `char*`
   22. `strToCharArr(string $string): CData`  php string to C 'char[]`
* class `Toknot\ReflectionCFunction` of method
   1. `__construct(FFI $ffi, string $name)` check FFI `$ffi` whether has C function `$name`
   2. `getName()` get function name
   3. `getClosure() : Closure`
   4. `isVariadic() : bool`
   5. `getNumberOfParameters() : int`  get number of parameters
   6. `getReturnType(): string` get return type
   7. `getParameters(): array`  get parameters type list