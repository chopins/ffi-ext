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

    private static $phpapi = null;
    private static bool $HAVE_LONG_DOUBLE = false;
    private static array $ZEND_FFI_TYPE_KIND = [];
    private static $zend_execute_ex = null;

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
    const IS_INDIRECT = 12;
    const IS_PTR = 13;
    const IS_ALIAS_PTR = 14;
    const _IS_ERROR = 15;
    const _IS_BOOL = 16;
    const _IS_NUMBER = 17;
    const HASH_FLAG_UNINITIALIZED = 1 << 3;
    const HT_MIN_SIZE = 8;
    const HT_MIN_MASK = -2;
    const HASH_FLAG_PACKED = 1 << 2;
    const HASH_FLAG_STATIC_KEYS = 1 << 4;

    public function __construct()
    {
        if(self::$phpapi === null) {
            $this->initPhpApi();
        }
        var_dump(FFI::sizeof(self::$phpapi->type('Bucket')));
    }

    public function getffi()
    {
        return self::$phpapi;
    }

    public function assert($v)
    {
        if(!$v) {
            throw new \RuntimeException('assert exception');
        }
    }

    protected function ZEND_FFI_TYPE_KIND($name)
    {
        return array_search("ZEND_FFI_TYPE_$name", self::$ZEND_FFI_TYPE_KIND);
    }

    private function initPhpApi()
    {
        $bitSize = PHP_INT_SIZE * 8;
        $code = "typedef int{$bitSize}_t zend_long;typedef uint{$bitSize}_t zend_ulong;typedef int{$bitSize}_t zend_off_t;";
        $code .= file_get_contents(__DIR__ . '/php.h');
        if(strcasecmp(PHP_OS, 'WINNT') == 0 && PHP_INT_SIZE === 4) {
            $code = str_replace('#IF_ZEND_WIN32_MACRO#', 'OSVERSIONINFOEX windows_version_info;', $code);
        } else {
            $code = str_replace('#IF_XPFPA_HAVE_CW_MACRO#', 'unsigned int saved_fpu_cw;', $code);
        }

        if(PHP_INT_SIZE === 4) {
            $code = str_replace('#IF_ZEND_USE_ABS_MACRO#', 'zend_op *jmp_addr;zval *zv;', $code);
        } else {
            $code = str_replace('#IF_ZEND_USE_ABS_MACRO#', 'uint32_t jmp_offset;', $code);
        }
        if(PHP_ZTS) {
            $code .= 'extern size_t executor_globals_offset;extern void *tsrm_get_ls_cache(void);';
        }

        self::$phpapi = FFI::cdef($code);
        try {
            FFI::type('long double');
            self::$HAVE_LONG_DOUBLE = true;
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
            case self::IS_PTR:
            case self::IS_ALIAS_PTR:
                return $zval->value->ptr;
            case self::IS_INDIRECT:
                return $zval->value->zv;
            default:
                throw new TypeError('unknown type');
        }
    }

    public function EG($name)
    {
        if(PHP_ZTS) {
            $lsc = self::$phpapi->tsrm_get_ls_cache();
            $v = self::$phpapi->cast('char*', $lsc) + self::$phpapi->executor_globals_offset;
            return self::$phpapi->cast('zend_executor_globals*', $v)[0]->$name;
        } else {
            return self::$phpapi->executor_globals->$name;
        }
    }

    public function phpVar($v)
    {
        //$arr = $this->zend_array_dup($this->zend_rebuild_symbol_table());
        //return $this->zvalValue($arr->arData->val);
    }

    public function Z_TYPE_INFO(&$zval)
    {
        return $zval->u1->type_info;
    }

    public function HT_ASSERT_RC1($ht)
    {
        return;
    }

    public function GC_FLAGS($p)
    {
        $gc_type_info = $p[0]->gc->u->type_info;
        return ($gc_type_info >> 0) & (GC_FLAGS_MASK >> 0x000003f0);
    }

    public function HT_SIZE_EX($size, $mask)
    {
        $ds = $size * FFI::sizeof(self::$phpapi->type('Bucket'));
        $hs = $this->HT_HASH_SIZE($size);
        return $ds + $hs;
    }

    public function HT_HASH_SIZE($ht)
    {
        return (-1 * $ht) * FFI::sizeof(self::$phpapi->type('uint32_t'));
    }

    public function pemalloc($size, $p)
    {
        if($p) {
            $len = ($size / FFI::sizeof(self::$phpapi->type('char')));
            $tmp = self::$phpapi->new("char[$len]", true, true);
        } else {
            $tmp = self::$phpapi->new($size);
        }

        return self::$phpapi->cast('void*', $tmp);
    }

    public function emalloc($size)
    {
        $len = ($size / FFI::sizeof(self::$phpapi->type('char')));
        $tmp = self::$phpapi->new("char[$len]");
        return self::$phpapi->cast('void*', $tmp);
    }

    public function HT_HASH_RESET_PACKED(&$ht)
    {
        $d = self::$phpapi->cast('uint32_t*', $ht[0]->arData);
        $d[-2] = -1;
        $d[-1] = -1;
    }

    public function zend_hash_real_init_packed_ex(&$ht)
    {
        if($this->GC_FLAGS($ht) & (1 << 7)) {
            $data = $this->pemalloc($this->HT_SIZE_EX($ht[0]->nTableSize, self::HT_MIN_MASK), 1);
        } elseif($ht[0]->nTableSize == self::HT_MIN_SIZE) {
            $data = emalloc($this->HT_SIZE_EX(self::HT_MIN_SIZE, self::HT_MIN_MASK));
        } else {
            $data = emalloc($this->HT_SIZE_EX($ht[0]->nTableSize, self::HT_MIN_MASK));
        }

        $ht[0]->arData = self::$phpapi->cast('Bucket*', self::$phpapi->cast('char*', $data) + $this->HT_HASH_SIZE($ht[0]->nTableMask));
        $ht[0]->u->v->flags = self::HASH_FLAG_PACKED | self::HASH_FLAG_STATIC_KEYS;
        $this->HT_HASH_RESET_PACKED($ht);
    }

    public function zend_hash_real_init_ex(&$ht, $packed)
    {
        $this->assert($this->HT_FLAGS($ht) & self::HASH_FLAG_UNINITIALIZED);
        if($packed) {
            $this->zend_hash_real_init_packed_ex($ht);
        } else {
            $this->zend_hash_real_init_mixed_ex($ht);
        }
    }

    public function zend_hash_real_init(&$ht, $packed)
    {
        $this->IS_CONSISTENT($ht);
        $this->HT_ASSERT_RC1($ht);
        $this->zend_hash_real_init_ex($ht, $packed);
    }

    public function zend_hash_check_size($nSize)
    {
        $max = PHP_INT_SIZE === 4 ? 0x04000000 : 0x80000000;
        if($nSize <= 8) {
            return 8;
        } elseif($nSize >= $max) {
            throw new \RuntimeException('"Possible integer overflow in memory allocation');
        }
        $nSize -= 1;
        $nSize |= ($nSize >> 1);
        $nSize |= ($nSize >> 2);
        $nSize |= ($nSize >> 4);
        $nSize |= ($nSize >> 8);
        $nSize |= ($nSize >> 16);
        return $nSize + 1;
    }

    public function HT_FLAGS($ht)
    {
        return $ht[0]->u->flags;
    }

    public function IS_CONSISTENT($a)
    {
        if($this->HT_FLAGS($a) & ((1 << 0) | (1 << 1)) === 0) {
            return;
        }
        $this->assert(0);
    }

    protected function zend_hash_extend(&$ht, $nSize, $packed)
    {
        if($nSize == 0) {
            return;
        }
        if($this->HT_FLAGS($ht) & self::HASH_FLAG_UNINITIALIZED) {
            if($nSize > $ht[0]->nTableSize) {
                $ht[0]->nTableSize = $this->zend_hash_check_size($nSize);
            }
            $this->zend_hash_real_init($ht, $packed);
        } else {
            if($packed) {
                
            } elseif($nSize > $ht[0]->nTableSize) {
                
            }
        }
    }

    protected function zend_hash_real_init_mixed($ht)
    {
        
    }

    public function ZVAL_INDIRECT(&$zval, $v)
    {
        $zval[0]->value->v = $v;
        $zval[0]->u1->type_info = 12;
    }

    protected function zend_hash_append_ind($ht, $key, $ptr)
    {
        
    }

    public function zend_rebuild_symbol_table()
    {
        $ZEND_CALL_HAS_SYMBOL_TABLE = 1 << 20;
        $ex = $this->EG('current_execute_data');
        while(!$this->isNull($ex) && ($this->isNull($ex[0]->func) || ($ex[0]->func[0]->common->type == 1))) {
            $ex = $ex[0]->prev_execute_data;
        }
        if($this->isNull($ex)) {
            return null;
        }
        if($this->Z_TYPE_INFO($ex)[0]->This & $ZEND_CALL_HAS_SYMBOL_TABLE) {
            return $ex[0]->symbol_table;
        }
        $t = $this->Z_TYPE_INFO($ex)[0]->This;
        ($t |= $ZEND_CALL_HAS_SYMBOL_TABLE);
        if($this->EG('symtable_cache_ptr') > $this->EG('symtable_cache')) {
            $ptr = $this->EG('symtable_cache_ptr');
            $symbol_table = $ex[0]->symbol_table = (--$ptr)[0];
            if(!$ex[0]->func[0]->op_array->last_var) {
                return $symbol_table;
            }
            $this->zend_hash_extend($symbol_table, $ex[0]->func[0]->op_array->last_var, 0);
        } else {
            $symbol_table = $ex[0]->symbol_table = zend_new_array($ex[0]->func[0]->op_array->last_var);
            if($this->isNull($ex[0]->func[0]->op_array->last_var)) {
                return $symbol_table;
            }
            $this->zend_hash_real_init_mixed($symbol_table);
            /* printf("Cache miss!  Initialized %x\n", EG(active_symbol_table)); */
        }
        if(!$this->isNull($ex[0]->func[0]->op_array->last_var)) {
            $str = $ex[0]->func[0]->op_array->vars;
            $end = $str + $ex[0]->func[0]->op_array->last_var;
            $var = $this->ZEND_CALL_VAR_NUM($ex, 0);

            do {
                $this->zend_hash_append_ind($symbol_table, $str, $var);
                $str++;
                $var++;
            } while($str != $end);
        }
        return $symbol_table;
    }

    public function ZEND_MM_ALIGNED_SIZE($size)
    {
        $align = FFI::sizeof(self::$phpapi->type('mm_align_test'));
        return (($size + $align - 1) & (~($align - 1)));
    }

    public function ZEND_CALL_VAR_NUM($call, $n)
    {
        $call = self::$phpapi->cast('zval*', $call);
        $zedsize = FFI::sizeof(self::$phpapi->type('zend_execute_data'));
        $zvalsize = FFI::sizeof(self::$phpapi->type('zval'));
        $slot = (($this->ZEND_MM_ALIGNED_SIZE($zedsize) + $this->ZEND_MM_ALIGNED_SIZE($zvalsize) - 1) / $this->ZEND_MM_ALIGNED_SIZE($zvalsize));
        return ($call + ($slot + $n));
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
        $typeCData = self::$phpapi->cast('zend_ffi_cdata*', $this->phpVar($type));
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
                    $this->assert(0);
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

    public function zend_hash_find($arrPtr, $name)
    {
        
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
        $zendObj = $this->phpVar($ffi);
        $zendStr = $this->phpVar($symName);
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
            if($this->Z_TYPE($data) == self::IS_INDIRECT) {
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
