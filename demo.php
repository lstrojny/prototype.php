<?php
require_once "prototype.types.php";

$collection = Collection(array(
        'path' => array(
                'to' => array(
                        'treasure' => 'here!'
                )
        )
));

echo $collection->get('path.to.treasure') . "\n";

debug($collection);