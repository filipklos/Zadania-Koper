<?php

session_start();

require_once 'config/database.php';
require_once 'items/global_functions.php';

try {

	if (isset($_GET['id'])) {

		isset($_SESSION['ID_user']) ? $ID_user = $_SESSION['ID_user'] : $ID_user = 0;
		$taskId = $_GET['id'];

		$taskQuery = $db->prepare("SELECT tasks.id, title, content, category, category_id, group_id, tasks.user_id, color, max_points, code, visible
			FROM tasks
			INNER JOIN categories ON category_id = categories.id
			LEFT JOIN user_answers ON user_answers.task_id = tasks.id AND user_answers.user_id = $ID_user
			WHERE category_id = categories.id AND tasks.id = :taskId");
		$taskQuery->bindValue(':taskId', $taskId, PDO::PARAM_INT);
		$taskQuery->execute();

		$task = $taskQuery->fetch();

		if (!empty($task)) {

			if (!$task['visible'] && (!isset($_SESSION['logged']) || $_SESSION['group_name'] != 'admin' && !isAuthor($db, $_GET['id'], $ID_user)))
				throw new Exception('No access');
			
			$max = $task['max_points'];
			$groupId = $task['group_id'];
			
			if ($groupId != null) {
				$filesQuery = $db->prepare("SELECT title, name FROM files WHERE task_group_id = $groupId");
				$filesQuery->execute();
				$files = $filesQuery->fetchAll();
			}

			if (isset($_SESSION['logged']) && $_SESSION['logged']) {

				if ($_SESSION['group_name'] == 'admin') {
					$answersQuery = $db->prepare("SELECT answer, points FROM answers WHERE task_id = :taskId");
					$answersQuery->bindValue(':taskId', $taskId, PDO::PARAM_INT);
					$answersQuery->execute();

					$answers = $answersQuery->fetchAll();
					$answersNumber = $answersQuery->rowCount();

				} else {
					$answersQuery = $db->prepare("SELECT COUNT(id) AS number, MAX(points) AS max FROM answers WHERE task_id = :taskId");
					$answersQuery->bindValue(':taskId', $taskId, PDO::PARAM_INT);
					$answersQuery->execute();

					$answers = $answersQuery->fetch();
					$answersNumber = $answers['number'];
					$pointsMax = $answers['max'];

					$condition = isset($_POST['send']) && $answersNumber == 0;

					if (isset($_POST['answer']) || $condition) {
						try {
							if (isset($_POST['answer'])) {
								$cookieName = 'answer_' . $taskId;
								if (isset($_COOKIE[$cookieName])) throw new Exception('Odpowiedzi możesz wysyłać co 10 sekund!');
								
								setcookie($cookieName, true, time() + 10);
								$answer = $_POST['answer'];

								if ($answersNumber > 0) {
									$pointsQuery = $db->prepare("SELECT points FROM answers WHERE task_id = :taskId AND
										REPLACE(REPLACE(REPLACE(REPLACE(answer, '\r', ''), '\t', ''), '\n', ''), ' ', '') = :answer");
									$pointsQuery->bindValue(':taskId', $taskId, PDO::PARAM_INT);
									$pointsQuery->bindValue(':answer', str_replace([" ", "\r", "\t", "\n"], '', $answer), PDO::PARAM_STR);
									$pointsQuery->execute();

									$points = $pointsQuery->fetch();
									!empty($points) ? $pointsNumber = $points['points'] : $pointsNumber = 0;
								}
							}
							else $pointsNumber = 1;

							$actPointsQuery = $db->prepare("SELECT id, points FROM user_answers WHERE user_id = {$_SESSION['ID_user']} AND task_id = :taskId");
							$actPointsQuery->bindValue(':taskId', $taskId, PDO::PARAM_INT);
							$actPointsQuery->execute();

							$actPoints = $actPointsQuery->fetch();

							if ($condition) $currentMax = 2;
							else if ($pointsNumber == 0) $currentMax = 0;
							else $pointsMax == $pointsNumber ? $currentMax = 2 : $currentMax = 1;

							if ($currentMax > $max || !$max) $max = $currentMax;

							if (empty($actPoints)) {
								$saveAnswer = $db->prepare("INSERT INTO user_answers(user_id, task_id, points, max_points, date) VALUES({$_SESSION['ID_user']}, :taskId, {$pointsNumber}, {$currentMax}, '".date('Y-m-d')."')");
								$saveAnswer->bindValue(':taskId', $taskId, PDO::PARAM_INT);
								$saveAnswer->execute();
							} else if ($pointsNumber > $actPoints['points']) {
								$updateAnswer = $db->prepare("UPDATE user_answers SET points = {$pointsNumber}, max_points = {$currentMax}, date = '".date('Y-m-d')."' WHERE id = {$actPoints['id']}");
								$updateAnswer->execute();
							}
						}
						catch (PDOException $error) {
							$_SESSION['e_save_answer'] = "Coś poszło nie tak. Nie udało się zapisać twojej odpowiedzi do bazy.";
						}
						catch (Exception $error) {
							$_SESSION['e_save_answer'] = $error->getMessage();
						}
					}
				}

				$codeQuery = $db->prepare("SELECT code, id FROM codes WHERE user_id = $ID_user AND task_id = $taskId");
				$codeQuery->execute();

				$codeResult = $codeQuery->fetch();
				if(empty($codeResult)) {
					$code = null;
					$codeID = null;
				} else {
					$code = $codeResult['code'];
					$codeID = $codeResult['id'];
				}

				if (isset($_POST['code'])) {
					try {
						if ($code == null)
							$saveCode = $db->prepare("INSERT INTO codes(user_id, task_id, code) VALUES($ID_user, $taskId, :code)");
						else
							$saveCode = $db->prepare("UPDATE codes SET code = :code WHERE user_id = $ID_user AND task_id = $taskId");

						$saveCode->bindValue(':code', $_POST['code'], PDO::PARAM_STR);
						$saveCode->execute();

						if($codeID == null) {
							$codeIDQuery = $db->prepare("SELECT id FROM codes WHERE user_id = $ID_user AND task_id = $taskId");
							$codeIDQuery->execute();
							$codeID = $codeIDQuery->fetch()['id'];
						}

						$code = $_POST['code'];
						$_SESSION['save_code'] = "Twój kod został poprawnie zapisany.";
					}
					catch (PDOException $error) {
						$_SESSION['e_save_code'] = "Coś poszło nie tak. Nie udało się zapisać twojego kodu do bazy.";
					}
				}

			}
			
			if ($groupId != null) {
				(!isset($_SESSION['group_name']) || $_SESSION['group_name'] != 'admin') ?
					$visibilityCondition = "AND (visible = 1 OR tasks.user_id = $ID_user)"
					: $visibilityCondition = '';

				$relatedTasksQuery = $db->prepare("SELECT id, title, visible FROM tasks WHERE group_id = $groupId $visibilityCondition ORDER BY title");
				$relatedTasksQuery->execute();

				$relatedTasksNo = $relatedTasksQuery->rowCount();
				$relatedTasks = $relatedTasksQuery->fetchAll();
			}

			if (!isset($relatedTasks) || $relatedTasks == null) {
				$relatedTasks = array();
				$relatedTasksNo = 0;
			}

		} else throw new Exception('There is no record for the given id.');

	} else throw new Exception('No given id.');

} catch (Exception $error) {

	header('Location: ./');
	exit();

}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title><?= $task['title'] ?> | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
	<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
	<script type="text/javascript" id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header class="task-title-header">
				<div class="bar">
					<div class="category" style="background-color:<?= $task['color'] ?>">
						<?= $task['category'] . "\n" ?>
					</div>
					<?php
					$condition = isset($_SESSION['group_name'])
						&& ($_SESSION['group_name'] == 'admin'
						|| $_SESSION['group_name'] == 'editor' && $_SESSION['ID_user'] == $task['user_id']);
					?>

					<div class="bar-options">
						<?= $condition ? '<a class="bar-element" href="visible.php?id='.$task['id'].'&v='.$task['visible'].'"><i class="icon-'.($task['visible'] ? 'invisible' : 'visible').'"></i>'.($task['visible'] ? 'Ukryj' : 'Pokaż').'</a>' : '' ?>

						<?= isset($_SESSION['group_name']) && $_SESSION['group_name'] == 'admin' ? '<a class="bar-element" href="statystyki_zadania_'.$task['id'].'"><i class="icon-stats"></i>Statystyki</a>' : '' ?>
						
						<?= $condition ? '<a class="bar-element print" data-param="task='.$taskId.'"><i class="icon-print"></i>Drukuj</a>'."\n" : '' ?>
						<?= $condition ? '<a class="bar-element" href="edycja_zadania_'.$task['id'].'"><i class="icon-pencil"></i>Edytuj</a>'."\n" : '' ?>
						<?= $condition ? '<div class="bar-element del-task"><i class="icon-trash"></i>Usuń</div>'."\n" : '' ?>
						<?= $max !== null ? '<div class="bar-icon">'.getResultIcon($max).'</div>' : '' ?>

					</div>
				</div>

				<h2><?= $task['title'] ?></h2>
				<span class="id hide"><?= $task['id'] ?></span>
			</header>

			<nav>
				<a href="./" class="button"><i class="icon-back"></i>&nbsp;Powrót</a>
			</nav>

			<?php
				if ($relatedTasksNo > 0) {
echo <<<END
\n			<aside>
				<div class="frame">
					<header>
						<h3>Powiązane zadania:</h3>
					</header>
					<div class="button-blocks">
END;
					foreach($relatedTasks as $relatedTask) {
						$current = $relatedTask['id'] == $taskId;
						echo '<a href="'.($current ? '#' : 'zadanie_'.$relatedTask['id'])
						.'" class="'.($current ? 'active-button ' : '')
						.'button button-block">'
							.(!$relatedTask['visible'] ? '<i class="icon-invisible"></i> ':'').$relatedTask['title']
						.'</a>';
					}

					if (isset($_SESSION['group_name']) && $_SESSION['group_name'] != 'user') {
						echo '<a href="add_task.php
							?title='.$task['title'].'
							&content='.str_replace(["\n", '"', "&", "<", ">", "+", "#"], ['%0A', '%22', '%26', '%3C', '%3E', '%2B', '%23'], $task['content']).'
							&category='.$task['category_id'].'
							&group='.$task['group_id'].'
							&code='.$task['code'].'
						" class="button button-block">&nbsp;+&nbsp;</a>';
					}
echo <<<END
</div>
				</div>
			</aside>

END;
				}
			?>
			
			<main>

				<article>
					<div class="frame">
						<div class="content"><?= str_replace("\n", '<br>', $task['content']) ?></div>
						<?php
							if(isset($files)) {
								foreach($files as $file)
									echo '<a href="files/'.$task['group_id'].'/'.$file['name'].'" class="button file" download><i class="icon-download"></i> '.$file['title'].'</a>';
							}
						?>

					</div>
				</article>

				<?php
				if (isset($_SESSION['logged']) && $answersNumber > 0) {
echo <<<END
\n				<article>
					<div class="frame">
END;
					if ($_SESSION['group_name'] == 'admin') {

echo <<<END
\n						<header>
							<h3>Odpowiedzi:</h3>
						</header>

						<ul>
END;
						foreach ($answers as $answer) echo "<li>{$answer['answer']} [{$answer['points']} pkt]</li>";
						echo "</ul>";

					} else {

echo <<<END
\n						<form class="answer" action="{$_SERVER['REQUEST_URI']}" method="post">
							<input type="text" name="answer" placeholder="Podaj odpowiedź" value="" required><input type="submit" value="Sprawdź">
						</form>
END;
						if (isset($answer)) {

							if ($pointsNumber == 0) $colorClass = "red";
							else if ($pointsNumber == $pointsMax) $colorClass = "green";
							else $colorClass = "orange";

							echo "<span class='alert {$colorClass} stamp'>Zdobyłeś: {$pointsNumber} / {$pointsMax} pkt</span>";
						}
						if (isset($_SESSION['e_save_answer'])) {
							echo "<span class='alert red'>{$_SESSION['e_save_answer']}</span>";
							unset($_SESSION['e_save_answer']);
						}
					}

echo <<<END
\n					</div>
				</article>
END;
				}
				?>
				
				<?php
				if (isset($_SESSION['logged']) && $_SESSION['group_name'] != 'admin' && $task['code']) {
					$max === null && $answersNumber > 0 ? $dis = 'readonly' : $dis = '';
					$max === null && $answersNumber == 0 ? $send = 'send' : $send = '';

					$is_html_code = false;
					if (isset($code) && isset($codeID)) {
						$is_html_code = preg_match("/<body[\S\s]+<\/body[\S\s]/i", $code) + preg_match("/<html[\S\s]+<\/html[\S\s]/i", $code);
					
						if($is_html_code) {
							echo '<div class="frame">';
							echo '<a target="_blank" href="html_viewer.php?code_id='.$codeID.'" class="button button-block" target="_blank" href="html_viewer.php?code_id='.$codeID.'"><i class="icon-external-link"></i>Wyświetl stronę</a>';
							echo '<br/>';
							echo '</div>';	
						}
					}

echo <<<END
\n				<article>
					<div class="frame">
						<form class="in-rows" action="{$_SERVER['REQUEST_URI']}" method="post">
							<input class="hide" type="checkbox" name="send">
END;
							echo '<input type="'.($dis ? 'button' : 'submit').'" class="'.($dis ? 'disabled-button':'').$send.'" value="'.($code != null ? 'Zapisz' : 'Wyślij').'">';
							if (isset($_SESSION['e_save_code'])) {
								echo "<span class='alert red'>{$_SESSION['e_save_code']}</span>";
								unset($_SESSION['e_save_code']);
							}
							if (isset($_SESSION['save_code'])) {
								echo "<span class='alert green'>{$_SESSION['save_code']}</span>";
								unset($_SESSION['save_code']);
							}
echo <<<END
\n
							<textarea name="code" placeholder="Podaj kod programu..." required $dis>
END;		
					if (isset($code)) echo $code;
					echo '</textarea>';
					
					
echo <<<END
\n						</form>
					</div>
				</article>
END;
				}
				?>

			</main>
			<script>

			// To dziala tylko kiedy script.min.js lub script.js jest najpierw zawarty gdzies w pliku! Inaczej autoGrow nie jest zdefiniowany
			let textareas = document.getElementsByTagName('textarea');
			for(let i = 0; i < textareas.length; i++) {
				autoGrow(textareas[i]);
			}

			</script>
		</div>
	</div>
	<?php include 'items/footer.php' ?>
</body>
</html>
