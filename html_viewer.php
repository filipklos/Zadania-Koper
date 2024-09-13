<?php

require_once "config/database.php";

$code_id = 0;

if (isset($_GET['code_id'])) {
    $code_id = $_GET['code_id'];
}

if ($code_id != 0) {
	$code_query = $db->prepare("SELECT code FROM codes WHERE id = :id");
	$code_query->bindValue(':id', $code_id, PDO::PARAM_INT);
	$code_query->execute();

	$code = $code_query->fetch();
    echo $code[0];
} else {
    echo "<h1>Nie podano wartości 'code_id' w GET</h1>";
}
?>