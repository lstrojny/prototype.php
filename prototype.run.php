<?php
require_once "prototype.php";

// fabrique de classes
$class = Object::create()
    ->fn('new', function ($that) {
        $object = new Object(clone $that->prototype);
        $args   = func_get_args();
        array_shift($args);
        if ($that->construct instanceof Method)
            $that->construct->apply($object, $args);
        return $object;
    });

// créons la classe $string
$string = new Object($class);

// ajoutons lui quelques méthodes d'instance...
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

// ...et quelques méthodes de classes
// notez qu'elles portent le même nom
$string->toUpperCase = function ($that, $str) use (&$string) {
    return $string->new($str)->toUpperCase();
};
$string->toLowerCase = function ($that, $str) use (&$string) {
    return $string->new($str)->toLowerCase();
};

// les méthodes de classes s'utilisent un peu de la même façon qu'en OOP classique
echo $string->toUpperCase('lowercase') . "\n"; // affiche "LOWERCASE"
echo $string->toLowerCase('uppercase') . "\n"; // affiche "uppercase"

// maintenant crééons une instance de $string
$my_string = $string->new('hello world !');
echo $my_string->toUpperCase() . "\n"; // affiche "HELLO WORLD !"
echo $my_string->toUpperCase()->toLowerCase() . "\n"; // affiche "hello world !"

// c'est là que ça devient intéressant, nous allons ajouter une nouvelle
// méthode d'instance à $string et nous allons l'appeller dans le contexte
// de $my_string
$string->prototype->replace = function ($that, $search, $replace) {
    return $that->new(str_replace($search, $replace, $that->str));
};

try {
    // cet appel va échouer parce que le prototype de $my_string est obsolète
    // car il s'agit d'un clone donc quand $string change, $my_string ne change
    // pas
    echo $my_string->replace('hello', 'strange') . "\n";
}
catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// corrigeons ça en mettant à jour le prototype de $my_string
$my_string->prototype = $string->prototype;

// et maintenant nous pouvons faire
echo $my_string->replace('hello', 'strange')->toUpperCase() . "\n"; // affiche "STRANGE WORLD !"

// on peut aussi appliquer les méthode de $string à des instances qui n'en hérite pas
$obj_a = Object::createFromArray(array('str' => 'abc'));
$obj_b = Object::createFromArray(array('str' => 'DEF'));

echo $string->prototype->toUpperCase->call($obj_a) . "\n";
echo $string->prototype->toLowerCase->call($obj_b) . "\n";
