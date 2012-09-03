<?php
require_once "prototype.classes.php";

echo "Serializing 10.000 times a rand String: ";
$start = microtime(true);
for ($i = 0; $i < 10000; $i++ ):
$my_string = String(rand(1,1000));
$serialized = serialize($my_string);
endfor;
echo round($overall = microtime(true) - $start, 3) . "s\n";
echo round(($overall / 10000) * 1000, 3) . "ms/each\n";



$another = unserialize($serialized);

debug($another);
