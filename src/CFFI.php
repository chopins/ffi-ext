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
use FFI\ParserException;

class CFFI
{
    private $code = '';
    private $lib = null;
    private $ffi = null;
    private $defines = [];

    public function __construct(string $code = '', ?string $lib = null)
    {
        $this->code = $code;
        $lib = $lib;
    }

    public function getFFI(): FFI
    {
        return $this->ffi;
    }

    public function dll(string $lib)
    {
        $this->lib = $lib;
    }

    public function include(string $file)
    {
        $this->code .= file_get_contents($file);
    }

    public function code(string $code)
    {
        $this->code .= $code;
    }

    public function define(string $name, $value)
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $name)) {
            throw new ParserException("C define macro name($name) error, only 'A-Za-z0-9_'", E_USER_ERROR);
        }
        $this->defines[$name] = $value;
    }

    public function isDefine($name)
    {
        return isset($this->defines[$name]);
    }

    public function typedef(string $type, string $def)
    {
        $this->code .= "typedef $type $def;";
    }

    public function ifDefine(string $name, bool $exp, $value, $else = '')
    {
        if ($exp) {
            $this->define($name, $value);
        } else {
            $this->define($name, $else);
        }
    }

    public function ifdef(string $name, string $if, string $else = '')
    {
        if ($this->isDefine($name)) {
            $this->code .= $if;
        } elseif ($else) {
            $this->code .= $else;
        }
    }

    public function ifndef(string $name, string $ifn, string $else = '')
    {
        if (!$this->isDefine($name)) {
            $this->code .= $ifn;
        } elseif (isset($exp[2])) {
            $this->code .= $else;
        }
    }

    public function newPtr($type, ?int $value = null): CData
    {
        $p = $this->ffi->new($type, false);
        if ($value !== null) {
            $p->cdata = $value;
        }
        return FFI::addr($p);
    }

    public function newValue($type, $value = null)
    {
        $v = $this->ffi->new($type);
        if ($value !== null) {
            $v->cdata = $value;
        }
        return $v;
    }

    private function mCdef(?string $lib = null): CFFI
    {
        $patterns = [];
        foreach ($this->defines as $name => $value) {
            $patterns["/([^A-Z0-9a-z_])$name([^A-Z0-9a-z_])/"] = fn ($m) => $m[1] . $value . $m[2];
        }
        $this->code = preg_replace_callback_array($patterns, $this->code);
        if ($lib) {
            $this->lib = $lib;
        }
        $this->ffi = FFI::cdef($this->code, $this->lib);
        return $this;
    }

    private function mCast($type, FFI\CData $ptr): CData
    {
        return $this->ffi->cast($type, $ptr);
    }

    private function mNew($type, $owned = true, $persistent = false): CData
    {
        return $this->ffi->new($type, $owned, $persistent);
    }

    private function mType($type): CType
    {
        return $this->ffi->type($type);
    }

    private function mSizeof($ptr): int
    {
        return FFI::sizeof($this->type($ptr));
    }

    /**
     * char**
     *
     * @param array $argv
     * @return FFI\CData
     */
    public static function argsPtr(array $argv): CData
    {
        return self::newCharPtrPtr($argv, false);
    }

    /**
     * char**
     *
     * @param array $array
     * @param boolean $owned
     * @return FFI\CData
     */
    public static function newCharPtrPtr(array $array, $owned = true): CData
    {
        $count = count($array);
        $p = FFI::new("char *[$count]", $owned);
        foreach ($array as $i => $arg) {
            $p[$i] = self::newCharPtr($arg, $owned);
        }
        $a = FFI::addr($p);
        return FFI::cast('char**', $a);
    }

    /**
     * char*
     *
     * @param string $string
     * @param boolean $owned
     * @return CData
     */
    public static function newCharPtr(string $string, $owned = true): CData
    {
        $charArr = self::newCharArray($string, $owned);
        return FFI::cast('char*', FFI::addr($charArr));
    }

    /**
     * char[]
     *
     * @param string $string
     * @param boolean $owned
     * @return CData
     */
    public static function newCharArray(string $string, $owned = true): CData
    {
        $len = strlen($string);
        $charArr = FFI::new("char[$len]", $owned);
        for ($i = 0; $i < $len; $i++) {
            $char = FFI::new('char', $owned);
            $char->cdata = $string[$i];
            $charArr[$i] = $char;
        }
        return $charArr;
    }

    /**
     * unmanaged and permanently, on system heap
     *
     * @param int $size
     * @return CData
     */
    public static function emalloc(int $size): CData
    {
        return FFI::cast('void*', FFI::new("char[$size]"), false, true);
    }

    /**
     * unmanaged and unpermanently, on php heap
     *
     * @param integer $size
     * @return CData
     */
    public static function malloc(int $size): CData
    {
        return FFI::cast('void*', FFI::new("char[$size]"), false, false);
    }

    public static function isNull($v): bool
    {
        return $v === null || (self::isCData($v) && FFI::isNull($v));
    }

    public static function haveLongDouble(): bool
    {
        try {
            return FFI::sizeof(FFI::type('long double')) > 8;
        } catch (FFI\ParserException $e) {
            return false;
        }
    }

    public static function isCData($v): bool
    {
        return $v instanceof CData;
    }
    public static function isIA64(): bool
    {
        if (PHP_INT_SIZE === 8) {
            return true;
        } else {
            $psize = FFI::sizeof(FFI::new('void *'));
            if ($psize === 8) {
                return true;
            }
            return false;
        }
    }

    public static function __callStatic($name, $arguments)
    {
        if ($name == 'sizeof') {
            return FFI::sizeof(FFI::type($arguments[0]));
        }
        return FFI::$name(...$arguments);
    }

    public function __call($name, $arguments)
    {
        $pMethod = ['cdef', 'cast', 'new', 'type', 'type', 'sizeof'];
        if (in_array($name, $pMethod)) {
            $method = "m" . ucfirst($name);
            return $this->$method(...$arguments);
        }
        return $this->ffi->$name(...$arguments);
    }
}
