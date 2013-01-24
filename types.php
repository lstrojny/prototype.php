<?php
require_once "prototype.php";

function _ () {
    static $class = null;
    $class || $class = Object::createFromArray(array(
        'new' => function ($that) {
            $object = new Object($that->prototype);
            $args   = func_get_args();
            array_shift($args);
            if ($that->construct instanceof Method)
                $that->construct->apply($object, $args);
            return $object;
        },
        'construct' => function ($that, $val) {
            $that->val = $val;
        },
        'unbox' => function ($that) {
            return $that->val;
        },
        'toString' => function ($that) {
            return (string)$that->val;
        }
    ));
    return $class;
}

function box ($val) {
    switch (gettype($val)) {
        case 'integer':
        case 'double':
            return Number($val);
        case 'string':
            return String($val);
        case 'array':
            return Collection($val);
        default:
            return $arg;
    }
}

function unbox ($object) {
    return $object instanceOf Object ? $object->unbox : $object;
}

function Boolean () {
    static $boolean = null;
    $boolean || $boolean = new Object(_());
    return func_num_args() ? $boolean->new(func_get_arg(0)) : $boolean;
}

Boolean()->prototype
    ->fn('construct', function ($that, $val) {
        $that->val = (boolean)$val;
    })
    ->fn('isTrue', function ($that) {
        return $that->val;
    })
    ->fn('isFalse', function ($that) {
        return !$that->val;
    })
    ->fn('ifTrue', function ($that, Closure $fct) {
        $that->isTrue() && $fct();
        return $that;
    })
    ->fn('ifFalse', function ($that, Closure $fct) {
        $that->isFalse() && $fct();
        return $that;
    });

function String () {
    static $string = null;
    $string || $string = new Object(_());
    return func_num_args() ? $string->new(func_get_arg(0)) : $string;
}

String()->prototype
    ->fn('toLowerCase', function ($that) {
        $that->val = strtolower($that->val);
        return $that;
    })
    ->fn('toUpperCase', function ($that) {
        $that->val = strtoupper($that->val);
        return $that;
    })
    ->fn('replace', function ($that, $search, $replacement) {
        $that->val = str_replace(unbox($search), unbox($replacement), $that->val);
        return $that;
    })
    ->fn('match', function ($that, $pattern) {
        if (preg_match(unbox($pattern), $that->val, $matches))
            return Collection($matches);
        else
            return Boolean(false);
    })
    ->fn('explode', function ($that, $separator){
        return Collection(explode(unbox($separator), $that->val));
    })
    ->fn('toFile', function ($that, $file) {
        return Boolean(file_put_contents(unbox($file), $that->val));
    });

String()
    ->fn('fromFile', function ($that, $file) {
        return is_readable(unbox($file)) ? String(file_get_contents(unbox($file))) : Boolean(false);
    });

function Number () {
    static $number = null;
    $number || $number = new Object(_());
    return func_num_args() ? $number->new(func_get_arg(0)) : $number;
}

Number()->prototype
    ->fn('format', function ($that, $decimals = 0, $dec_point = ".", $thousands_sep = ',') {
        return String(number_format($that->val, $decimals, $dec_point, $thousands_sep));
    })
    ->fn('convert', function ($that, $from_base, $to_base) {
        return String(base_convert($that->val, $from_base, $to_base));
    })
    ->fn('compareTo', function ($that, $number) {
    })
    ->fn('isGreaterThan', function ($that, $number, $strict = false) {
    })
    ->fn('isLowerThan', function ($that, $number, $strict = false) {
    });

function Collection () {
    static $collection = null;
    $collection || $collection = new Object(_());
    return func_num_args() ? $collection->new(func_get_arg(0)) : $collection;
}

Collection()->prototype
    ->fn('construct', function ($that, $values = array()) {
        if (empty($values)) return;
        foreach ((array)$values as $key => $value)
            $that->$key = is_array($value) ? Collection($value) : $value;
    })
    ->fn('toString', function ($that) {
        return json_encode($that->unbox());
    })
    ->fn('each', function ($that, Closure $fct) {
        foreach ((array)$that as $key => $value) {
            if ($key == 'prototype' || $value instanceOf Method) continue;
            $fct($key, $value);
        }
        return $that;
    })
    ->fn('unbox', function ($that) {
        $array = array();
        $that->each(function ($i,$item) use (&$array) {
            $array[$i] = $item instanceOf Object ? $item->unbox() : $item;
        });
        return $array;
    })
    ->fn('count', function ($that) {
        $count = 0;
        $that->each(function ($i,$item) use (&$count) {
            $count++;
        });
        return $count;
    })
    ->fn('push', function ($that, $value) {
        $that[$that->count()->unbox()+1] = $value;
        return $that;
    })
    ->fn('pop', function ($that) {
        $i = 1;
        $c = $that->count();
        do { $k = $c-$i; } while ($k > 0 && !isset($that[$k]));
        if ($k >= 0) {
            $val = $that[$k];
            unset($that[$k]);
            return $val;
        }
        return null;
    })
    ->fn('get', function ($that, $key, $def = null) {
        $keys = explode('.', $key);
        $curr = $that;
        while ($k = array_shift($keys)) {
           if (!$curr instanceOf Object) return $def;
           elseif (!isset($curr[$k])) return $def;
           else $curr = $curr[$k];
        }
        return $curr;
    })
    ->fn('implode', function ($that, $glue) {
        return String(implode($glue, $that->unbox()));
    });
