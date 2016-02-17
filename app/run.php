<?php 

include "parser.php";

$parser = new Parser\Parser();
$parser->run();
print_r('done');