<?php

/**
 * php-ffi-extend (http://toknot.com)
 *
 * @copyright  Copyright (c) 2020 Szopen Xiao (Toknot.com)
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/ffi-ext
 * @version    0.1
 */

namespace Toknot;

use FFI;
use FFI\CData;
use FFI\CType;
use TypeError;
use RuntimeException;

class FFIExtend
{

    public static bool $usePhpImplement = false;
    private static $ffi = null;
    private static $cffi = null;
    private static bool $HAVE_LONG_DOUBLE = false;
    private static array $ZEND_FFI_TYPE_KIND = [];

    const ZEND_FFI_SYM_TYPE = 0;
    const ZEND_FFI_SYM_VAR = 2;
    const ZEND_FFI_SYM_FUNC = 3;
    const ZEND_FFI_TYPE_OWNED = 1 << 0;
    const ZEND_FFI_ATTR_VARIADIC = 1 << 2;
    const ZEND_FFI_ATTR_INCOMPLETE_ARRAY = 1 << 3;
    const ZEND_FFI_ATTR_VLA = (1 << 4);
    const ZEND_FFI_ATTR_UNION = 1 << 5;
    const IS_UNDEF = 0;
    const IS_NULL = 1;
    const IS_FALSE = 2;
    const IS_TRUE = 3;
    const IS_LONG = 4;
    const IS_DOUBLE = 5;
    const IS_STRING = 6;
    const IS_ARRAY = 7;
    const IS_OBJECT = 8;
    const IS_RESOURCE = 9;
    const IS_REFERENCE = 10;
    const IS_CONSTANT_AST = 11;
    const IS_CALLABLE = 12;
    const IS_ITERABLE = 13;
    const IS_VOID = 14;

    private static $IS_INDIRECT = 12;
    private static $IS_PTR = 13;
    private static $IS_ALIAS_PTR = 14;

    const _IS_ERROR = 15;
    const _IS_BOOL = 16;

    private static $_IS_NUMBER = 17;

    public function __construct()
    {
        if (self::$ffi === null) {
            $this->checkDLLEnv();
            $this->versionMacro();
            $this->initPhpApi();
        }
    }

    private function checkDLLEnv()
    {
        if (getenv('PHP_DLL_FILE_PATH') && !defined('PHP_DLL_FILE_PATH')) {
            define('PHP_DLL_FILE_PATH', getenv('PHP_DLL_FILE_PATH'));
        }
        defined('PHP_DLL_FILE_PATH') || define('PHP_DLL_FILE_PATH', false);
        if (PHP_DLL_FILE_PATH && !file_exists(PHP_DLL_FILE_PATH)) {
            throw new RuntimeException('PHP DLL file "' . PHP_DLL_FILE_PATH . '" not exists');
        }
    }

    public function is64() {
        if(PHP_INT_SIZE === 8) {
            return true;
        } else {
            $psize = FFI::sizeof(FFI::new('void *'));
            if($psize === 8) {
                return true;
            }
            return false;
        }
    }

    public function getffi()
    {
        return self::$ffi;
    }

    protected function ZEND_FFI_TYPE_KIND($name)
    {
        return array_search("ZEND_FFI_TYPE_$name", self::$ZEND_FFI_TYPE_KIND);
    }

    protected function versionMacro()
    {
        if (PHP_MAJOR_VERSION < 8) {
            self::$IS_INDIRECT = 13;
            self::$IS_PTR = 14;
            self::$IS_ALIAS_PTR = 15;
            self::$_IS_NUMBER = 20;
        }
        $this->ffiTypeArray();
    }

    protected function ffiTypeArray()
    {
        try {
            if (FFI::sizeof(FFI::type('long double')) > 8) {
                self::$HAVE_LONG_DOUBLE = true;
            } else {
                self::$HAVE_LONG_DOUBLE = false;
            }
        } catch (FFI\ParserException $e) {
            self::$HAVE_LONG_DOUBLE = false;
        }
        self::$ZEND_FFI_TYPE_KIND = [
            'ZEND_FFI_TYPE_VOID',
            'ZEND_FFI_TYPE_FLOAT',
            'ZEND_FFI_TYPE_DOUBLE'
        ];
        if (self::$HAVE_LONG_DOUBLE) {
            self::$ZEND_FFI_TYPE_KIND[] = 'ZEND_FFI_TYPE_LONGDOUBLE';
        }
        self::$ZEND_FFI_TYPE_KIND = array_merge(self::$ZEND_FFI_TYPE_KIND, [
            'ZEND_FFI_TYPE_UINT8',
            'ZEND_FFI_TYPE_SINT8',
            'ZEND_FFI_TYPE_UINT16',
            'ZEND_FFI_TYPE_SINT16',
            'ZEND_FFI_TYPE_UINT32',
            'ZEND_FFI_TYPE_SINT32',
            'ZEND_FFI_TYPE_UINT64',
            'ZEND_FFI_TYPE_SINT64',
            'ZEND_FFI_TYPE_ENUM',
            'ZEND_FFI_TYPE_BOOL',
            'ZEND_FFI_TYPE_CHAR',
            'ZEND_FFI_TYPE_POINTER',
            'ZEND_FFI_TYPE_FUNC',
            'ZEND_FFI_TYPE_ARRAY',
            'ZEND_FFI_TYPE_STRUCT',
        ]);
    }

    private function initPhpApi()
    {
        $bitSize = PHP_INT_SIZE * 8;
        $code = "typedef int{$bitSize}_t zend_long;typedef uint{$bitSize}_t zend_ulong;typedef int{$bitSize}_t zend_off_t;";

        $code .= file_get_contents(__DIR__ . '/php.h');
        if (strcasecmp(PHP_OS_FAMILY, 'Windows') === 0) {
            $code = str_replace('ZEND_FASTCALL', '__vectorcall', $code);
            $phpDll = $this->findPhpDll();
            self::$ffi = FFI::cdef($code, $phpDll);
        } else {
            $code = str_replace('ZEND_FASTCALL', '__attribute__((fastcall))', $code);
            if (PHP_DLL_FILE_PATH) {
                self::$ffi = FFI::cdef($code, PHP_DLL_FILE_PATH);
            } else {
                self::$ffi = FFI::cdef($code);
            }
        }

        $this->setZendffi();
    }

    protected function findPhpDll()
    {
        if (PHP_DLL_FILE_PATH) {
            return PHP_DLL_FILE_PATH;
        }
        $f = '/php7' . (PHP_ZTS ? 'ts' : '') . '.dll';
        $p = dirname(PHP_BINARY) . $f;
        if (file_exists($p)) {
            return $p;
        }
        $p = dirname(ini_get('extension_dir')) . $f;
        if (file_exists($p)) {
            return $p;
        }
        $p = PHP_BINDIR . $f;
        if (file_exists($p)) {
            return $p;
        }
        $p = PHP_LIBDIR . $f;
        if (file_exists($p)) {
            return $p;
        }
        throw new RuntimeException('Can not found php.dll');
    }

    protected function setZendffi()
    {
        $obj = $this->zval(self::$ffi);
        self::$cffi = self::$ffi->cast('zend_ffi*', $obj);
    }

    public function castSameType(FFI $ffi, &$arg)
    {
        if ($arg instanceof FFI\CData) {
            $typeStruct = \FFI::typeof($arg);
            $type = $this->getCTypeName($typeStruct);
            $arg = $ffi->cast($type, $arg);
        }
    }

    public function zvalValue(CData $zval)
    {
        switch ($this->Z_TYPE($zval)) {
            case self::IS_LONG:
                return $zval->value->lval;
            case self::IS_DOUBLE:
                return $zval->value->dval;
            case self::IS_STRING:
                return $zval->value->str;
            case self::IS_ARRAY:
                return $zval->value->arr;
            case self::IS_OBJECT:
                return $zval->value->obj;
            case self::IS_RESOURCE:
                return $zval->value->res;
            case self::IS_FALSE:
                return false;
            case self::IS_TRUE:
                return true;
            case self::IS_NULL:
                return NULL;
            case self::IS_REFERENCE:
                return $zval->value->ref;
            case self::IS_CONSTANT_AST:
                return $zval->value->ast;
            case self::$IS_PTR:
            case self::$IS_ALIAS_PTR:
                return $zval->value->ptr;
            case self::$IS_INDIRECT:
                return $zval->value->zv;
            default:
                throw new TypeError('unknown type');
        }
    }

    public function zval($v)
    {
        $ex = self::$ffi->zend_rebuild_symbol_table();
        $sym = self::$ffi->zend_array_dup($ex);
        return $this->zvalValue($sym->arData->val);
    }

    public function emalloc($size)
    {
        return self::$ffi->cast('void*', self::$ffi->new("char[$size]"), false);
    }

    public function sizeof($ffi, $type)
    {
        return FFI::sizeof($ffi->type($type));
    }

    public function cast2Size(CData $data, $ffi = null)
    {
        $f = $ffi ?? self::$ffi;
        $s = $f->new('size_t');
        if (FFI::sizeof($s) > FFI::sizeof($data)) {
            return $s->cdata = $data->cdata;
        } else {
            return $f->cast('size_t', $data);
        }
    }

    public function ZSTR_VAL(CData $str)
    {
        return $str->val;
    }

    public function ZSTR_LEN(CData $str)
    {
        return $str->len;
    }

    public function getCTypeName(CType $type)
    {
        $cdata = $this->zval($type);
        $ffiCData = self::$ffi->cast('zend_ffi_cdata*', $cdata);
        $typeCData = $this->ZEND_FFI_TYPE($ffiCData[0]->type);
        return $this->getCTypeCDataName($typeCData);
    }

    public function Z_TYPE(CData $zval)
    {
        return $zval->u1->v->type;
    }

    public function getZStr(CData $zval)
    {
        return FFI::string($this->ZSTR_VAL($zval));
    }

    public function Z_OBJ_P(CData $obj)
    {
        return $obj[0]->value->obj;
    }

    protected function getCTypeCDataName(FFI\CData $type)
    {
        $is_ptr = false;
        $buf = '';
        $name = '';
        while (1) {
            switch ($type[0]->kind) {
                case $this->ZEND_FFI_TYPE_KIND('VOID'):
                    $name = 'void';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('FLOAT'):
                    $name = 'float';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('DOUBLE'):
                    $name = 'double';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('UINT8'):
                    $name = 'uint8_t';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('SINT8'):
                    $name = 'int8_t';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('UINT16'):
                    $name = 'uint16_t';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('SINT16'):
                    $name = 'int16_t';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('UINT32'):
                    $name = 'uint32_t';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('SINT32'):
                    $name = 'int32_t';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('UINT64'):
                    $name = 'uint64_t';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('SINT64'):
                    $name = 'int64_t';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('ENUM'):
                    if (!$this->isNull($type[0]->enumeration->tag_name)) {
                        $tagname = $type[0]->enumeration->tag_name;
                        $buf = $this->getZStr($tagname) . $buf;
                    } else {
                        $buf = '<anonymous>' . $buf;
                    }
                    $name = 'enum ';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('BOOL'):
                    $name = 'bool';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('CHAR'):
                    $name = 'char';
                    break;
                case $this->ZEND_FFI_TYPE_KIND('POINTER'):
                    $buf = '*' . $buf;
                    $is_ptr = 1;
                    $type = $this->ZEND_FFI_TYPE($type[0]->pointer->type);
                    break;
                case $this->ZEND_FFI_TYPE_KIND('FUNC'):
                    if ($is_ptr) {
                        $is_ptr = 0;
                        $buf = '(' . $buf . ')';
                    }
                    $buf .= '()';
                    $type = $this->ZEND_FFI_TYPE($type[0]->func[0]->ret_type);
                    break;
                case $this->ZEND_FFI_TYPE_KIND('ARRAY'):
                    if ($is_ptr) {
                        $is_ptr = 0;
                        $buf = "($buf)";
                    }
                    $buf .= '[';
                    if ($type->attr & self::ZEND_FFI_ATTR_VLA) {
                        $buf .= '*';
                    } else if (!($type->attr & self::ZEND_FFI_ATTR_INCOMPLETE_ARRAY)) {
                        $buf .= $type[0]->array->length;
                    }
                    $buf .= ']';
                    $type = $this->ZEND_FFI_TYPE($type[0]->array->type);
                    break;
                case $this->ZEND_FFI_TYPE_KIND('STRUCT'):
                    if ($type[0]->attr & self::ZEND_FFI_ATTR_UNION) {
                        if (!$this->isNull($type[0]->record->tag_name)) {
                            $tagname = $type[0]->record->tag_name;
                            $buf = $this->getZStr($tagname) . $buf;
                        } else {
                            $buf = '<anonymous>' . $buf;
                        }
                        $name = "union ";
                    } else {
                        if (!$this->isNull($type[0]->record->tag_name)) {
                            $tagname = $type[0]->record->tag_name;
                            $buf = $this->getZStr($tagname) . $buf;
                        } else {
                            $buf = '<anonymous>' . $buf;
                        }
                        $name = "struct ";
                    }
                    break;
                default:
                    if (self::$HAVE_LONG_DOUBLE && $type[0]->kind == $this->ZEND_FFI_TYPE_KIND('LONGDOUBLE')) {
                        $name = 'long double';
                        break;
                    }
                    assert_options(ASSERT_BAIL, 1);
                    assert(0);
            }
            if ($name) {
                break;
            }
        }
        return "$name{$buf}";
    }

    public function isNull(?CData $v)
    {
        return $v === null || FFI::isNull($v);
    }

    public function zend_hash_find_ptr(CData $zendArrayPtr, CData $name, string $type)
    {
        $v = self::$ffi->zend_hash_find($zendArrayPtr, $name);
        if ($this->isNull($v)) {
            return NULL;
        }
        $p = self::Z_PTR_P($v);
        return self::$ffi->cast($type, $p);
    }

    public function zend_hash_num_elements(CData $zendArrayPtr)
    {
        $a = self::$ffi->cast('zend_array*', $zendArrayPtr);
        return $a[0]->nNumOfElements;
    }

    protected function findSymobl(FFI $ffi, string $symName, $symType)
    {
        $zendObj = $this->zval($ffi);
        $zendStr = $this->zval($symName);
        $zffi = self::$ffi->cast('zend_ffi*', $zendObj);
        if ($this->isNull($zffi->symbols)) {
            return null;
        }
        $sym = $this->zend_hash_find_ptr($zffi->symbols, $zendStr, 'zend_ffi_symbol*');
        if ($this->isNull($sym) || $sym[0]->kind !== $symType) {
            return null;
        }
        return $sym;
    }

    public function hasCFunc(FFI $ffi, string $name)
    {
        $sym = $this->findSymobl($ffi, $name, self::ZEND_FFI_SYM_FUNC);
        return !$this->isNull($sym);
    }

    public function hasCVariable(FFI $ffi, string $name)
    {
        $sym = $this->findSymobl($ffi, $name, self::ZEND_FFI_SYM_VAR);
        return !$this->isNull($sym);
    }

    public function hasCType(FFI $ffi, string $type)
    {
        $sym = $this->findSymobl($ffi, $type, self::ZEND_FFI_SYM_TYPE);
        return !$this->isNull($sym);
    }

    public static function Z_PTR_P(CData $zval)
    {
        return $zval[0]->value->ptr;
    }

    public function ZEND_FFI_TYPE($t)
    {
        return self::$ffi->cast(
            'zend_ffi_type*',
            self::$ffi->cast('uintptr_t', $t)->cdata & ~self::ZEND_FFI_TYPE_OWNED
        );
    }

    public function castAllSameType(FFI $ffi, array &$args)
    {
        foreach ($args as &$v) {
            $this->castSameType($ffi, $v);
        }
    }

    public function iteratorZendArray(CData $hashTable, callable $callable)
    {
        for ($i = 0; $i < $hashTable[0]->nNumUsed; $i++) {

            $p = $hashTable[0]->arData + $i;
            if ($this->Z_TYPE($p[0]->val) == self::IS_UNDEF) {
                continue;
            }
            $v = $this->zvalValue($p[0]->val);
            $data = FFI::addr($p)[0]->val;
            if ($this->Z_TYPE($data) == self::$IS_INDIRECT) {
                $data = $this->zvalValue($data);
                if ($this->Z_TYPE($data) == self::IS_UNDEF) {
                    continue;
                }
            }
            if (!$this->isNull($p->key)) {
                $callable($this->getZStr($p->key), $v);
            } else {
                $callable($p->h, $v);
            }
        }
    }

    /**
     * PHP array to C Data of char*[] type, PHP array to char**
     *
     * @param integer $argc   number of elements in given array
     * @param array $argv   given array
     * @return CData
     */
    public function argsPtr(int $argc, array $argv): CData
    {
        $p = self::$ffi->new("char *[$argc]", false);
        foreach ($argv as $i => $arg) {
            $p[$i] = $this->strToCharPtr($arg);
        }
        $a = FFI::addr($p);
        return FFI::cast('char**', $a);
    }

    /**
     * PHP string to C char* type
     *
     * @param string $string
     * @return CData
     */
    public function strToCharPtr(string $string): CData
    {
        $charArr = $this->strToCharArr($string);
        return FFI::cast('char*', FFI::addr($charArr));
    }

    /**
     * PHP string to  C Data of char[] type, php string to C char[]
     *
     * @param string $string
     * @return CData
     */
    public function strToCharArr(string $string): CData
    {
        $len = strlen($string);
        $charArr = self::$ffi->new("char[$len]", false);
        for ($i = 0; $i < $len; $i++) {
            $char = self::$ffi->new('char', false);
            $char->cdata = $string[$i];
            $charArr[$i] = $char;
        }
        return $charArr;
    }
}
