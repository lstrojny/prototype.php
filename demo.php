<?php
require_once "prototype.php";

$obj1 = new Object;

$obj1->prototype->foo = function () { echo "foo\n"; };

$obj2 = new Object($obj1->prototype);

$obj2->foo(); // foo

$obj1->prototype->foo = function () { echo "bar\n"; };

$obj2->foo(); // bar (inheritance!)

$obj3 = new Object($obj2->prototype);

$obj1->prototype->foo = function () { echo "baz\n"; };

$obj3->foo(); // baz (transitivity!)

$obj4 = new Object;

$obj4->prototype->foo = function () { echo "pok\n"; };

$obj3->prototype = $obj4->prototype;

$obj3->foo(); // pok (prototype exchange!)
