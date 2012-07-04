<?php
require_once "prototype.php";

$class = Object::create()
    ->fn('new', function ($that) {
        $object = new Object(clone $that->prototype);
        $args   = func_get_args();
        array_shift($args);
        if ($that->construct instanceof Method)
            $that->construct->apply($object, $args);
        return $object;
    });

$string = new Object($class);

$string->prototype->construct = function ($that, $str = "") {
    $that->str = $str;
};
$string->prototype->toString = function ($that) {
    return (string)$that->str;
};
$string->prototype->toUpperCase = function ($that) use (&$string) {
    return $string->new(strtoupper($that->str));
};
$string->prototype->toLowerCase = function ($that) use (&$string) {
    return $string->new(strtolower($that->str));
};
$string->toUpperCase = function ($that, $str) use (&$string) {
    return $string->new($str)->toUpperCase();
};
$string->toLowerCase = function ($that, $str) use (&$string) {
    return $string->new($str)->toLowerCase();
};

echo $string->toUpperCase('lowercase') . "\n"; // displays "LOWERCASE"
echo $string->toLowerCase('uppercase') . "\n"; // displays "uppercase"

$my_string = $string->new('hello world !');
echo $my_string->toUpperCase() . "\n"; // displays "HELLO WORLD !"
echo $my_string->toUpperCase()->toLowerCase() . "\n"; // displays "hello world !"

$string->prototype->replace = function ($that, $search, $replace) {
    return $that->new(str_replace($search, $replace, $that->str));
};

try {
    // Will fail
    echo $my_string->replace('hello', 'strange') . "\n";
}
catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$my_string->prototype = $string->prototype;

// Will pass
echo $my_string->replace('hello', 'strange')->toUpperCase() . "\n"; // displays "STRANGE WORLD !"

$obj_a = Object::createFromArray(array('str' => 'abc'));
$obj_b = Object::createFromArray(array('str' => 'DEF'));

echo $string->prototype->toUpperCase->call($obj_a) . "\n";
echo $string->prototype->toLowerCase->call($obj_b) . "\n";
