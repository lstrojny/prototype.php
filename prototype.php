<?php

class Object implements ArrayAccess {

    public function __construct (Object $prototype = null) {
        $this->prototype = $prototype ? clone $prototype : new Prototype;
    }

    public function __get ($key) {
        if (isset($this->$key)) return $this->$key;
        if (isset($this->prototype)) return $this->prototype->$key;
        return null;
    }

    public function __set ($key, $value) {
        if ($value instanceof Closure) $this->fn($key,$value);
        else $this->$key = $value;
    }

    public function __call ($method, $args = array()) {
        if (!($fn = $this->__get($method)) || !$fn instanceof Method)
            throw new BadMethodCallException("Invalid method $method");
        return $fn->apply($this, $args);
    }

    public function __clone () {
        try { $this->__call('clone'); }
        catch (BadMethodCallException $e) { return; }
    }

    public function __toString () {
        try { return $this->__call('toString'); }
        catch (BadMethodCallException $e) { return "[object]"; }
    }

    public function __destruct () {
        try { $this->__call('destruct'); }
        catch (BadMethodCallException $e) { return; }
    }

    public function fn ($name, Closure $function) {
        if (!is_callable($function))
            throw new InvalidArgumentException("Invalid function");
        $this->$name = new Method($function);
        return $this;
    }

    public function each (Closure $function) {
        $gm = function ($object) use (&$gm) {
            $m = (array)$object;
            if ($object->prototype)
                $m += $gm($object->prototype);
            return $m;
        };
        foreach ($gm($this) as $key => $value) {
            if ($key != 'prototype')
                $function($key,$value);
        }
        return $this;
    }

    public function offsetExists ($offset) {
        return $this->__get($offset) !== null;
    }

    public function offsetGet ($offset) {
        return $this->__get($offset);
    }

    public function offsetSet ($offset, $value) {
        return $this->__set($offset, $value);
    }

    public function offsetUnset ($offset) {
        if (isset($this->$offset)) unset($this->$offset);
    }

    public static function create ($prototype = null) {
        return new static ($prototype);
    }

    public static function createFromArray (array $data) {
        $object = static::create();
        foreach ($data as $key => $value)
            $object->$key = $value;
        return $object;
    }
}

class Prototype extends Object {

    public function __construct () {
    }
}

class Method {

    protected $_closure;

    public function __construct (Closure $function) {
        if (!$function)
            throw new RuntimeException("Invalid closure");
        $this->_closure = $function;
    }

    public function __invoke () {
        return call_user_func_array($this->_closure, func_get_args());
    }

    public function call (Object $context) {
        $function = $this->_closure;
        return call_user_func_array($function, func_get_args());
    }

    public function apply (Object $context, array $args) {
        $function = $this->_closure;
        array_unshift($args, $context);
        return call_user_func_array($function, $args);
    }
}
