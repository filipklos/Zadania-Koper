<?php
session_start();

if ((!isset($_SESSION['logged'])) || ($_SESSION['logged'] == false) || ($_SESSION['group_name'] != 'admin'))
{
	header('Location: ./');
	exit();
}
if (!isset($_POST['change_gr_id']))
{
	header('Location: zarzadzanie_uzytkownikami');
	exit();
}

$new_group_id = $_POST['change_gr_id'];
$data_correct = true;

if (!is_numeric($new_group_id)){
	$data_correct = false;
}

if ($data_correct == true) {
	require_once "config/database.php";
	$userQuery = $db->prepare("SELECT ID_user, group_id FROM users WHERE ID_user = :ng_id");
	$userQuery->bindValue(':ng_id', $new_group_id, PDO::PARAM_INT);
	$userQuery->execute();

	$user = $userQuery->fetch();
	$user_group_id = $user['group_id'];

	$groupsQuery = $db->prepare('SELECT group_name FROM user_groups WHERE ID_group = :ug_id');
	$groupsQuery->bindValue(':ug_id', $user_group_id, PDO::PARAM_INT);
	$groupsQuery->execute();

	$group_name = $groupsQuery->fetch();
	$user_group_name = $group_name['group_name'];

	if($user_group_name == "admin"){
		header('Location: zarzadzanie_uzytkownikami');
		exit();
	}
	if ($user_group_name == "user") {
		$Query = $db->prepare('UPDATE users SET group_id = 3 WHERE ID_user = :ng_id');
		$Query->bindValue(':ng_id', $new_group_id, PDO::PARAM_INT);
		$Query->execute();
	}
	else if($user_group_name == "editor"){
		$Query = $db->prepare('UPDATE users SET group_id = 2 WHERE ID_user = :ng_id');
		$Query->bindValue(':ng_id', $new_group_id, PDO::PARAM_INT);
		$Query->execute();
	}
	$_SESSION['correct_move'] = 'Pomyślnie zmieniono rolę użytkownika';
	header('Location: zarzadzanie_uzytkownikami');
}
else {
	header('Location: zarzadzanie_uzytkownikami');
}