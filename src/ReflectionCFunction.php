<?php

/**
 * php-capi (http://toknot.com)
 *
 * @copyright  Copyright (c) 2020 Szopen Xiao (Toknot.com)
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/php-gtk
 * @version    0.1
 */

namespace Toknot;

use ReflectionException;
use FFI;

class ReflectionCFunction extends PhpApi
{

    private $name = '';
    private $ffi = null;
    private $type = null;

    public function __construct(FFI $ffi, $name)
    {
        $this->ffi = $ffi;
        $this->name = $name;
        $zendObj = $this->phpVar($ffi);
        $zendStr = $this->phpVar($name);
        $zffi = $this->phpffi()->cast('zend_ffi*', $zendObj);
        if(FFI::isNull($zffi->symbols)) {
            throw new ReflectionException("C function $name does not exists");
        }

        $sym = $this->zend_hash_find_ptr($zffi->symbols, $zendStr, 'zend_ffi_symbol*');
        if(!FFI::isNull($sym) && $sym[0]->kind == self::ZEND_FFI_SYM_FUNC) {
            $this->type = $this->ZEND_FFI_TYPE($sym[0]->type)[0];
            return;
        }
        throw new ReflectionException("C function $name does not exists");
    }

    public function isVariadic()
    {
        return (bool) ($this->type->attr & self::ZEND_FFI_ATTR_VARIADIC);
    }

    public function cfuncNumArgs()
    {
        return $this->type->func->args[0]->nNumOfElements;
    }

    /**
     * Get C function return type name
     * 
     * @return string
     */
    public function getReturnType()
    {
        return $this->getCTypeCDataName($this->type->func->ret_type);
    }

    /**
     * Get C function parameters type list
     * 
     * @return array
     */
    public function getParameters(): array
    {
        $array = $this->phpffi()->zend_array_dup($this->type->func->args);
        $parameters = [];
        $this->iteratorZendArray($array, function($k, $v) use(&$parameters) {
            $type = $this->phpffi()->cast('zend_ffi_type*', $v);
            $type = $this->ZEND_FFI_TYPE($type);
            $name = $this->getCTypeCDataName($type);
            $parameters[$k] = $name;
        });
        return $parameters;
    }

}
