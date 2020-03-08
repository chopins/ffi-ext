<?php

/**
 * php-capi (http://toknot.com)
 *
 * @copyright  Copyright (c) 2020 Szopen Xiao (Toknot.com)
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/php-capi
 * @version    0.1
 */

namespace Toknot;

use FFI;
use FFI\CData;
use FFI\CType;
use TypeError;

class FFIExtend
{

    public static bool $usePhpImplement = false;
    private static $phpapi = null;
    private static bool $HAVE_LONG_DOUBLE = false;
    private static array $ZEND_FFI_TYPE_KIND = [];
    private static bool $isWin = false;
    private $phpDll = '';

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
        if(self::$phpapi === null) {
            $this->versionMacro();
            $this->initPhpApi();
        }
    }

    public function getffi()
    {
        return self::$phpapi;
    }

    protected function ZEND_FFI_TYPE_KIND($name)
    {
        return array_search("ZEND_FFI_TYPE_$name", self::$ZEND_FFI_TYPE_KIND);
    }

    protected function versionMacro()
    {
        if(PHP_MAJOR_VERSION < 8) {
            self::$IS_INDIRECT = 13;
            self::$IS_PTR = 14;
            self::$IS_ALIAS_PTR = 15;
            self::$_IS_NUMBER = 20;
        }
    }

    private function initPhpApi()
    {
        $bitSize = PHP_INT_SIZE * 8;
        $code = "typedef int{$bitSize}_t zend_long;typedef uint{$bitSize}_t zend_ulong;typedef int{$bitSize}_t zend_off_t;";

        $code .= file_get_contents(__DIR__ . '/php.h');
        if(strcasecmp(PHP_OS_FAMILY, 'Windows') === 0) {
            $this->phpDll = dirname(PHP_BINARY) . '/php7' . (PHP_ZTS ? 'ts' : 'nts') . '.dll';
            self::$phpapi = FFI::cdef($code, $this->phpDll);
            self::$isWin = true;
        } else {
            $code .= 'extern HashTable* zend_array_dup(HashTable *source);zval* zend_hash_find(const HashTable *ht, zend_string *key);';
            self::$phpapi = FFI::cdef($code);
        }
        try {
            $a = FFI::type('long double');
            ob_start();
            var_dump($a);
            $types = ob_get_clean();
            if(strpos($types, 'object(FFI\CType:long double)') === 0) {
                self::$HAVE_LONG_DOUBLE = true;
            } else {
                self::$HAVE_LONG_DOUBLE = false;
            }
        } catch(FFI\ParserException $e) {
            self::$HAVE_LONG_DOUBLE = false;
        }
        self::$ZEND_FFI_TYPE_KIND = [
            'ZEND_FFI_TYPE_VOID',
            'ZEND_FFI_TYPE_FLOAT',
            'ZEND_FFI_TYPE_DOUBLE'];
        if(self::$HAVE_LONG_DOUBLE) {
            self::$ZEND_FFI_TYPE_KIND[] = 'ZEND_FFI_TYPE_LONGDOUBLE';
        }
        self::$ZEND_FFI_TYPE_KIND = array_merge(self::$ZEND_FFI_TYPE_KIND, ['ZEND_FFI_TYPE_UINT8',
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

    public function castSameType(FFI $ffi, &$arg)
    {
        if($arg instanceof FFI\CData) {
            $typeStruct = \FFI::typeof($arg);
            $type = $this->getCTypeName($typeStruct);
            $arg = $ffi->cast($type, $arg);
        }
    }

    public function zvalValue(CData $zval)
    {
        switch($this->Z_TYPE($zval)) {
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
        $ex = self::$phpapi->zend_rebuild_symbol_table();
        $sym = $this->zend_array_dup($ex);
        return $this->zvalValue($sym->arData->val);
    }

    public function sizeof($ffi, $type)
    {
        return FFI::sizeof($ffi->type($type));
    }

    public function HT_HASH_SIZE($mask)
    {
        $m = self::$phpapi->cast('int32_t', $mask);
        $p = self::$phpapi->cast('uint32_t', -1 * $m->cdata);
        $s = self::$phpapi->new('size_t');
        if(FFI::sizeof($s) > FFI::sizeof($p)) {
            return $p->cdata * $this->sizeof(self::$phpapi, 'uint32_t');
        } else {
            return self::$phpapi->cast('size_t', $p)->cdata * $this->sizeof(self::$phpapi, 'uint32_t');
        }
    }

    public function cast2Size(CData $data, $ffi = null)
    {
        $f = $ffi ?? self::$phpapi;
        $s = $f->new('size_t');
        if(FFI::sizeof($s) > FFI::sizeof($data)) {
            return $s->cdata = $data->cdata;
        } else {
            return $f->cast('size_t', $data);
        }
    }

    public function emalloc($size)
    {
        $ptr = self::$phpapi->new("char[$size]");
        FFI::memset($ptr, 0, $size);
        return self::$phpapi->cast('void*', $ptr);
    }

    public function win_zend_array_dup($source)
    {
        $target = FFI::addr(self::$phpapi->new('HashTable'));
        $target[0]->gc->refcount = 1;
        $target[0]->gc->u->type_info = (self::IS_ARRAY | ((1 << 4) << 0));
        $flags = ($source[0]->u->flags & 0xff);
        $target[0]->u->flags = $flags;
        $target[0]->nTableMask = $source[0]->nTableMask;
        $target[0]->nNextFreeElement = $source[0]->nNextFreeElement;
        $target[0]->nInternalPointer = ($source[0]->nInternalPointer < $source[0]->nNumUsed) ?
            $source[0]->nInternalPointer : 0;

        $target[0]->nTableSize = $source[0]->nTableSize;

        $s = self::$phpapi->new('size_t');
        if(FFI::sizeof($s) > FFI::sizeof($target[0]->nTableSize)) {
            $tableSize = $target[0]->nTableSize;
        } else {
            $tableSize = self::$phpapi->cast('size_t', $target[0]->nTableSize)->cdata;
        }

        $htsize = (($tableSize * $this->sizeof(self::$phpapi, 'Bucket')) + $this->HT_HASH_SIZE($target[0]->nTableMask));
        $ema = $this->emalloc($htsize);

        $data = self::$phpapi->cast('char*', $ema);

        $target[0]->arData = self::$phpapi->cast('Bucket*', $data + $this->HT_HASH_SIZE($target[0]->nTableMask));

        $idx = (self::$phpapi->cast('int32_t', $target[0]->nTableMask));
        $ptr = self::$phpapi->cast('uint32_t', self::$phpapi->cast('uint32_t*', $target[0]->arData)[$idx->cdata]);
        FFI::memset(FFI::addr($ptr), -1, $this->HT_HASH_SIZE($target->nTableMask));

        if($source[0]->nNumUsed == $source[0]->nNumOfElements) {
            $idx = $this->zend_array_dup_elements($source, $target[0], 1, 0);
        } else {
            $idx = $this->zend_array_dup_elements($source, $target[0], 1, 1);
        }
        $target[0]->nNumUsed = $idx;
        $target[0]->nNumOfElements = $idx;
        return $target;
    }

    protected function zend_array_dup_elements($source, $target, $static_keys, $with_holes)
    {
        $idx = 0;
        $q = $target->arData;
        $p = $source[0]->arData;
        $end = $p + $source[0]->nNumUsed;

        do {
            if(!$this->zend_array_dup_element($source, $target, $idx, $p, $q, 0, $static_keys, $with_holes)) {
                $target_idx = $idx;
                $idx++;
                $p++;
                while($p != $end) {
                    if($this->zend_array_dup_element($source, $target, $target_idx, $p, $q, 0, $static_keys, $with_holes)) {
                        if($source[0]->nInternalPointer == $idx) {
                            $target->nInternalPointer = $target_idx;
                        }
                        $target_idx++;
                        $q++;
                    }
                    $idx++;
                    $p++;
                }
                return $target_idx;
            }
            $idx++;
            $p++;
            $q++;
        } while($p != $end);
        return $idx;
    }

    protected function Z_TYPE_INFO($zval)
    {
        return $zval->u1->type_info;
    }

    protected function zend_array_dup_element($source, $target, $idx, $p, $q, $packed, $static_keys, $with_holes)
    {
        $data = $p->val;
        $Z_TYPE_FLAGS_MASK = 0xff00;
        if($with_holes) {
            if(!$packed && $data->u1->type_info === self::$IS_INDIRECT) {
                $data = $data->value->zv;
            }
            if($data->u1->type_info === self::IS_UNDEF) {
                return 0;
            }
        } elseif(!$packed) {
            if($data->u1->type_info == self::$IS_INDIRECT) {
                $data = $data->value->zv;
                if($data->u1->type_info === self::IS_UNDEF) {
                    return 0;
                }
            }
        }
        do {
            if(($data->u1->type_info & $Z_TYPE_FLAGS_MASK) != 0) {
                if($this->Z_TYPE($data) === self::IS_REFERENCE && $data->value->counted->gc[0]->refcount == 1 && ($this->Z_TYPE($data->value->ref->val) != self::IS_ARRAY ||
                    $data->value->ref->val->value->arr != $source)) {
                    $data = $data->value->ref->val;
                    if(!($data->u1->type_info & $Z_TYPE_FLAGS_MASK) != 0) {
                        break;
                    }
                }
                $data->value->counted->gc->refcount = $data->value->counted->gc->refcount + 1;
            }
        } while(0);
        $_gc = $data->value->counted;
        $_t = $data->u1->type_info;

        if(PHP_INT_SIZE === 8) {
            $q->val->value->counted = $_gc;
            $q->val->u1->type_info = $_t;
        } else {
            $_w2 = $data->value->ww->w2;
            $q->val->value->counted = $_gc;
            $q->val->value->ww->w2 = $_w2;
            $q->val->u1->type_info = $_t;
        }
        $q->h = $p->h;

        if($packed) {
            $q->key = null;
            return 1;
        }

        $q->key = $p->key;
        if(!$static_keys && $q->key) {
            if(!((($q->key[0]->gc->u->type_info >> 0) & (0x000003f0 >> 0)) & (1 << 6))) {
                $q->key[0]->value->counted->gc->refcount = $q->key[0]->value->counted->gc->refcount + 1;
            }
        }

        $nIndex = self::$phpapi->cast('int32_t', self::$phpapi->cast('uint32_t', ($q->h | $target->nTableMask)))->cdata;
        $q[0]->val->u2->next = self::$phpapi->cast('uint32_t*', $target->arData)[$nIndex];
        self::$phpapi->cast('uint32_t*', $target->arData)[$nIndex] = ($idx * $this->sizeof(self::$phpapi, 'Bucket'));
        return 1;
    }

    public function zend_array_dup($arr)
    {
        if(self::$isWin || self::$usePhpImplement) {
            return $this->win_zend_array_dup($arr);
        } else {
            return self::$phpapi->zend_array_dup($arr);
        }
    }

    public function zend_hash_find($arr, $name)
    {
        if(self::$isWin) {
            return $this->callFuncWindows('zend_hash_find@@8', [$arr, $name]);
        } else {
            return self::$phpapi->zend_hash_find($arr, $name);
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
        $typeCData = self::$phpapi->cast('zend_ffi_cdata*', $this->zval($type));
        $typeCData = $this->ZEND_FFI_TYPE($typeCData[0]->type);
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
        while(1) {
            switch($type[0]->kind) {
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
                    if(!$this->isNull($type[0]->enumeration->tag_name)) {
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
                    if($is_ptr) {
                        $is_ptr = 0;
                        $buf = '(' . $buf . ')';
                    }
                    $buf .= '()';
                    $type = $this->ZEND_FFI_TYPE($type[0]->func[0]->ret_type);
                    break;
                case $this->ZEND_FFI_TYPE_KIND('ARRAY'):
                    if($is_ptr) {
                        $is_ptr = 0;
                        $buf = "($buf)";
                    }
                    $buf .= '[';
                    if($type->attr & self::ZEND_FFI_ATTR_VLA) {
                        $buf .= '*';
                    } else if(!($type->attr & self::ZEND_FFI_ATTR_INCOMPLETE_ARRAY)) {
                        $buf .= $type[0]->array->length;
                    }
                    $buf .= ']';
                    $type = $this->ZEND_FFI_TYPE($type[0]->array->type);
                    break;
                case $this->ZEND_FFI_TYPE_KIND('STRUCT'):
                    if($type[0]->attr & self::ZEND_FFI_ATTR_UNION) {
                        if(!$this->isNull($type[0]->record->tag_name)) {
                            $tagname = $type[0]->record->tag_name;
                            $buf = $this->getZStr($tagname) . $buf;
                        } else {
                            $buf = '<anonymous>' . $buf;
                        }
                        $name = "union ";
                    } else {
                        if(!$this->isNull($type[0]->record->tag_name)) {
                            $tagname = $type[0]->record->tag_name;
                            $buf = $this->getZStr($tagname) . $buf;
                        } else {
                            $buf = '<anonymous>' . $buf;
                        }
                        $name = "struct ";
                    }
                    break;
                default:
                    if(self::$HAVE_LONG_DOUBLE && $type[0]->kind == $this->ZEND_FFI_TYPE_KIND('LONGDOUBLE')) {
                        $name = 'long double';
                        break;
                    }
                    assert_options(ASSERT_BAIL, 1);
                    assert(0);
            }
            if($name) {
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
        $v = $this->zend_hash_find($zendArrayPtr, $name);
        if($this->isNull($v)) {
            return NULL;
        }
        $p = self::Z_PTR_P($v);
        return self::$phpapi->cast($type, $p);
    }

    public function zend_hash_num_elements(CData $zendArrayPtr)
    {
        $a = self::$phpapi->cast('zend_array*', $zendArrayPtr);
        return $a[0]->nNumOfElements;
    }

    protected function findSymobl(FFI $ffi, string $symName, $symType)
    {
        $zendObj = $this->zval($ffi);
        $zendStr = $this->zval($symName);
        $zffi = self::$phpapi->cast('zend_ffi*', $zendObj);
        if($this->isNull($zffi->symbols)) {
            return null;
        }
        $sym = $this->zend_hash_find_ptr($zffi->symbols, $zendStr, 'zend_ffi_symbol*');
        if($this->isNull($sym) || $sym[0]->kind !== $symType) {
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
        return self::$phpapi->cast('zend_ffi_type*',
                self::$phpapi->cast('uintptr_t', $t)->cdata & ~ self::ZEND_FFI_TYPE_OWNED);
    }

    public function castAllSameType(FFI $ffi, array &$args)
    {
        foreach($args as &$v) {
            $this->castSameType($ffi, $v);
        }
    }

    public function iteratorZendArray(CData $hashTable, callable $callable)
    {
        for($i = 0; $i < $hashTable[0]->nNumUsed; $i++) {

            $p = $hashTable[0]->arData + $i;
            if($this->Z_TYPE($p[0]->val) == self::IS_UNDEF) {
                continue;
            }
            $v = $this->zvalValue($p[0]->val);
            $data = FFI::addr($p)[0]->val;
            if($this->Z_TYPE($data) == self::$IS_INDIRECT) {
                $data = $this->zvalValue($data);
                if($this->Z_TYPE($data) == self::IS_UNDEF) {
                    continue;
                }
            }
            if(!$this->isNull($p->key)) {
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
        $p = self::$phpapi->new("char *[$argc]", false);
        foreach($argv as $i => $arg) {
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
        $charArr = self::$phpapi->new("char[$len]", false);
        for($i = 0; $i < $len; $i++) {
            $char = self::$phpapi->new('char', false);
            $char->cdata = $string[$i];
            $charArr[$i] = $char;
        }
        return $charArr;
    }

}
