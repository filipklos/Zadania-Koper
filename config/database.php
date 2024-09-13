<?php

function delFiles($db, $filesDel) {
	try {
		if (!empty($filesDel)) {
			foreach ($filesDel as $fileDel) {
				$loc = 'files/'.$fileDel['task_group_id'];
				unlink("{$loc}/{$fileDel['name']}");

				if (count(scandir($loc)) == 2) rmdir($loc);

				$fileDelQuery = $db->prepare('DELETE FROM files WHERE files.id = :file_id');
				$fileDelQuery->bindValue(':file_id', $fileDel['id'], PDO::PARAM_INT);
				$fileDelQuery->execute();
			}
		}
		else throw new Exception();
	}
	catch (Exception $error) { return false; }
	return true;
}

function delEmptyElementsAssociatedWithTask($db, $table, $tasksCol='id', $tableCol='task_id') {
	$delQuery = $db->prepare("DELETE FROM $table WHERE (SELECT COUNT(id) FROM tasks WHERE tasks.$tasksCol = $table.$tableCol) = 0");
	$delQuery->execute();
}

function delEmptyElements($db) {
	try {
		delEmptyElementsAssociatedWithTask($db, 'task_groups', 'group_id', 'id');
		delEmptyElementsAssociatedWithTask($db, 'categories', 'category_id', 'id');
		delEmptyElementsAssociatedWithTask($db, 'answers');
		delEmptyElementsAssociatedWithTask($db, 'user_answers');
		delEmptyElementsAssociatedWithTask($db, 'codes');

		$filesQuery = $db->prepare('SELECT id, task_group_id, name FROM files WHERE (SELECT COUNT(id) FROM task_groups WHERE files.task_group_id = task_groups.id) = 0');
		$filesQuery->execute();

		$filesDel = $filesQuery->fetchAll();
		if (!delFiles($db, $filesDel)) throw new Exception('Failed to delete file');

		return true;
	}
	catch (Exception $error) {
		return false;
	}
}

$config = require_once 'config.php';

try {

	$db = new PDO("mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}", $config['user'], $config['password'], [
	PDO::ATTR_EMULATE_PREPARES => false,
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
	]);

} catch (PDOException $error) {

	exit('<h1 style="text-align: center">Błąd połączenia z bazą danych!</h1>');

}
