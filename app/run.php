<?php 

include "parser.php";

$parser = new Parser\Parser();
$parser->run();
print_r("<pre>");
// print_r($parser->menu_errors);
// print_r($parser->mod_errors);
print_r("</pre>");
