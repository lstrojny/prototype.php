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
    
    public function __sleep () {
        try { return $this->__call('sleep'); }
        catch (BadMethodCallException $e) { return array_keys((array)$this); }
    }
    
    public function __wakeup () {
        try { $this->__call('wakeup'); }
        catch (BadMethodCallException $e) { return; }
    }

    public function fn ($name, Closure $function) {
        if (!is_callable($function))
            throw new InvalidArgumentException("Invalid function");
        $this->$name = new Method($function);
        return $this;
    }

    public function each (Closure $function) {
        try { $this->__call('each', array($function)); }
        catch (BadMethodCallException $e) {
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

class Method implements Serializable {

    protected $_closure;
    protected $_code;

    public function __construct (Closure $function) {
        if (!$function)
            throw new RuntimeException("Invalid closure");
        $this->_closure = $function;
    }

    public function __invoke () {
        return call_user_func_array($this->_closure, func_get_args());
    }
    
    public function __toString () {
        try { return $this->_fetchCode(); }
        catch (Exception $e) { return ""; }
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
    
    public function serialize () {
        empty($this->_code) && $this->_fetchCode();
        return serialize($this->_code);
    }
    
    public function unserialize ($serialized) {
        if (!$fct = self::create(unserialize($serialized)))
            throw new RuntimeException("Unable to unserialize method");
            
        $this->_closure = $fct;
    }
    
    protected function _fetchCode() {
        $reflection = new ReflectionFunction($this->_closure);
        $file = new SplFileObject($reflection->getFileName());
        $file->seek($reflection->getStartLine()-1);
    
        $code = '';
        while ($file->key() < $reflection->getEndLine()) {
            $code .= $file->current();
            $file->next();
        }
        
        $begin = strpos($code, 'function');
        $end   = strrpos($code, '}');
        $code  = str_replace(array("\r","\n"), "", substr($code, $begin, $end - $begin + 1));
        
        return $this->_code = $code;
    }
    
    public static function create ($definition) {
        if (!preg_match('~(function)?\s*\((?P<args>[^\)]*)\)\s*\{(?P<code>.*)\}~i', $definition, $matches))
            return false;
    
        $args = $matches['args'];
        $code = $matches['code'];
        return create_function($args, $code);
    }
}

function debug (Object $obj, $indent = 0, $return = false) {
    $pad   = str_repeat("|  ", $indent);
    $lines = array();
    foreach ((array)$obj as $key => $value) {
        if (is_object($value) && !is_callable(array($value, '__toString')))
            $value = "[object]";
        if ($value instanceOf Object)
            $value = str_replace("|  Object", "Object", trim(debug($value, $indent+1, true)));
        if ($value instanceOf Method)
            $value = "[function]";
        $lines[$key] = "{$pad}+ {$key} : {$value}";
    }
    ksort($lines);
    $debug = "{$pad}Object (".count($lines).")\n".implode("\n",$lines)."\n";
    if ($return) return $debug;
    echo $debug;
}
