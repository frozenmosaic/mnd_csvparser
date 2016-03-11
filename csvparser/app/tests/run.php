<?php 

// include "menuparser.php";

// $parser = new MenuParser\Parser('uploads/sample_menu.csv');
// print_r("<pre>");
// // print_r($parser->menu_errors);
// // print_r($parser->mod_errors);
// print_r("</pre>");

        $sizename = 'Small';
         $cat_id = 54;
        $query =
            "SELECT *
            FROM  cs_categorysize
            WHERE sizename = '" . $sizename .
            "' AND catid = " . $cat_id;
echo $query;

