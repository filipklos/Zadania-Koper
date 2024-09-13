<?php

function isAuthor($db, $taskId, $userId) {
	try {
		$authorQuery = $db->prepare("SELECT user_id FROM tasks WHERE id = :task_id AND user_id = :user_id");
		$authorQuery->bindValue(':task_id', $taskId, PDO::PARAM_INT);
		$authorQuery->bindValue(':user_id', $userId, PDO::PARAM_INT);
		$authorQuery->execute();

		return !empty($authorQuery->fetch());
	}
	catch (PDOException $error) {
		return false;
	}
}

function getResultIcon($maxVal) {
	if ($maxVal !== null) {
		switch ($maxVal) {
			case 0: $color = '#C00'; break;
			case 1: $color = '#AAA'; break;
			case 2: $color = '#DB0'; break;
			default: $color = 'rgba(0, 0, 0, 0)'; break;
		}
		$maxVal == 0 ? $icon = 'wrong' : $icon = 'good';

		return '<i class="icon-'.$icon.'" style="color:'.$color.'"></i>';
	}
	return '';
}

function setCookieFromPost($name, $period = 30) {
	if (isset($_POST[$name]))
		setcookie($name, $_POST[$name], time() + 86400 * $period);
}

function getFromPostOrCookie($name, $default, $toDefault = null) {
	if (isset($_POST[$name])) {
		if ($_POST[$name] != $toDefault)
			return $_POST[$name];
	} else {
		if (isset($_COOKIE[$name]) && $_COOKIE[$name] != $toDefault)
			return $_COOKIE[$name];
	}
	return $default;
}

function compareUsers($a, $b, $sortColumn, $sortOrder) {
    $valueA = isset($a[$sortColumn]) ? $a[$sortColumn] : '';
    $valueB = isset($b[$sortColumn]) ? $b[$sortColumn] : '';

    if (is_numeric($valueA) && is_numeric($valueB)) {
        $result = ($valueA - $valueB);
    } else {
        $result = strcasecmp($valueA, $valueB);
    }

    return ($sortOrder === 'ASC') ? $result : -$result;
}