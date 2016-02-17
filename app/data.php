<?php 

include "parser.php";

$parser = new Parser\Parser();
$parser->run();
print_r("<pre>");
// print_r("inserted");
// print_r($parser->csv_mod);
// print_r($parser->size_id);
print_r("</pre>");
