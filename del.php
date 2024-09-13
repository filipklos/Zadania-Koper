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
	$delQuery = $db->prepare('DELETE FROM tasks WHERE tasks.id = :id');
	$delQuery->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
	$delQuery->execute();

	$_SESSION['del_task'] = true;

} catch (PDOException $error) { $_SESSION['del_task'] = false; }

delEmptyElements($db);

header('Location: ./');
