<?php

$host     = "localhost";
$dbname   = "mnd";
$user     = "root";
$password = "mysql*root";
$connStr  = "mysql:host=" . $host . ";dbname=" . $dbname;

$pdo      = new \PDO($connStr, $user, $password);

$query = 
"SELECT * FROM cs_menugroup";

$res = $pdo->query($query);
$res = $res->fetchAll();

print_r("<pre>");
print_r($res);
print_r("</pre>");
