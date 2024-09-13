<?php

session_start();

if ((!isset($_SESSION['logged'])) || ($_SESSION['logged'] == false))
{
	header('Location: ./');
	exit();
}

require_once "config/database.php";
require_once "items/global_functions.php";

$userId = '%';
$taskId = '%';
$categoryId = '%';
$className = '%';

$groupFilter = getFromPostOrCookie('groupMU', '%', '0');
$categoryFilter = getFromPostOrCookie('category', '%', '0');
$roll = getFromPostOrCookie('roll', '1');

setCookieFromPost('groupMU');
setCookieFromPost('category');

if ($_SESSION['group_name'] == 'admin') {
	if (isset($_POST['user_id'])) $userId = $_POST['user_id'];
	else if (isset($_GET['id'])) $taskId = $_GET['id'];
	else {
		header('Location: zarzadzanie_uzytkownikami');
		exit();
	}

	if ($groupFilter != '0') $className = $groupFilter;

	$classGroupsQuery = $db->query('SELECT ID_user AS id, class_name FROM users WHERE class_name IS NOT NULL GROUP BY class_name ORDER BY class_name');
	$classGroupsArray = $classGroupsQuery->fetchAll();
} else {
	$userId = $_SESSION['ID_user'];

	$categoryQuery = $db->query("SELECT id, category FROM categories ORDER BY category");
	$categories = $categoryQuery->fetchAll();
}

$categoryId = $categoryFilter;

$data_correct = true;

if ($userId != '%') {
	$userQuery=$db->prepare("SELECT group_id, name, surname FROM users WHERE ID_user = :u_id");
	$userQuery->bindValue(':u_id', $userId, PDO::PARAM_INT);
	$userQuery->execute();

	$user_data = $userQuery->fetch();
	
	if(!$user_data) {
		$data_correct = false;
		$_SESSION['e_id'] = "Nie udało się wczytać danych użytkownika";
	}
	else {
		if ($user_data["group_id"] == 1) {
			$data_correct = false;
			$_SESSION['e_id'] = "Nie można wyświetlić osiągnięć dla administratora";
		}
	}
}
else {
	$taskQuery=$db->prepare("SELECT title FROM tasks WHERE id = :task_id");
	$taskQuery->bindValue(':task_id', $taskId, PDO::PARAM_INT);
	$taskQuery->execute();

	$task = $taskQuery->fetch();
	
	if (!$task) {
		$data_correct = false;
		$_SESSION['e_id'] = "Nie udało się wczytać statystyk zadania";
	}
}

if($data_correct == true){
	$groupMethod = $taskId == '%' ? 'GROUP BY tasks.id' : 'GROUP BY user_answers.user_id';
	$answersQuery=$db->prepare("SELECT user_answers.points, MAX(answers.points) AS max_points, tasks.id, user_answers.user_id as user_id, title, content, codes.code, codes.id as code_id, category, color, visible, user_answers.max_points AS max, name, surname
		FROM user_answers
		LEFT JOIN tasks
		ON user_answers.task_id=tasks.id
		LEFT JOIN codes
		ON user_answers.user_id=codes.user_id AND tasks.id=codes.task_id
		LEFT JOIN answers
		ON answers.task_id=tasks.id
		LEFT JOIN categories
		ON categories.id=tasks.category_id
		LEFT JOIN users
		ON users.ID_user=user_answers.user_id
		WHERE user_answers.user_id LIKE :u_id AND tasks.id LIKE :task_id AND tasks.category_id LIKE :category_id AND users.class_name LIKE :class_name
		$groupMethod
		ORDER BY users.name, user_answers.id DESC");
	$answersQuery->bindValue(':u_id', $userId, PDO::PARAM_STR);
	$answersQuery->bindValue(':task_id', $taskId, PDO::PARAM_STR);
	$answersQuery->bindValue(':category_id', $categoryId, PDO::PARAM_STR);
	$answersQuery->bindValue(':class_name', $className, PDO::PARAM_STR);
	$answersQuery->execute();

	$user_answers = $answersQuery->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title><?= $taskId == '%' ? 'Osiągnięcia' : 'Statystyki zadania' ?> | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
	<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
	<script type="text/javascript" id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1>
					<?= $taskId == '%' ?
					"Osiągnięcia: {$user_data['name']} {$user_data['surname']}"
					: "Statystyki zadania: {$task['title']}" ?>

				</h1>
			</header>

			<nav>
				<?php
					if (isset($_SESSION['e_id']))
					{
						echo '<span class="alert red">'.$_SESSION['e_id'].'</span>';
						unset($_SESSION['e_id']);
					}
				?>
				<a href="<?=
					$taskId == '%' ? 'zarzadzanie_uzytkownikami' : 'zadanie_'.$taskId
				?>" class="button float"><i class="icon-back"></i>&nbsp;Powrót</a>

				<div class="button filter">
					Filtry
					<div class="roll"><svg><path d="<?php
						if ($roll) echo 'M 0 10 l 7 -5 l 7 5';
						else echo 'M 0 7 l 7 5 l 7 -5';
					?>"></path></svg></div>
				</div>

				<div class="area<?= $roll ? ' show-area':'' ?>">
					<form class="filter-form" action="" method="post">
						<div>
							<?php
							if ($_SESSION['group_name'] != 'admin') {
								$checked = $categoryId == "%" ? 'checked' : '';
echo <<<END
\n							<span class="filter-label">Kategorie:</span>

							<input type="radio" name="category" id="cat0" value="0" $checked>
							<label class="button filter-option" for="cat0"> wszystkie </label>
END;
								foreach ($categories as $cat) {
									echo '<input type="radio" name="category" id="cat'.$cat["id"].'" value="'.$cat["id"].'"'.($categoryId == $cat["id"] ? ' checked':'').'>';
									echo '<label class="button filter-option" for="cat'.$cat["id"].'"> '.$cat["category"].' </label>';
								}
							} else if ($taskId != '%') {
								$checked = $groupFilter == "%" ? 'checked' : '';
echo <<<END
\n							<span class="filter-label">Grupy:</span>

							<input type="radio" name="groupMU" id="gr0" value="0" $checked>
							<label class="button filter-option" for="gr0"> wszystkie </label>
END;
								foreach ($classGroupsArray as $gr) {
									echo '<input type="radio" name="groupMU" id="gr'.$gr["id"].'" value="'.$gr["class_name"].'"'.($groupFilter == $gr["class_name"] ? ' checked':'').'>';
									echo '<label class="button filter-option" for="gr'.$gr["id"].'"> '.$gr["class_name"].' </label>';
								}
							}
							?>
						</div>
					</form>
				</div>
			</nav>

			<main>
				<?php
					foreach ($user_answers as $user_answer) {
						$shown = $user_answer['visible'] || $_SESSION['group_name'] == 'admin';
echo <<<END
\n				<article>
					<div class="frame">
						<header>
							<div class="bar">
END;
						$is_html_code = false;
						if($user_answer['code']) {
							$is_html_code = preg_match("/<body[\S\s]+<\/body[\S\s]/i", $user_answer['code']) + preg_match("/<html[\S\s]+<\/html[\S\s]/i", $user_answer['code']);
						}
								
						if ($taskId == '%') {
							$content = str_replace("\n", '<br>', $user_answer['content']);
echo <<<END
\n								<div class="category" style="background-color:{$user_answer['color']}">
									{$user_answer['category']}
								</div>
								<div class="bar-options">
END;
							if ($is_html_code && $user_answer['code']) echo '<a class="bar-element" target="_blank" href="html_viewer.php?code_id='.$user_answer['code_id'].'"><i class="icon-external-link"></i>Wyświetl stronę</a></br>';
							if ($_SESSION['group_name'] == 'admin' && $user_answer['code']) echo '<a class="bar-element" target="_blank" href="porownanie?user='.$userId.'&task='.$user_answer['id'].'"><i class="icon-compare"></i>Porównaj</a>';
							if ($user_answer['max'] !== null) echo '<div class="bar-icon">'.getResultIcon($user_answer['max']).'</div>';
echo <<<END
\n								</div>
							</div>

							<h2>{$user_answer['title']}</h2>
						</header>
							
						<h3>{$user_answer['points']}/{$user_answer['max_points']}</h3>
END;
							if ($shown) echo '<div class="content content-list more">'.$content.'</div>';
							else echo '<h3><i class="icon-invisible"></i>Treść zadania została ukryta</h3>';
						} else {
echo <<<END
\n								{$user_answer['points']}/{$user_answer['max_points']}
								<div class="bar-options">
END;
							if ($is_html_code && $user_answer['code']) echo '<a class="bar-element" target="_blank" href="html_viewer.php?code_id='.$user_answer['code_id'].'"><i class="icon-external-link"></i>Wyświetl stronę</a></br>';
							if ($_SESSION['group_name'] == 'admin' && $user_answer['code']) echo '<a class="bar-element" target="_blank" href="porownanie?user='.$user_answer['user_id'].'&task='.$user_answer['id'].'"><i class="icon-compare"></i>Porównaj</a>';
							if ($user_answer['max'] !== null) echo '<div class="bar-icon">'.getResultIcon($user_answer['max']).'</div>';
echo <<<END
\n								</div>		
							</div>

							<h2>{$user_answer['name']} {$user_answer['surname']}</h2>
						</header>
END;
						}

						/*
						if ($user_answer['code']) {
							$regex_url = "/(http|https|ftp|ftps)\:\/\/[^\s\"]*(^\")?/"; // Dziekujemy ChatGPT za tego regexa :)
							$regex_ip  = '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/';

							$links_found = preg_match_all($regex_url, $user_answer['code'], $links);
							$ips_found   = preg_match_all($regex_ip , $user_answer['code'], $ips  );
							$links_content = '';

							if($links_found > 0) {
								foreach($links[0] as $link) {
									$links_content .= $link;
echo <<<END
\n						<a href="{$link}" class="button button-block" target="_blank"><i class="icon-external-link"></i> {$link}</a>
END;							
								}
							}
							if($ips_found > 0) {
								foreach($ips[0] as $ip) {
									$links_content .= $link;
echo <<<END
\n						<a href="http://{$ip}" class="button button-block" target="_blank"><i class="icon-external-link"></i> {$ip}</a>
END;				
								}
							}

							$code_without_spaces = str_replace(array(" ", ",", "\n", "\r"), "", $user_answer['code']);
						}
						*/

						if ($user_answer['code'] && $shown/* && $links_content != $code_without_spaces*/) {
echo <<<END
\n						<textarea class="text_long" name="code" readonly>
{$user_answer['code']}
						</textarea>
END;
						}
echo <<<END
\n					</div>
				</article>
END;
					}
				?>

			</main>
		</div>
	</div>

	<script>

	// To dziala tylko kiedy script.min.js lub script.js jest najpierw zawarty gdzies w pliku! Inaczej autoGrow nie jest zdefiniowany
	let textareas = document.getElementsByTagName('textarea');
	for(let i = 0; i < textareas.length; i++) {
		autoGrow(textareas[i]);
	}
	
	</script>

	<?php include 'items/footer.php' ?>
</body>
</html>