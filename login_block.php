<?php

session_start();
require_once "config/database.php";

if (!isset($_SESSION['logged']) || !$_SESSION['logged'] || $_SESSION['group_name'] != 'admin' || !isset($_SESSION['blockStatus']))
{
	header('Location: ./');
	exit();
}

try {
    $blockStatus = $_SESSION['blockStatus'] == 'active' ? 'inactive' : 'active';

    $blockQuery = $db->prepare("UPDATE global_settings SET value = '$blockStatus' WHERE name = 'login-block'");
    $blockQuery->execute();

} catch (PDOException $error) {}

header('Location: ./');
