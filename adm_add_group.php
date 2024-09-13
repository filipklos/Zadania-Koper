<?php
session_start();

if ((!isset($_SESSION['logged'])) || ($_SESSION['logged'] == false) || ($_SESSION['group_name'] != 'admin'))
{
	header('Location: ./');
	exit();
}
if ((!isset($_POST['selected_id'])) && !isset($_SESSION['selected_id']))
{
	header('Location: zarzadzanie_uzytkownikami');
	exit();
}
if (isset($_POST['selected_id'])) {
	$_SESSION['selected_id'] = $_POST['selected_id'];
}


if (isset($_POST['new_group_name']) && isset($_SESSION['selected_id'])) {
	$new_group_name = $_POST['new_group_name'];
	$users_array = $_SESSION['selected_id'];
	$ID_users = "";

	foreach($users_array as $id_in_array) {
		if (is_numeric($id_in_array)){
			$ID_users .= $id_in_array.", ";
		}
	}
	$ID_users = substr($ID_users, 0, -2);

	require_once "config/database.php";
	$userQuery = $db->prepare("SELECT ID_user, group_id FROM users WHERE ID_user IN($ID_users)");
	$userQuery->execute();

	$user_group = $userQuery->fetchAll();

	$ID_users = "";

	foreach($user_group as $user) {
		if($user['group_id'] != 1) {
			$ID_users .= $user['ID_user'].", ";
		}
	}
	$ID_users = substr($ID_users, 0, -2);

	if (strlen($new_group_name) < 2 || strlen($new_group_name) > 254)
	{
		$_SESSION['e_new_group_name'] = "Nazwa grupy musi posiadać od 2 do 255 znaków!";
	}
	else {
		if(strlen($ID_users) > 0)
		{
			$Query = $db->prepare("UPDATE users SET class_name = :new_group_name WHERE ID_user IN($ID_users)");
			$Query->bindValue(':new_group_name', $new_group_name, PDO::PARAM_STR);
			$Query->execute();

			$_SESSION['correct_move'] = 'Pomyślnie nazwano grupy użytkowników';
			header('Location: zarzadzanie_uzytkownikami');
		}
		else {
			$_SESSION['e_id'] = "Nie udało się ustawić grupy użytkownikom!";
		}
	}
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title>Nazywanie grup | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1>Wpisywanie nazwy grupy użytkowników</h1>
			</header>

			<nav>
				<?php
					if (isset($_SESSION['e_id']))
					{
						echo '<span class="alert red">'.$_SESSION['e_id'].'</span>';
						unset($_SESSION['e_id']);
					}
				?>
				<a href="zarzadzanie_uzytkownikami" class="button"><i class="icon-back"></i>&nbsp;Powrót</a>
			</nav>

			<main>
				<form class="in-rows" method="post">
					<input type="text" name="new_group_name" placeholder="Nazwij grupę">
					<?php
						if (isset($_SESSION['e_new_group_name']))
						{
							echo '<span class="alert red">'.$_SESSION['e_new_group_name'].'</span>';
							unset($_SESSION['e_new_group_name']);
						}
					?>
					<input type="submit" value="Potwierdź">
				</form>
			</main>
		</div>
	</div>

	<?php include 'items/footer.php' ?>
</body>
</html>