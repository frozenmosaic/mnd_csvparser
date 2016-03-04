<?php


$string = "&#24180;&#24180;&#26377;&#39192;(4&#20154;&#20221;) &#26865;&#37002;&#39770;+ &#27963;&#34662;+&#22291;&#24180;&#31957;	Channel Catfishm Live Shrimp & Rice Cake (for 4)";

// echo $string;

echo "\r\n";
$converted = html_entity_decode($string);
echo $converted;