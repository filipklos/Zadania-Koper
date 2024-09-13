<?php

session_start();

require_once 'config/database.php';
require_once 'items/global_functions.php';

if (!isset($_SESSION['logged']) || !$_SESSION['logged'] || $_SESSION['group_name'] == 'user' ||
	(isset($_GET['edit']) && $_SESSION['group_name'] != 'admin' && !isAuthor($db, $_GET['edit'], $_SESSION['ID_user'])))
{
	header('Location: ./');
	exit();
}

function findInArray($arr, $what, $where) {
	foreach ($arr as $el)
		if ($el[$where] == $what) return true;
	return false;
}

function checkFileErrors($name) {
	if ($_FILES[$name]['error'] > 0) {
		switch ($_FILES[$name]['error']) {
			case 1: 
			case 2: throw new Exception('Rozmiar pliku jest zbyt duży (max: 20MB)!');
			default: throw new ErrorException('Wystąpił błąd podczas wysyłania pliku.');
		}
	}
	return true;
}

function checkFileType($name, $types)
{
	foreach($types as $type) {
		if (strpos($_FILES[$name]['type'], $type) === 0)
			return true;
	}
	throw new Exception('Niewłaściwy typ pliku!');
}

function saveFile($name, $dir)
{
	$fileName = htmlentities($_FILES[$name]['name'], ENT_QUOTES, "UTF-8");
	if ($fileName != $_FILES[$name]['name']) throw new Exception('Nazwa pliku zawiera niedozwolone znaki (& \' " < >)!');

	$loc = 'files/'.$dir;
	if (!is_dir($loc)) mkdir($loc);
	$loc .= '/'.$fileName;

	if (@is_uploaded_file($_FILES[$name]['tmp_name'])) {
		if (!@move_uploaded_file($_FILES[$name]['tmp_name'], $loc)) throw new Exception('Wystąpił błąd podczas wysyłania pliku.');
	}
	else throw new Exception('Wystąpił błąd podczas wysyłania pliku.');

	return true;
}

$categoriesQuery = $db->query("SELECT id, category FROM categories");
$categories = $categoriesQuery->fetchAll();

$groupsQuery = $db->query("SELECT id, task_group FROM task_groups");
$groups = $groupsQuery->fetchAll();

if (isset($_GET['edit'])) $taskId = $_GET['edit'];

if (isset($_POST['category']) || isset($taskId) || isset($_GET['title'])) {
	
	if (isset($_GET['title'])) {
		$title = $_GET['title'];
		$content = $_GET['content'];
		$category = $_GET['category'];
		$group = $_GET['group'];
		$code = $_GET['code'];

		$newCategory = '';
		$newGroup = '';
		$color = '';
		$del = 0;
		$delAns = 0;
		$visible = 1;

		$data_correct = false;

	} else if (isset($taskId) && (!isset($_POST['title']) || $_POST['title'] == "")) {
		$taskCompleteQuery = $db->prepare('SELECT title, content, category_id, group_id, color, code, visible FROM tasks, categories WHERE categories.id = tasks.category_id AND tasks.id = :taskId');
		$taskCompleteQuery->bindValue(':taskId', $taskId, PDO::PARAM_INT);
		$taskCompleteQuery->execute();
	
		$data = $taskCompleteQuery->fetch();

		if ($data == null) {
			header('Location: ./');
			exit();
		}
	
		$title = $data['title'];
		$content = $data['content'];
		$category = $data['category_id'];
		$group = $data['group_id'];
		$color = $data['color'];
		$code = $data['code'];
		$visible = $data['visible'];
	
		$newCategory = '';
		$newGroup = '';
		$del = 0;
		$delAns = 0;

		$data_correct = false;
	
	} else {
		$data_correct = true;

		$title = $_POST['title'];
		$content = $_POST['content'];
		$category = $_POST['category'];
		$group = $_POST['group'];
		$newGroup = $_POST['new_group'];
		$newCategory = $_POST['new_category'];
		$color = $_POST['category_color'];
		$del = $_POST['del'];
		$delAns = substr($_POST['del_ans'], 1);
		isset($_POST['code']) ? $code = 1 : $code = 0;
		isset($_POST['invisible']) ? $visible = 0 : $visible = 1;

		if ( strlen($title) < 3 || strlen($title) > 40 ) {
			$data_correct = false;
			$_SESSION['e_title'] = "Nazwa musi posiadać od 3 do 40 znaków!";
		}

		if (empty($content)) {
			$data_correct = false;
			$_SESSION['e_content'] = "To pole nie może być puste!";
		}

		if ($category == 0) {
			if ( strlen($newCategory) < 2 || strlen($newCategory) > 20 ) {
				$data_correct = false;
				$_SESSION['e_new_category'] = "Nazwa musi posiadać od 2 do 20 znaków!";
			}
		}
		else if (!findInArray($categories, $category, "id")) {
			$data_correct = false;
			$_SESSION['e_category'] = "Proszę wybrać kategorię!";
		}

		if ($group == 0) {
			if ( strlen($newGroup) < 2 || strlen($newGroup) > 20 ) {
				$data_correct = false;
				$_SESSION['e_new_group'] = "Nazwa musi posiadać od 2 do 20 znaków!";
			}
		}
		else if ($group == -1) $group = null;
		else if (!findInArray($groups, $group, "id")) {
			$data_correct = false;
			$_SESSION['e_group'] = "Wybierz grupę jeszcze raz!";
		}
	}

	if (isset($_POST['answer'])) {
		$answer = $_POST['answer'];
		$points = $_POST['points'];

		$answerCorrect = true;

		if (strlen($answer) < 1) {
			$answerCorrect = false;
			unset($answer);
		}

		if ($points < 0 || $points > 20) {
			$answerCorrect = false;
			$_SESSION['e_answer'] = 'Punkty nie mogą wychodzić poza zakres od 0 do 20!';
		}

		if ($answerCorrect && isset($taskId)) {
			try {
				$answerQuery = $db->prepare('INSERT INTO answers(task_id, answer, points) VALUES(:taskId, :answer, :points)');
				$answerQuery->bindValue(':taskId', $taskId, PDO::PARAM_INT);
				$answerQuery->bindValue(':answer', $answer, PDO::PARAM_STR);
				$answerQuery->bindValue(':points', $points, PDO::PARAM_INT);
				$answerQuery->execute();

			} catch (PDOException $error) {
				$_SESSION['e_answer'] = 'Coś poszło nie tak. Nie udało się dodać odpowiedzi. Spróbuj jeszcze raz.';
			}
		}
	}

	if ($del != 0) {
		try {
			$filesDeletionQuery = $db->prepare('SELECT id, task_group_id, name FROM files WHERE id = :del_id');
			$filesDeletionQuery->bindValue(':del_id', $del, PDO::PARAM_INT);
			$filesDeletionQuery->execute();
			
			$filesDeletion = $filesDeletionQuery->fetchAll();
			if (!$filesDeletion || !delFiles($db, $filesDeletion))
				throw new Exception('Failed to delete file');
			
		} catch (Exception $error) {
			$_SESSION['e_file'] = 'Nie udało się usunąć pliku.';
		}
	}

	if ($delAns != 0) {
		try {
			$answerDeletionQuery = $db->prepare('DELETE FROM answers WHERE id = :delId');
			$answerDeletionQuery->bindValue(':delId', $delAns, PDO::PARAM_INT);
			$answerDeletionQuery->execute();
		} catch (PDOException $error) {
			$_SESSION['e_answer'] = 'Nie udało się usunąć odpowiedzi.';
		}
	}

	if (isset($_FILES['file-upload'])) {
		try {
			$fileName = $_FILES['file-upload']['name'];

			$fileCheckQuery = $db->prepare('SELECT name FROM files WHERE name = :fileName AND task_group_id = :group');
			$fileCheckQuery->bindValue(':group', $group, PDO::PARAM_INT);
			$fileCheckQuery->bindValue(':fileName', $fileName, PDO::PARAM_STR);
			$fileCheckQuery->execute();

			if (!empty($fileCheckQuery->fetch())) throw new Exception('Plik o podanej nazwie już istnieje!');
			if ($group === null || !htmlentities($group)) throw new ErrorException('Incorrect group selected');

			checkFileErrors('file-upload');
			checkFileType('file-upload', array('text/', 'application/pdf', 'image/', 'application/vnd.openxmlformats-officedocument'));
			saveFile('file-upload', $group);

			$fileQuery = $db->prepare('INSERT INTO files(task_group_id, title, name) VALUES(:group, :fileName1, :fileName2)');
			$fileQuery->bindValue(':group', $group, PDO::PARAM_INT);
			$fileQuery->bindValue(':fileName1', $fileName, PDO::PARAM_STR);
			$fileQuery->bindValue(':fileName2', $fileName, PDO::PARAM_STR);

			$fileQuery->execute();

		}
		catch (PDOException $pdoError) {
			$_SESSION['e_file'] = 'Coś poszło nie tak. Nie udało się dodać pliku.';
		}
		catch (ErrorException $error){}
		catch (Exception $error) {
			$_SESSION['e_file'] = $error->getMessage();
		}
	}

	if ($group > 0 && findInArray($groups, $group, "id")) {
		$filesQuery = $db->prepare('SELECT id, name FROM files WHERE task_group_id = :group');
		$filesQuery->bindValue(':group', $group, PDO::PARAM_INT);
		$filesQuery->execute();

		$files = $filesQuery->fetchAll();
	}

	if (isset($taskId)) {
		$answersQuery = $db->prepare('SELECT id, answer, points FROM answers WHERE answers.task_id = :taskId');
		$answersQuery->bindValue(':taskId', $taskId, PDO::PARAM_INT);
		$answersQuery->execute();

		$answers = $answersQuery->fetchAll();
	}

	$_SESSION['title'] = $title;
	$_SESSION['content'] = $content;
	$_SESSION['category'] = $category;
	$_SESSION['group'] = $group;
	$_SESSION['new_group'] = $newGroup;
	$_SESSION['new_category'] = $newCategory;
	$_SESSION['category_color'] = $color;

	if ($data_correct) {
		try {
			if ($group === "0") {
				$groupQuery = $db->prepare('INSERT INTO task_groups(task_group) VALUES (:newGroup)');
				$groupQuery->bindValue(':newGroup', $newGroup, PDO::PARAM_STR);
				$groupQuery->execute();
				
				$indexQuery = $db->prepare('SELECT id FROM task_groups ORDER BY id DESC LIMIT 1');
				$indexQuery->execute();

				$group = $indexQuery->fetch()['id'];
			}

			if ($category === "0") {
				$categoryQuery = $db->prepare('INSERT INTO categories(category, color) VALUES (:newCategory, :color)');
				$categoryQuery->bindValue(':newCategory', $newCategory, PDO::PARAM_STR);
				$categoryQuery->bindValue(':color', $color, PDO::PARAM_STR);
				$categoryQuery->execute();
				
				$indexQuery = $db->prepare('SELECT id FROM categories ORDER BY id DESC LIMIT 1');
				$indexQuery->execute();

				$category = $indexQuery->fetch()['id'];
			}
			
			if (isset($taskId)) {
				$taskQuery = $db->prepare("UPDATE tasks SET category_id = :category, group_id = :group, title = :title, content = :content, code = $code, visible = $visible WHERE tasks.id = :taskId");
				$taskQuery->bindValue(':taskId', $taskId, PDO::PARAM_INT);
			} else {
				$taskQuery = $db->prepare("INSERT INTO tasks(category_id, group_id, user_id, title, content, code, visible) VALUES (:category, :group, :user_id, :title, :content, $code, $visible)");
				$taskQuery->bindValue(':user_id', $_SESSION['ID_user'], PDO::PARAM_INT);
			}

			$taskQuery->bindValue(':category', $category, PDO::PARAM_INT);
			$taskQuery->bindValue(':group', $group, PDO::PARAM_INT);
			$taskQuery->bindValue(':title', $title, PDO::PARAM_STR);
			$taskQuery->bindValue(':content', $content, PDO::PARAM_STR);
			$taskQuery->execute();

			if (isset($taskId)) {
				$_SESSION['insert_correct'] = "Zadanie zostało zmienione";
				header('Location: edycja_zadania_'.$taskId);
			} else {
				$_SESSION['insert_correct'] = "Zadanie zostało dodane";
				header('Location: edycja_zadania_'.$db->lastInsertId());
			}

		} catch (PDOException $error) {
			$_SESSION['insert_error'] = "Coś poszło nie tak. Spróbuj jeszcze raz.";
		}

		delEmptyElements($db);
	}

}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title><?= isset($taskId) ? 'Edycja zadania' : 'Dodawanie nowego zadania' ?> | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1><?= isset($taskId) ? 'Edycja zadania' : 'Dodawanie nowego zadania' ?></h1>
			</header>

			<nav>
				<a href="<?= isset($taskId) ? 'zadanie_'.$taskId : './' ?>" class="button"><i class="icon-back"></i>&nbsp;Powrót</a>
			</nav>

			<main>
				<form class="in-rows" enctype="multipart/form-data" action="<?= isset($taskId) ? 'edycja_zadania_'.$taskId : 'dodawanie_zadania' ?>" method="post">
					<?php
						if (isset($_SESSION['insert_correct'])) {
							echo '<span class="alert green">'.$_SESSION['insert_correct'].'</span>';
							if (!isset($data_correct) || !$data_correct) unset($_SESSION['insert_correct']);
						}

						if (isset($_SESSION['insert_error'])) {
							echo '<span class="alert red">'.$_SESSION['insert_error'].'</span>';
							unset($_SESSION['insert_error']);
						}
					?>

					<input type="text" value="<?php
						if (isset($_SESSION['title'])) {
							echo $_SESSION['title'];
							unset($_SESSION['title']);
						}
					?>" name="title" placeholder="Podaj nazwę zadania" required>
					<?php
					if (isset($_SESSION['e_title'])) {
						echo '<span class="alert red">'.$_SESSION['e_title'].'</span>';
						unset($_SESSION['e_title']);
					}
					?>

					<textarea name="content" placeholder="Podaj treść zadania" required><?php
						if (isset($_SESSION['content'])) {
							echo $_SESSION['content'];
							unset($_SESSION['content']);
						}
					?></textarea>
					<?php
					if (isset($_SESSION['e_content'])) {
						echo '<span class="alert red">'.$_SESSION['e_content'].'</span>';
						unset($_SESSION['e_content']);
					}
					?>

					<label>
						<input type="checkbox" name="code"<?= isset($code) && $code || !isset($code) ? ' checked' : '' ?>>
						 Dodaj pole do wysyłania kodu programu
					</label>

					<label>
						<input type="checkbox" name="invisible"<?= isset($visible) && !$visible ? ' checked' : '' ?>>
						 Ukryj zadanie
					</label>

					<label>
						Wybierz kategorię:
						<select name="category">
							<option value="-1" <?= isset($_SESSION['category']) && $_SESSION['category'] == -1 ? 'selected':'' ?>>Wybierz...</option>
							<option value="0" <?= isset($_SESSION['category']) && $_SESSION['category'] == 0 ? 'selected':'' ?>>+ Dodaj nową</option>
							<?php
							foreach ($categories as $category) {
								$s = '';
								if (isset($_SESSION['category']) && $_SESSION['category'] == $category['id']) $s = 'selected';
								echo "<option value='{$category['id']}' {$s}>{$category['category']}</option>";
							}
							?>

						</select>
					</label>
					<?php
					if (isset($_SESSION['e_category'])) {
						echo '<span class="alert red">'.$_SESSION['e_category'].'</span>';
						unset($_SESSION['e_category']);
					}
					?>

					<div class="form-div<?= isset($_SESSION['category']) && $_SESSION['category'] == 0 ? '':' hide' ?>" id="form-div-category">
						<input type="<?php
							$t = 'hidden';
							if (isset($_SESSION['category'])) {
								if ($_SESSION['category'] == 0) $t = 'text';
								unset($_SESSION['category']);
							}
							echo $t;
						?>" name="new_category" value="<?php
							if (isset($_SESSION['new_category'])) {
								echo $_SESSION['new_category'];
								unset($_SESSION['new_category']);
							}
						?>" placeholder="Podaj nazwę kategorii" required>
						
						<input type="color" name="category_color" value="<?php
							if(isset($_SESSION['category_color'])) {
								echo $_SESSION['category_color'];
								unset($_SESSION['category_color']);
							}
						?>">
					</div>
					
					<?php
					if (isset($_SESSION['e_new_category'])) {
						echo '<span class="alert red">'.$_SESSION['e_new_category'].'</span>';
						unset($_SESSION['e_new_category']);
					}
					?>

					<label>
						Wybierz grupę:
						<select name="group">
							<option value="-1" <?= isset($_SESSION['group']) && $_SESSION['group'] == null ? 'selected':'' ?>>&#8709; Bez grupy</option>
							<option value="0" <?= isset($_SESSION['group']) && $_SESSION['group'] == 0 ? 'selected':'' ?>>+ Dodaj nową</option>
							<?php
							foreach ($groups as $groupOption) {
								$s = '';
								if (isset($_SESSION['group']) && $_SESSION['group'] == $groupOption['id']) $s = 'selected';
								echo "<option value='{$groupOption['id']}' {$s}>{$groupOption['task_group']}</option>";
							}
							?>

						</select>
					</label>
					<?php
					if (isset($_SESSION['e_group'])) {
						echo '<span class="alert red">'.$_SESSION['e_group'].'</span>';
						unset($_SESSION['e_group']);
					}
					?>

					<div class="form-div<?= isset($_SESSION['group']) && $_SESSION['group'] == 0 ? '':' hide' ?>" id="form-div-group">
						<input type="<?php
							$t = 'hidden';
							if (isset($_SESSION['group'])) {
								if ($_SESSION['group'] == 0) $t = 'text';
								unset($_SESSION['group']);
							}
							echo $t;
						?>" name="new_group" value="<?php
							if (isset($_SESSION['new_group'])) {
								echo $_SESSION['new_group'];
								unset($_SESSION['new_group']);
							}
						?>" placeholder="Podaj nazwę grupy" required>
					</div>

					<?php
					if (isset($_SESSION['e_new_group'])) {
						echo '<span class="alert red">'.$_SESSION['e_new_group'].'</span>';
						unset($_SESSION['e_new_group']);
					}
					?>

					<div class="form-file-div<?= (!isset($group) || $group < 1) ? ' hide':'' ?>">
						<header>
							<h3>Dołączone pliki:</h3>
						</header>

						<div>
					<?php
						if (isset($files)) foreach($files as $file) {
echo <<<END
\n							<div class="form-div file-selected">
								<span class="tile" id="fs{$file['id']}">{$file['name']}</span>
								<div class="button tile" id="{$file['id']}"><i class="icon-trash"></i></div>
							</div>

END;
						}
					?>
						</div>
						
						<input type="hidden" name="del" value="0">
						<div class="form-div add-file">
							<label for="file-upload" class="button">
								+ Dodaj plik
							</label>

							<input type="hidden" name="MAX_FILE_SIZE" value="20971520">
							<input id="file-upload" type="file" name="file-upload" accept="text/*, application/pdf, image/*, .doc, .docx, .xls, .xlsx, .odt, .ods">
						</div>

						<?php
							if (isset($_SESSION['e_file'])) {
								echo '<span class="alert red">'.$_SESSION['e_file'].'</span>';
								if (!isset($data_correct) || !$data_correct) unset($_SESSION['e_file']);
							}
						?>

					</div>

					<div class="form-answers-div<?= !isset($taskId) ? ' hide' : '' ?>">
						<header>
							<h3>Odpowiedzi:</h3>
						</header>

						<div>
						<?php
						if (isset($answers)) foreach ($answers as $ans)
echo <<<END
\n							<div class="form-div">
								<span class="tile">{$ans['answer']} [{$ans['points']} pkt]</span>
								<div class="button tile" id="a{$ans['id']}"><i class="icon-trash"></i></div>
							</div>

END;
						?>
						</div>

						<input type="hidden" name="del_ans" value="0">
						<div class="form-div add-task<?= !isset($answer) ? ' hide' : '' ?>">
							<input type="text" name="answer" placeholder="Podaj odpowiedź">
							<input type="number" name="points" placeholder="pkt" min="0" max="20" value="1">
						</div>

						<div class="form-div add-button<?= isset($answer) ? ' hide' : '' ?>">
							<div class="button longer"> + Dodaj odpowiedź </div>
						</div>

						<?php
							if (isset($_SESSION['e_answer'])) {
								echo '<span class="alert red">'.$_SESSION['e_answer'].'</span>';
								if (!isset($data_correct) || !$data_correct) unset($_SESSION['e_answer']);
							}
						?>
					</div>
								
					<input type="submit" value="<?= isset($taskId) ? 'Zatwierdź' : 'Dodaj zadanie' ?>">
				</form>
			</main>
		</div>
	</div>

	<?php include 'items/footer.php' ?>
</body>
</html>
