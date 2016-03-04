<?php

try
{
    $this->dbo = new \PDO("mysql:host=localhost;dbname=menudrive", "root", "vy");
} catch (PDOException $e) {
    $e->getMessage();
}


$query = "INSERT INTO `test` values(1)";

$stmt = $this->dbo->prepare($query);
$stmt->execute();