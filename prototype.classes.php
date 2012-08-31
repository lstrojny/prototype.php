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
        'getPrimitive' => function ($that) {
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
        $that->val = valtolower($that->val);
        return $that;
    })
    ->fn('toUpperCase', function ($that) {
        $that->val = valtoupper($that->val);
        return $that;
    })
    ->fn('replace', function ($that, $search, $replacement) {
        $that->val = val_replace($search, $replacement, $that->val);
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
    });

$i = Number(123);
echo $i->format(3, ',', '.');
