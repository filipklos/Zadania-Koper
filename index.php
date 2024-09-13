<?php

session_start();

require_once 'config/database.php';
require_once 'items/global_functions.php';

$sort = getFromPostOrCookie('sort', '1');
$filter = getFromPostOrCookie('filter', '0');
$category = getFromPostOrCookie('category', '%', '0');
$roll = getFromPostOrCookie('roll', '1');

setCookieFromPost('sort');
setCookieFromPost('filter');
setCookieFromPost('category');

try {

	isset($_SESSION['ID_user']) ? $ID_user = $_SESSION['ID_user'] : $ID_user = 0;

	$orderBy = 'title';
	$orderMethod = 'ASC';

	if ($sort == 0 || $sort == 1) $orderBy = 'id';
	if ($sort == 1 || $sort == 3) $orderMethod = 'DESC';

	switch ($filter) {
		case 1:  $filterOption = "= 2"; break;
		case 2:  $filterOption = "IS NULL"; break;
		case 3:  $filterOption = "REGEXP '[10]'"; break;
		default: $filterOption = "IS NULL OR 1=1";
	}

	(!isset($_SESSION['group_name']) || $_SESSION['group_name'] != 'admin') ?
		$visibilityCondition = "AND (visible = 1 OR tasks.user_id = $ID_user)"
		: $visibilityCondition = '';

	$tasksQuery = $db->prepare("SELECT tasks.id, title, content, category, color, max_points, visible
		FROM tasks
		INNER JOIN categories ON category_id = categories.id
		LEFT JOIN user_answers ON user_answers.task_id = tasks.id AND user_answers.user_id = $ID_user
		WHERE category_id LIKE :category AND (max_points $filterOption) $visibilityCondition
		ORDER BY $orderBy $orderMethod");
	$tasksQuery->bindValue(':category', $category, PDO::PARAM_STR);
	$tasksQuery->execute();

	$tasks = $tasksQuery->fetchAll();

	$categoriesQuery = $db->query("SELECT categories.id, category FROM categories
		WHERE (SELECT COUNT(category_id) FROM tasks WHERE category_id = categories.id $visibilityCondition) > 0
		ORDER BY category");
	$categories = $categoriesQuery->fetchAll();

} catch (PDOException $error) {

	$tasks = array();
	$categories = array();

}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title>KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<nav>
				<?php
					if (isset($_SESSION['correct_move']))
					{
						echo '<span class="alert green">'.$_SESSION['correct_move'].'</span>';
						unset($_SESSION['correct_move']);
					}
					if (isset($_SESSION['end']))
					{
						echo '<span class="alert red">Konto zostało usunięte</span>';
						session_unset();
					}
					if (isset($_SESSION['form_nick'])) unset($_SESSION['form_nick']);
					if (isset($_SESSION['form_name'])) unset($_SESSION['form_name']);
					if (isset($_SESSION['form_surname'])) unset($_SESSION['form_surname']);
					if (isset($_SESSION['form_class'])) unset($_SESSION['form_class']);
					if (isset($_SESSION['form_password1'])) unset($_SESSION['form_password1']);
					if (isset($_SESSION['form_password2'])) unset($_SESSION['form_password2']);
					if (isset($_SESSION['form_chosen_user'])) unset($_SESSION['form_chosen_user']);
					if (isset($_SESSION['e_nick'])) unset($_SESSION['e_nick']);
					if (isset($_SESSION['e_pass'])) unset($_SESSION['e_pass']);
					if (isset($_SESSION['e_pass1'])) unset($_SESSION['e_pass1']);
					if (isset($_SESSION['e_pass2'])) unset($_SESSION['e_pass2']);
					if (isset($_SESSION['e_bot'])) unset($_SESSION['e_bot']);
					if (isset($_SESSION['e_list'])) unset($_SESSION['e_list']);
					if (isset($_SESSION['correct_del'])) unset($_SESSION['correct_del']);
					if (isset($_SESSION['correct_up'])) unset($_SESSION['correct_up']);
					if (isset($_SESSION['selected_id'])) unset($_SESSION['selected_id']);

					if ((isset($_SESSION['logged'])) && ($_SESSION['logged'] == true))
					{
						if ($_SESSION['group_name'] == 'admin')
						{
							$blockMsg = 'Blokuj logowanie';
							$blockColor = 'auto';
							try {
								$blockQuery = $db->prepare("SELECT value FROM global_settings WHERE name = 'login-block'");
								$blockQuery->execute();

								$_SESSION['blockStatus'] = $blockQuery->fetch()['value'];
								if ($_SESSION['blockStatus'] == 'active') {
									$blockMsg = 'Odblokuj logowanie';
									$blockColor = '#C00';
								}
							}
							catch (PDOException $error) {}
echo <<<END
\n				<div class="user-nav">
					<ul class="nav-bar">
						<li class="user-name">{$_SESSION['user']}</li>
						<li class="button"><a href="dodawanie_zadania"><i class="icon-add"></i> Dodaj zadanie</a></li>
						<li class="button"><a href="zarzadzanie_uzytkownikami"><i class="icon-users"></i> Zarządzanie kontami</a></li>
						<li class="button"><a href="https://onedrive.live.com/view.aspx?resid=9D15A13490028EF1!35521&ithint=file%2cpptx&authkey=!AO3Z6qDsgjhp2mY" target="_blank"><i class="icon-angle-up"></i> Kurs C++</a></li>
						<li class="button"><a href="https://onedrive.live.com/edit?id=9D15A13490028EF1!36622&resid=9D15A13490028EF1!36622&ithint=file%2cpptx&authkey=!AIDvtDPNCynoRtE&wdo=2&cid=9d15a13490028ef1" target="_blank"><i class="icon-angle-up"></i> Kurs Python</a></li>
						<li class="button"><a href="algorytmy"><i class="icon-pi"></i> Algorytmy</a></li>
						<li class="button"><a class="print" data-param="category=$category"><i class="icon-print"></i> Drukuj</a></li>
						<li class="button"><a href="login_block.php" style="color:$blockColor"><i class="icon-block"></i> $blockMsg</a></li>
						<li class="button"><a href="zmiana_hasla"><i class="icon-pass"></i> Zmień hasło</a></li>
						<li class="button"><a href="log_out.php"><i class="icon-logout"></i> Wyloguj się</a></li>
					</ul>
				</div>

END;
						}
						else if ($_SESSION['group_name'] == 'user')
						{
echo <<<END
\n				<div class="user-nav">
					<ul class="nav-bar">
						<li class="user-name">{$_SESSION['user']}</li>
						<li class="button"><a href="osiagniecia"><i class="icon-good"></i> Osiągnięcia</a></li>
						<li class="button"><a href="https://onedrive.live.com/view.aspx?resid=9D15A13490028EF1!35521&ithint=file%2cpptx&authkey=!AO3Z6qDsgjhp2mY" target="_blank"><i class="icon-angle-up"></i> Kurs C++</a></li>
						<li class="button"><a href="https://onedrive.live.com/edit?id=9D15A13490028EF1!36622&resid=9D15A13490028EF1!36622&ithint=file%2cpptx&authkey=!AIDvtDPNCynoRtE&wdo=2&cid=9d15a13490028ef1" target="_blank"><i class="icon-angle-up"></i> Kurs Python</a></li>
						<li class="button"><a href="algorytmy"><i class="icon-pi"></i> Algorytmy</a></li>
						<li class="button"><a href="zmiana_hasla"><i class="icon-pass"></i> Zmień hasło</a></li>
						<li class="button"><a href="log_out.php"><i class="icon-logout"></i> Wyloguj się</a></li>
					</ul>
				</div>

END;
						}
						else if ($_SESSION['group_name'] == 'editor') {
echo <<<END
\n				<div class="user-nav">
					<ul class="nav-bar">
						<li class="user-name">{$_SESSION['user']}</li>
						<li class="button"><a href="dodawanie_zadania"><i class="icon-add"></i> Dodaj zadanie</a></li>
						<li class="button"><a href="osiagniecia"><i class="icon-good"></i> Osiągnięcia</a></li>
						<li class="button"><a href="https://onedrive.live.com/view.aspx?resid=9D15A13490028EF1!35521&ithint=file%2cpptx&authkey=!AO3Z6qDsgjhp2mY" target="_blank"><i class="icon-angle-up"></i> Kurs C++</a></li>
						<li class="button"><a href="https://onedrive.live.com/edit?id=9D15A13490028EF1!36622&resid=9D15A13490028EF1!36622&ithint=file%2cpptx&authkey=!AIDvtDPNCynoRtE&wdo=2&cid=9d15a13490028ef1" target="_blank"><i class="icon-angle-up"></i> Kurs Python</a></li>
						<li class="button"><a href="algorytmy"><i class="icon-pi"></i> Algorytmy</a></li>
						<li class="button"><a class="print" data-param="category=$category"><i class="icon-print"></i> Drukuj</a></li>
						<li class="button"><a href="zmiana_hasla"><i class="icon-pass"></i> Zmień hasło</a></li>
						<li class="button"><a href="log_out.php"><i class="icon-logout"></i> Wyloguj się</a></li>
					</ul>
				</div>

END;
						}
					}
					else
					{
						if (isset($_SESSION['form_login']))
						{
							$login = $_SESSION['form_login'];
							unset($_SESSION['form_login']);
						}
						else $login = "";
echo <<<ECHO
\n				<form class="login" action="sign_in.php" method="post">
					<input type="text" value="$login" name="login" placeholder="Login" required><input type="password" name="password" placeholder="Hasło" required><input type="submit" value="Zaloguj się">
				</form>
ECHO;
						if (isset($_SESSION['error']))
						{
							echo '<span class="alert red">'.$_SESSION['error'].'</span>';
							unset($_SESSION['error']);
						}
						echo '<span>Nie posiadasz konta? <a href="rejestracja">Zarejestruj się</a></span>';
						echo '<span>Kurs C++: <a href="https://onedrive.live.com/view.aspx?resid=9D15A13490028EF1!35521&ithint=file%2cpptx&authkey=!AO3Z6qDsgjhp2mY" target="_blank">Prezentacja</a></span>';
						echo '<span>Kurs Python: <a href="https://onedrive.live.com/edit?id=9D15A13490028EF1!36622&resid=9D15A13490028EF1!36622&ithint=file%2cpptx&authkey=!AIDvtDPNCynoRtE&wdo=2&cid=9d15a13490028ef1" target="_blank">Prezentacja</a></span>';
						echo '<span>Strona z algorytmami: <a href="algorytmy" style="display:inline-block">Algorytmy</a></span>';
						echo '<span>Strona do przesyłania plików
							<a href="https://tg.idsl.pl" target="_blank" style="display:inline-block"><i class="icon-external-link"></i>tg.idsl.pl</a>
						</span>';
					}
				?>

			</nav>
			
			<?php
			if (isset($_SESSION['del_task'])) {
				if ($_SESSION['del_task'])
					echo '<span class="alert green">Zadanie zostało usunięte.</span>';
				else
					echo '<span class="alert red">Coś poszło nie tak. Zadanie nie zostało usunięte. Spróbuj jeszcze raz.</span>';

				unset($_SESSION['del_task']);
			}
			?>
		</div>

		<div class="subcontainer">
			<header>
				<h1>KOPER Zadania</h1>
			</header>

			<nav>
				<div class="button filter">
					Filtry
					<div class="roll"><svg><path d="<?php
						if ($roll) echo 'M 0 10 l 7 -5 l 7 5';
						else echo 'M 0 7 l 7 5 l 7 -5';
					?>"></path></svg></div>
				</div>

				<div class="area<?= $roll ? ' show-area':'' ?>">
					<form class="filter-form" action="./" method="post">
						<div>
							<span class="filter-label">Filtry:</span>

							<input type="radio" name="filter" id="filter0" value="0" <?= $filter == "0" ? 'checked':'' ?>>
							<label class="button filter-option" for="filter0"> wszystkie </label>

							<input type="radio" name="filter" id="filter1" value="1" <?= $filter == "1" ? 'checked':'' ?>>
							<label class="button filter-option" for="filter1"> zrobione </label>

							<input type="radio" name="filter" id="filter2" value="2" <?= $filter == "2" ? 'checked':'' ?>>
							<label class="button filter-option" for="filter2"> niezrobione </label>

							<input type="radio" name="filter" id="filter3" value="3" <?= $filter == "3" ? 'checked':'' ?>>
							<label class="button filter-option" for="filter3"> w trakcie </label>
						</div>

						<div>
							<span class="filter-label">Sortuj wg:</span>

							<input type="radio" name="sort" id="sort0" value="0" <?= $sort == "0" ? 'checked':'' ?>>
							<label class="button filter-option" for="sort0"> od najstarszych </label>

							<input type="radio" name="sort" id="sort1" value="1" <?= $sort == "1" ? 'checked':'' ?>>
							<label class="button filter-option" for="sort1"> od najnowszych </label>

							<input type="radio" name="sort" id="sort2" value="2" <?= $sort == "2" ? 'checked':'' ?>>
							<label class="button filter-option" for="sort2"> A-Z </label>

							<input type="radio" name="sort" id="sort3" value="3" <?= $sort == "3" ? 'checked':'' ?>>
							<label class="button filter-option" for="sort3"> Z-A </label>
						</div>

						<div>
							<span class="filter-label">Kat.:</span>

							<input type="radio" name="category" id="cat0" value="0" <?= $category == "%" ? 'checked':'' ?>>
							<label class="button filter-option" for="cat0"> all </label>

							<?php
							foreach ($categories as $cat) {
								echo '<input type="radio" name="category" id="cat'.$cat["id"].'" value="'.$cat["id"].'"'.($category == $cat["id"] ? ' checked':'').'>';
								echo '<label class="button filter-option" for="cat'.$cat["id"].'"> '.$cat["category"].' </label>';
							}
							?>
						</div>
					</form>
				</div>
			</nav>

			<main>
				<section>

					<?php
					foreach ($tasks as $task) {
						$content = htmlentities($task['content']);
echo <<<END
\n					<article>
						<a href="zadanie_{$task['id']}">
							<div class="frame frame-list">
								<header>
									<div class="bar">
										<div class="category" style="background-color:{$task['color']}">
											{$task['category']}
										</div>
										<div class="bar-options">
END;
										if (!$task['visible']) echo '<div class="bar-icon"><i class="icon-invisible"></i></div>';
										if ($task['max_points'] !== null) echo '<div class="bar-icon">'.getResultIcon($task['max_points']).'</div>';
echo <<<END
\n										</div>
									</div>

									<h2>{$task['title']}</h2>
								</header>

								<div class="content content-list">$content</div>
							</div>
						</a>
					</article>
END;
					}
					?>

				</section>
			</main>
		</div>
	</div>
	<?php include 'items/footer.php' ?>
</body>
</html>
