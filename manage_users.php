<?php
session_start();
if ((!isset($_SESSION['logged'])) || ($_SESSION['logged'] == false) || ($_SESSION['group_name'] != 'admin'))
{
	header('Location: ./');
	exit();
}

require_once "config/database.php";
require_once "items/global_functions.php";

$sort_column = getFromPostOrCookie('sort_column', 'surname');
$sort_order = getFromPostOrCookie('sort_order', 'ASC');
$groupMU = getFromPostOrCookie('groupMU', '%', '0');
$categoryMU = getFromPostOrCookie('categoryMU', '%', '0');
$today = getFromPostOrCookie('today', '0');

setCookieFromPost('sort_column');
setCookieFromPost('sort_order');
setCookieFromPost('groupMU');
setCookieFromPost('categoryMU');
setCookieFromPost('today');

try {
	$usersQuery = $db->prepare("SELECT ID_user, username, group_id, name, surname, class, class_name FROM users".($groupMU != '%' ? ' WHERE class_name LIKE :group':''));
	if ($groupMU != '%') $usersQuery->bindValue(':group', $groupMU, PDO::PARAM_STR);
	$usersQuery->execute();

	$usersArray = $usersQuery->fetchAll();

	$tasksQuery = $db->prepare("SELECT user_answers.user_id, points FROM user_answers, tasks WHERE tasks.id = user_answers.task_id AND category_id LIKE :category AND user_answers.date >= :date");
	$tasksQuery->bindValue(':date', $today, PDO::PARAM_STR);
	$tasksQuery->bindValue(':category', $categoryMU, PDO::PARAM_STR);
	$tasksQuery->execute();

	$tasksArray = $tasksQuery->fetchAll();

	$groupsQuery = $db->query('SELECT ID_group, group_name FROM user_groups');
	$groupsArray = $groupsQuery->fetchAll();

	$classGroupsQuery = $db->query('SELECT ID_user AS id, class_name FROM users WHERE class_name IS NOT NULL GROUP BY class_name ORDER BY class_name');
	$classGroupsArray = $classGroupsQuery->fetchAll();

	$categoriesQuery = $db->query('SELECT id, category FROM categories ORDER BY category');
	$categoriesArray = $categoriesQuery->fetchAll();

	foreach ($usersArray as &$user_in_array) {
		if ($user_in_array['group_id'] != 1) {
			if (!isset($user_in_array['points'])) {
				$user_in_array['points'] = 0;
			}
			foreach ($tasksArray as $task) {
				if ($task['user_id'] == $user_in_array['ID_user']) {
					$user_in_array['points'] += ($task['points'] > 0 ? 1 : 0);
				}
			}
		}
	}

	usort($usersArray, function($a, $b) use ($sort_column, $sort_order) {
		return compareUsers($a, $b, $sort_column, $sort_order);
	});
}
catch (PDOException $error) {
	header('Location: ./');
	exit();
}

if (isset($_SESSION['selected_id'])) unset($_SESSION['selected_id']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title>Zarządzanie kontami | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<nav>
				<?php
					if ($today == 0) {
						$today = date('Y-m-d');
						$icon = '';
						$text = 'All';
					} else {
						$today = 0;
						$icon = '-checked';
						$text = 'Dzisiaj';
					}
echo <<<END
\n				<div class="user-nav">
					<form action="zarzadzanie_uzytkownikami" method="post">
						<ul class="nav-bar">
							<li class="button"><a href="./"><i class="icon-back"></i>&nbsp;Powrót</a></li>
							
							<li class="button"><a href="dodawanie_kont"><i class="icon-user-add"></i> Dodaj konto</a></li>
							<li class="button"><a class="form-action" id="usuwanie_kont"><i class='icon-user-del'></i> Usuń użytkowników</a></li>
							<li class="button"><a class="form-action" id="zwiekszanie_klasy"><i class='icon-grow-up'></i> Zwiększ klasę</a></li>
							<li class="button"><a class="form-action" id="nazywanie_grup"><i class='icon-group'></i> Wpisz grupę</a></li>
							<li class="button"><a class="form-action" id="usuwanie_punktow"><i class='icon-reset-points'></i> Zeruj pkt</a></li>
							
							<li class="button">
								<input class="hide" type="text" name="today" value="$today">
								<input class="hide" type="submit" id="today">
								<label class="form-action" for="today"><i class='icon-calendar$icon'></i> $text</label>
							</li>
						</ul>
					</form>
				</div>

END;
				?>
			</nav>
		</div>
		<div class="subcontainer">
			<nav>
				<?php
					if (isset($_SESSION['correct_move']))
					{
						echo '<span class="alert green">'.$_SESSION['correct_move'].'</span>';
						unset($_SESSION['correct_move']);
					}
				?>

				<div class="area show-area">
					<form class="filter-form" action="zarzadzanie_uzytkownikami" method="post">
						<div>
							<span class="filter-label">Grupy:</span>

							<input type="radio" name="groupMU" id="gr0" value="0" <?= $groupMU == "%" ? 'checked':'' ?>>
							<label class="button filter-option" for="gr0"> all </label>

							<?php
							foreach ($classGroupsArray as $gr) {
								echo '<input type="radio" name="groupMU" id="gr'.$gr["id"].'" value="'.$gr["class_name"].'"'.($groupMU == $gr["class_name"] ? ' checked':'').'>';
								echo '<label class="button filter-option" for="gr'.$gr["id"].'"> '.$gr["class_name"].' </label>';
							}
							?>
						</div>

						<div>
							<span class="filter-label">Kat.:</span>

							<input type="radio" name="categoryMU" id="cat0" value="0" <?= $categoryMU == "%" ? 'checked':'' ?>>
							<label class="button filter-option" for="cat0"> all </label>

							<?php
							foreach ($categoriesArray as $cat) {
								echo '<input type="radio" name="categoryMU" id="cat'.$cat["id"].'" value="'.$cat["id"].'"'.($categoryMU == $cat["id"] ? ' checked':'').'>';
								echo '<label class="button filter-option" for="cat'.$cat["id"].'"> '.$cat["category"].' </label>';
							}
							?>
						</div>
					</form>
				</div>
			</nav>

			<main>
				<div class="table">
					<form id="users_data" method="post">
						<input type="text" name="category" class="hide" value="<?= $categoryMU ?>">

						<table>
							<tr>
								<th><input type="checkbox" id="option-all"></th>
								<th>
									<form method="post">
										<input type="hidden" name="sort_column" value="name">
										<input type="hidden" name="sort_order" value="<?= ($sort_column == 'name' && $sort_order == 'ASC') ? 'DESC' : 'ASC' ?>">
										<input type="submit" id="NameSubmit" hidden>
										<label for="NameSubmit">
											Imię
											<?php
												if ($sort_column == 'name') {
													if ($sort_order == 'ASC') {
														echo '<span style="all: unset;">↑</span>';
														echo '<span style="all: unset; color: #ccc">↓</span>';
													}
													else {
														echo '<span style="all: unset; color: #ccc">↑</span>';
														echo '<span style="all: unset;">↓</span>';
													}
												}
												else {
													echo '<span style="all: unset; color: #ccc">↑</span>';
													echo '<span style="all: unset; color: #ccc">↓</span>';
												}
											?>
										</label>
									</form>
								</th>
								<th>
									<form method="post">
										<input type="hidden" name="sort_column" value="surname">
										<input type="hidden" name="sort_order" value="<?= ($sort_column == 'surname' && $sort_order == 'ASC') ? 'DESC' : 'ASC' ?>">
										<input type="submit" id="SurnameSubmit" hidden>
										<label for="SurnameSubmit">
											Nazwisko
											<?php
												if ($sort_column == 'surname') {
													if ($sort_order == 'ASC') {
														echo '<span style="all: unset;">↑</span>';
														echo '<span style="all: unset; color: #ccc">↓</span>';
													}
													else {
														echo '<span style="all: unset; color: #ccc">↑</span>';
														echo '<span style="all: unset;">↓</span>';
													}
												}
												else {
													echo '<span style="all: unset; color: #ccc">↑</span>';
													echo '<span style="all: unset; color: #ccc">↓</span>';
												}
											?>
										</label>
									</form>
								</th>
								<th>
									<form method="post">
										<input type="hidden" name="sort_column" value="class">
										<input type="hidden" name="sort_order" value="<?= ($sort_column == 'class' && $sort_order == 'ASC') ? 'DESC' : 'ASC' ?>">
										<input type="submit" id="ClassSubmit" hidden>
										<label for="ClassSubmit">
											Klasa
											<?php
												if ($sort_column == 'class') {
													if ($sort_order == 'ASC') {
														echo '<span style="all: unset;">↑</span>';
														echo '<span style="all: unset; color: #ccc">↓</span>';
													}
													else {
														echo '<span style="all: unset; color: #ccc">↑</span>';
														echo '<span style="all: unset;">↓</span>';
													}
												}
												else {
													echo '<span style="all: unset; color: #ccc">↑</span>';
													echo '<span style="all: unset; color: #ccc">↓</span>';
												}
											?>
										</label>
									</form>
								</th>
								<th>
									<form method="post">
										<input type="hidden" name="sort_column" value="username">
										<input type="hidden" name="sort_order" value="<?= ($sort_column == 'username' && $sort_order == 'ASC') ? 'DESC' : 'ASC' ?>">
										<input type="submit" id="UsernameSubmit" hidden>
										<label for="UsernameSubmit">
											Nick
											<?php
												if ($sort_column == 'username') {
													if ($sort_order == 'ASC') {
														echo '<span style="all: unset;">↑</span>';
														echo '<span style="all: unset; color: #ccc">↓</span>';
													}
													else {
														echo '<span style="all: unset; color: #ccc">↑</span>';
														echo '<span style="all: unset;">↓</span>';
													}
												}
												else {
													echo '<span style="all: unset; color: #ccc">↑</span>';
													echo '<span style="all: unset; color: #ccc">↓</span>';
												}
											?>
										</label>
									</form>
								</th>
								<th>
									<form method="post">
										<input type="hidden" name="sort_column" value="class_name">
										<input type="hidden" name="sort_order" value="<?= ($sort_column == 'class_name' && $sort_order == 'ASC') ? 'DESC' : 'ASC' ?>">
										<input type="submit" id="Class_nameSubmit" hidden>
										<label for="Class_nameSubmit">
											Grupa
											<?php
												if ($sort_column == 'class_name') {
													if ($sort_order == 'ASC') {
														echo '<span style="all: unset;">↑</span>';
														echo '<span style="all: unset; color: #ccc">↓</span>';
													}
													else {
														echo '<span style="all: unset; color: #ccc">↑</span>';
														echo '<span style="all: unset;">↓</span>';
													}
												}
												else {
													echo '<span style="all: unset; color: #ccc">↑</span>';
													echo '<span style="all: unset; color: #ccc">↓</span>';
												}
											?>
										</label>
									</form>
								</th>
								<th>
									<form method="post">
										<input type="hidden" name="sort_column" value="points">
										<input type="hidden" name="sort_order" value="<?= ($sort_column == 'points' && $sort_order == 'ASC') ? 'DESC' : 'ASC' ?>">
										<input type="submit" id="PointsSubmit" hidden>
										<label for="PointsSubmit">
											Zrobione
											<?php
												if ($sort_column == 'points') {
													if ($sort_order == 'ASC') {
														echo '<span style="all: unset;">↑</span>';
														echo '<span style="all: unset; color: #ccc">↓</span>';
													}
													else {
														echo '<span style="all: unset; color: #ccc">↑</span>';
														echo '<span style="all: unset;">↓</span>';
													}
												}
												else {
													echo '<span style="all: unset; color: #ccc">↑</span>';
													echo '<span style="all: unset; color: #ccc">↓</span>';
												}
											?>
										</label>
									</form>
								</th>
								<th>
									<form method="post">
										<input type="hidden" name="sort_column" value="group_id">
										<input type="hidden" name="sort_order" value="<?= ($sort_column == 'group_id' && $sort_order == 'ASC') ? 'DESC' : 'ASC' ?>">
										<input type="submit" id="GroupSubmit" hidden>
										<label for="GroupSubmit">
											Rola
											<?php
												if ($sort_column == 'group_id') {
													if ($sort_order == 'ASC') {
														echo '<span style="all: unset;">↑</span>';
														echo '<span style="all: unset; color: #ccc">↓</span>';
													}
													else {
														echo '<span style="all: unset; color: #ccc">↑</span>';
														echo '<span style="all: unset;">↓</span>';
													}
												}
												else {
													echo '<span style="all: unset; color: #ccc">↑</span>';
													echo '<span style="all: unset; color: #ccc">↓</span>';
												}
											?>
										</label>
									</form>
								</th>
								<th>Reset hasła</th>
							</tr>
							<?php
								foreach ($usersArray as $column) {
echo <<<END
									<tr>
										<td><input class="option-select" type="checkbox" name="selected_id[]" value="{$column['ID_user']}"></td>
END;
										if($column['group_id'] != 1) {
echo <<<END
											<td>{$column['name']}</td>
											<td>{$column['surname']}</td>
											<td>{$column['class']}</td>
											<td>{$column['username']}</td>
											<td>{$column['class_name']}</td>
											<td><button type='submit' class='button flat' formaction='podejrzyj_uzytkownika' name='user_id' value="{$column['ID_user']}">{$column['points']}</button></td>
END;
											foreach($groupsArray as $user) {
												if ($user['ID_group'] == $column['group_id']) {
echo <<<END
													<td><button type='submit' class='button flat' formaction='zmien_redaktora' name='change_gr_id' value="{$column['ID_user']}">{$user['group_name']}</button></td>
END;
												}
											}
echo <<<END
											<td><button type='submit' class='button flat' formaction='reset_hasla' name='reset_id' value="{$column['ID_user']}">Resetuj</button></td>
END;
										}
										else {
echo <<<END
											<td></td>
											<td></td>
											<td></td>
											<td>{$column['username']}</td>
											<td></td>
END;
											echo "<td></td>";
											foreach($groupsArray as $user) {
												if ($user['ID_group'] == $column['group_id']) {
													echo "<td>{$user['group_name']}</td>";
												}
											}
											echo "<td></td>";
										}
									echo "</tr>";
								}
							?>
						</table>
					</form>
				</div>
			</main>
		</div>
	</div>

	<?php include 'items/footer.php' ?>
</body>
</html>
