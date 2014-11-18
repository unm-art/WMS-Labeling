<?php 

session_start();
$cnumVal = (isset($_POST['value'])) ? $_POST['value'] : "";
$cnumVal = str_replace('\n', "<br>", $cnumVal);
print $cnumVal;
$_SESSION['cnumVal'] = preg_replace(array('#<br\s*/?>#i', '#<\/div\s*/?>#i', '#<div\s*/?>#i'),array("\n","",""), $cnumVal);
$_SESSION['printArray'] = $_SESSION['cnumVal'];
?>