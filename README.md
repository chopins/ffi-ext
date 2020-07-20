[中文](#php-ffi-增强功能)
# php FFI Extend
Call PHP C API

php DLL(php7.dll,php7ts.dll) find order, if file exists loading:
  * like unix os, Usually does not require additional loading
  * in windows, find order:
    1. if undefine constant `PHP_DLL_FILE_PATH`, will find environment variable `PHP_DLL_FILE_PATH`
    2. find user constant `PHP_DLL_FILE_PATH`
    3. find directory of predefined constant `PHP_BINARY` (physical location, not `PHP_BINDIR`)
    4. find php parent directory of ext directory (physical location, not `PHP_EXTENSION_DIR`)
    5. find `PHP_BINDIR` directory
    6. find `PHP_LIBDIR` directory

**Note: constant `PHP_DLL_FILE_PATH` work for like unix OS**

# Reference

### class `Toknot\FFIExtend` of methods
|                方法                    |                            描述                          |
|---------------------------------------|---------------------------------------------------------|
| 1. `__construct()`                    |                                                         |
| 2. `phpffi() : ffi`                   | get ffi                                                 |
| 3. `emalloc($size) : CData`           | emalloc memory, and set 0                               |
| 4. `castSameType(FFI $ffi, &$arg)`    | cast to same type                                       |
| 5. `zvalValue(FFI\CData $zval) : CData` | get value of php C zval struct                        |
| 6. `zval($var): CData`                | get C zval(CData) from php variable($var, php string,int ...) |
| 7. `ZSTR_VAL(CData $str) : CData`     | php C `ZSTR_VAL` macro                                  |
| 8. `ZSTR_LEN(CData $str) : int`       | php C `ZSTR_LEN` macro                                  |
| 9. `getCTypeName(CType $type):string` | get C type name                                         |
| 10. `Z_TYPE(CData $type) : int`       | php C `Z_TYPE` macro                                    |
| 11. `getZStr(CData $str) : string`    | get string from string zval                             |
| 12. `Z_OBJ_P(CData $obj)`             | php C `Z_OBJ_P` macro, `$obj` must be pointer           |
| 13. `isNull($v) : bool`               | check `$v` whether is `NULL` or C `NULL`                |
| 14. `zend_hash_find_ptr(CData $zendArrayPtr, CData $name, string $type): CData` | find a value from php C hash array, and cast to `$type`                                                                   |
| 15. `zend_hash_num_elements(CData $array) : int` | get number of php C HashTable                |
| 16. `hasCFunc(FFI $ffi, string $name) : bool` | check given `$name` of function whether in given FFI `$ffi`                                                                                            |
| 17. `hasCVariable(FFI $ffi, string $name) : bool` | check given `$name` of variable  whether in given FFI `$ffi`                                                                                            |
| 18. `hasCType(FFI $ffi, string $name) : bool` | check given `$name` of c type  whether in given FFI `$ffi`                                                                                            |
| 19. `Z_PTR_P(CData $zval) : CData`    | php C `Z_PTR_P` macro, `$zval` must be pointer          |
| 20. `ZEND_FFI_TYPE(CData $t) : CData` | php FFI of `ZEND_FFI_TYPE` macro                        |
| 21. `castAllSameType(FFI $ffi, array &$args)` |  cast array of args to same type                |
| 22. `iteratorZendArray(CData $hashTable, callable $callable)` | iterator a zend HashTable, `$hashTable` must be pointer, the `$callable` smailer to `function callback($key, $value)`                     |
| 23. `argsPtr(int $argc, array $argv): CData` | php `$argv` array to C `char**`                  |
| 24. `strToCharPtr(string $string): CData`  |  php string to C `char*`                           |
| 25. `strToCharArr(string $string): CData` | php string to C `char[]`                            |
| 26. `is64(): bool`                    | check os whether is 64bit (whether is LP64)             |


### class `Toknot\ReflectionCFunction` of methods
|                方法                    |                            描述                         |
|---------------------------------------|---------------------------------------------------------|
| 1. `__construct(FFI $ffi, string $name)` | Reflection FFI `$ffi` of C function `$name`          |
| 2. `getName()`                        | get function name                                       |
| 3. `getClosure() : Closure`           |                                                         |
| 4. `isVariadic() : bool`              |                                                         |
| 5. `getNumberOfParameters() : int`    | get number of parameters                                |
| 6. `getReturnType(): string`          | get return type                                         |
| 7. `getParameters(): array`           | get parameters type list                                |


# PHP FFI 增强功能
PHP DDL(php7.dll,php7ts.dll，动态库)查找顺序，如果文件存在：
  * 对于类UNIX系统，通常是不需要指定加载。通常当PHP以其他程序的模块方式安装时需要指定
  * 对于windows
    1. 如果未定义常量`PHP_DLL_FILE_PATH`，将会使用环境变量 `PHP_DLL_FILE_PATH`定义的路径
    2. 根据常量 `PHP_DLL_FILE_PATH` 指定路径查找,（实际位置，非`PHP_BINDIR`)
    3. 根据PHP预定义常量`PHP_BINARY` 指定的路径查找 (实际位置，非`PHP_EXTENSION_DIR`)
    4. 在PHP扩展所在文件夹的上一层文件夹下查找
    5. 在`PHP_BINDIR`目录查找
    6. 在`PHP_LIBDIR`目录查找

**注意:  常量 `PHP_DLL_FILE_PATH` 在类UNIX系统下依然有效**

# 类方法参考列表

### 类 `Toknot\FFIExtend` 的方法
|                方法                    |                            描述                         |
|---------------------------------------|---------------------------------------------------------|
| 1. `__construct()`                    |                                                         |
| 2. `phpffi() : ffi`                   | 获得PHP C 接口的 FFI 类对象                                |
| 3. `emalloc($size) : CData`           | 分配内存，并设置为0                                        |
| 4. `castSameType(FFI $ffi, &$arg)`    | 转换任意其他FFI对象的C数据到指定FFI对象的同结构名字的C数据        |
| 5. `zvalValue(FFI\CData $zval) : CData` |    get value of php C zval struct                      |
| 6. `zval($var): CData`                | get C zval(CData) from php variable($var, php string,int ...)|
| 7. `ZSTR_VAL(CData $str) : CData`     | php C `ZSTR_VAL` macro                                    |
| 8. `ZSTR_LEN(CData $str) : int`       | php C `ZSTR_LEN` macro                                    |
| 9. `getCTypeName(CType $type):string`  | 获取指定CType的结构名字                                     |
| 10. `Z_TYPE(CData $type) : int`       | php C `Z_TYPE` macro                                      |
| 11. `getZStr(CData $str) : string`    | get string from string zval                               |
| 12. `Z_OBJ_P(CData $obj)`             | php C `Z_OBJ_P` macro, `$obj` must be pointer             |
| 13. `isNull($v) : bool`               | 检查 `$v` 是 PHP `NULL` 或 C `NULL`                         |
| 14. `zend_hash_find_ptr(CData $zendArrayPtr, CData $name, string $type): CData` | 从 PHP hashTable 中查找指定元数的值，并转换成指定类型   |
| 15. `zend_hash_num_elements(CData $array) : int` | 获取 php HashTable 元素的个数                      |
| 16. `hasCFunc(FFI $ffi, string $name) : bool` | 检测指定名字的函数是否存在与指定的FFI对象中               |
| 17. `hasCVariable(FFI $ffi, string $name) : bool` | 在FFI对象中，检查指定函数名字是否是可变函数           |
| 18. `hasCType(FFI $ffi, string $name) : bool` | 在FFI对象中，检查指定的类型名是否存在                     |
| 19. `Z_PTR_P(CData $zval) : CData`     | php C `Z_PTR_P` macro, `$zval` must be pointer             |
| 20. `ZEND_FFI_TYPE(CData $t) : CData`  | php FFI of `ZEND_FFI_TYPE` macro                           |
| 21. `castAllSameType(FFI $ffi, array &$args)` |  批量转换到指定FFI的同名数据结构                         |
| 22. `iteratorZendArray(CData $hashTable, callable $callable)` | 迭代一个Zend HashTable,`$hashTable`必须数指针，`$callable`原型必须类似`function callback($key, $value)`     |
| 23. `argsPtr(int $argc, array $argv): CData`  | 将PHP `$argv` 数组转换成C `char**`                    |
| 24. `strToCharPtr(string $string): CData`  |  将 PHP 字符串转换成C `char*`                            |
| 25. `strToCharArr(string $string): CData`  | 将 PHP 字符串转换成C `char[]`                            |
| 26. `is64(): bool`                     | 检查系统是否是64位(是否是64位数据模型)                          |

### 类 `Toknot\ReflectionCFunction` 的方法
|                方法                    |                            描述                         |
|---------------------------------------|---------------------------------------------------------|
| 1. `__construct(FFI $ffi, string $name)` | 反射指定 FFI `$ffi` 的 C 函数 `$name`                   |
| 2. `getName()`                        | 获取函数名                                                |
| 3. `getClosure() : Closure`           | 返回函数的闭包                                            |
| 4. `isVariadic() : bool`              | 是否是可变函数                                             |
| 5. `getNumberOfParameters() : int`    | 获取参数个数                                              |
| 6. `getReturnType(): string`          | 获取返回类型                                               |
| 7. `getParameters(): array`           | 获取参数类型列表                                             |