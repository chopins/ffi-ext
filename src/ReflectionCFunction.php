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

use ReflectionException;
use FFI;
use Reflector;

class ReflectionCFunction extends PhpApi implements Reflector
{

    private $name = '';
    private $ffi = null;
    private $type = null;

    public function __construct(FFI $ffi, $name)
    {
        parent::__construct();
        $this->ffi = $ffi;
        $this->name = $name;
        $sym = $this->findSymobl($ffi, $name, self::ZEND_FFI_SYM_FUNC);
        if(!$this->isNull($sym)) {
            $this->type = $this->ZEND_FFI_TYPE($sym[0]->type)[0];
            return;
        }
        throw new ReflectionException("C function $name does not exists");
    }

    public function getName()
    {
        return $this->name;
    }

    public static function export()
    {
        return $this->name;
    }

    public function getClosure()
    {
        $num = $this->getNumberOfParameters();
        switch($num) {
            case 0:
                return function() {
                    return $this->ffi->{$this->name}();
                };
            case 1:
                return function($a) {
                    return $this->ffi->{$this->name}($a);
                };
            case 2:
                return function($a, $b) {
                    return $this->ffi->{$this->name}($a, $b);
                };
            case 3:
                return function($a, $b, $c) {
                    return $this->ffi->{$this->name}($a, $b, $c);
                };
            case 4:
                return function($a, $b, $c, $d) {
                    return $this->ffi->{$this->name}($a, $b, $c, $d);
                };
            case 5:
                return function($a, $b, $c, $d, $e) {
                    return $this->ffi->{$this->name}($a, $b, $c, $d, $e);
                };
            case 6:
                return function($a, $b, $c, $d, $e, $f) {
                    return $this->ffi->{$this->name}($a, $b, $c, $d, $e, $f);
                };
            default:
                $p = array_fill(0, $num, '$a');
                foreach($p as $i => &$v) {
                    $v = $v . $i;
                }
                $args = implode(',', $p);
                $c = null;
                eval("\$c = function($args){return $this->ffi->{$this->name}($args)};");
                return $c;
        }
    }

    public function __toString()
    {
        return "C Function {$this->name}";
    }

    public function isVariadic()
    {
        return (bool) ($this->type->attr & self::ZEND_FFI_ATTR_VARIADIC);
    }

    public function getNumberOfParameters()
    {
        $args = $this->type->func->args;
        if($this->isNull($args)) {
            return 0;
        }
        return $args[0]->nNumOfElements;
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
