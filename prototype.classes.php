<?php
require "prototype.php";

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
        $that->val = str_replace($search, $replacement, $that->val);
        return $that;
    })
    ->fn('match', function ($that, $pattern, $flags = 0, $offset = 0) {
        return preg_match($pattern, $that->val, $matches, $flags, $offset) ? $matches : false;
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
    });

function Collection () {
    static $collection = null;
    $collection || $collection = new Object(_());
    return func_num_args() ? $collection->new(func_get_arg(0)) : $collection;
}

Collection()->prototype
    ->fn('construct', function ($that, array $values = array()) {
        if (empty($values)) return;
        foreach ($values as $key => $value) $that->$key = $value;
    })
    ->fn('each', function ($that, Closure $fct) {
        foreach ((array)$that as $key => $value) {
            if ($key == 'prototype' || $value instanceOf Method)
                continue;
            $fct($key, $value);
        }
        return $that;
    })
    ->fn('count', function ($that) {
        $count = 0;
        foreach ((array)$that as $key => $value) {
            if ($key != 'prototype' && !$value instanceOf Method)
                $count++;
        }
        return Number($count);
    })
    ->fn('push', function ($that, $value) {
        $that[$that->count()->unbox()+1] = $value;
        return $that;
    });
    
$items = Collection(range(1,10));
echo $items->count() . " items\n";
$items->push(11);
echo $items->count() . " items\n";
