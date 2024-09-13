<?php

session_start();

if ((!isset($_POST['login'])) || (!isset($_POST['password']))) {
	header('Location: ./');
	exit();
}

if (isset($_SESSION['form_login'])) unset($_SESSION['form_login']);
$login = $_POST['login'];
$password = $_POST['password'];
$redirectSuccess = $_POST['redirectSuccess'] ?? './';
$redirectFailure = $_POST['redirectFailure'] ?? './';
$_SESSION['form_login'] = $login;

$login = htmlentities($login, ENT_QUOTES, "UTF-8");

require_once "config/database.php";

$blockQuery = $db->prepare("SELECT value FROM global_settings WHERE name = 'login-block'");
$blockQuery->execute();
$blockStatus = $blockQuery->fetch()['value'];

$userQuery = $db->prepare('SELECT users.*, user_groups.group_name FROM users, user_groups
	WHERE users.username = :login AND users.group_id = user_groups.ID_group');
$userQuery->bindValue(':login', $login, PDO::PARAM_STR);
$userQuery->execute();

$user_results = $userQuery->fetch();
if (
	$user_results && password_verify($password, $user_results['password']) &&
	($user_results['group_name'] == 'admin' || $blockStatus != 'active')
) {
	$_SESSION['logged'] = true;

	$_SESSION['ID_user'] = $user_results['ID_user'];
	$_SESSION['user'] = $user_results['username'];
	$_SESSION['group_name'] = $user_results['group_name'];


	unset($_SESSION['error']);
	header('Location: ' . $redirectSuccess);
} else {
	$_SESSION['error'] = "Nieprawidłowy login lub hasło";
	header('Location: ' . $redirectFailure);
}
