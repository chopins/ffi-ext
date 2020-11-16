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

use ReflectionException;
use Toknot\ReflectionCFunction;
use FFI;
use Reflector;

class ReflectionFFIObject extends FFIExtend
{
    private $refObj;
    public function __construct(FFI $ffi)
    {
        parent::__construct();
        $this->refObj = $ffi;
    }

    public function getFunction(string $name): ReflectionCFunction
    {
        return new ReflectionCFunction($this->refObj, $name);
    }

    public function getEunm()
    {
        return $this->getDefines($this->getffi()->ZEND_FFI_SYM_CONST);
    }

    public function getVariable()
    {
        return $this->getDefines($this->getffi()->ZEND_FFI_SYM_VAR);
    }

    public function getType()
    {
        return $this->getDefines($this->getffi()->ZEND_FFI_SYM_TYPE);
    }

    private function getDefines($type)
    {
        $zendObj = $this->zval($this->refObj);
        $zffi = $this->getffi()->cast('zend_ffi*', $zendObj);
        if (CFFI::isNull($zffi->symbols)) {
            return null;
        }
        $refList = [];
        $this->iteratorZendArray($zffi->symbols, function ($k, $v) use (&$refList, $type) {
            $symbol = $this->getffi->cast('zend_ffi_symbol*', $v);
            if ($symbol[0]->kind === $type) {
                $name = $this->getZStr($this->getffi->cast('zend_string*', $k));
                $refList[] = $name;
            }
        });
        return $refList;
    }

    public function getFunctions()
    {
        $zendObj = $this->zval($this->refObj);
        $zffi = $this->getffi()->cast('zend_ffi*', $zendObj);
        if (CFFI::isNull($zffi->symbols)) {
            return null;
        }
        $refList = [];
        $this->iteratorZendArray($zffi->symbols, function ($k, $v) use (&$refList) {
            $symbol = $this->getffi->cast('zend_ffi_symbol*', $v);
            if ($symbol[0]->kind === $this->getffi->ZEND_FFI_SYM_FUNC) {
                $func = $this->getZStr($this->getffi->cast('zend_string*', $k));
                $refList[] = $this->getFunction($func);
            }
        });
        return $refList;
    }

    public function hasFunction(string $name): bool
    {
        return $this->hasCFunc($this->refObj, $name);
    }

    public function hasEnum(string $enum)
    {
        return $this->hasCEnum($this->refObj, $enum);
    }

    public function hasVariable(string $name)
    {
        return $this->hasCVariable($this->refObj, $name);
    }

    public function hasType(string $type)
    {
        return $this->hasCType($this->refObj, $type);
    }
}
