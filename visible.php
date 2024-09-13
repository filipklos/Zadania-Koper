<?php
session_start();

require_once "config/database.php";
require_once "items/global_functions.php";

if (!isset($_SESSION['logged']) || !$_SESSION['logged'] || !isset($_GET['id']) || $_SESSION['group_name'] != 'admin' && !isAuthor($db, $_GET['id'], $_SESSION['ID_user']))
{
	header('Location: ./');
	exit();
}

try {
	isset($_GET['v']) && $_GET['v'] ? $visible = 0 : $visible = 1;

	$visibleQuery = $db->prepare("UPDATE tasks SET visible = $visible WHERE id = :id");
	$visibleQuery->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$visibleQuery->execute();

} catch (PDOException $error) {}

header('Location: zadanie_'.htmlentities($_GET['id']));
