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


if (isset($_POST['pass']) && isset($_SESSION['selected_id'])) {
	$pass = $_POST['pass'];
	$users_array = $_SESSION['selected_id'];
	$ID_user = $_SESSION['ID_user'];
	$ID_users = "";

	foreach($users_array as $id_in_array) {
		if (is_numeric($id_in_array)){
			if ($id_in_array != "2"){
				$ID_users .= $id_in_array.", ";
			}
			else {
				$G_del = true;
			}
			if ($id_in_array == $ID_user){
				$self_del = true;
			}
		}
	}
	$ID_users = substr($ID_users, 0, -2);

	require_once "config/database.php";
	require_once "items/global_functions.php";

	$categoryMU = getFromPostOrCookie('categoryMU', '0', '0');
	
	$passQuery= $db->prepare('SELECT password FROM users WHERE ID_user = :ID_user');
	$passQuery->bindValue(':ID_user', $ID_user, PDO::PARAM_INT);
	$passQuery->execute();

	$user_pass = $passQuery->fetch();

	if (password_verify($pass, $user_pass['password']))
	{
		if ($categoryMU != 0) {
			$Query = $db->prepare("UPDATE user_answers 
			SET points = 0, max_points = 0 
			WHERE user_id IN ($ID_users) AND task_id IN (
			SELECT id 
			FROM tasks 
			WHERE category_id = $categoryMU
			)");
			$Query->execute();
		}
		else {
			$Query = $db->prepare("UPDATE user_answers 
			SET points = 0, max_points = 0 
			WHERE user_id IN ($ID_users)");
			$Query->execute();
		}
		$_SESSION['correct_move'] = 'Pomyślnie wyzerowano punkty użytkowników';
		header('Location: zarzadzanie_uzytkownikami');
	}
	else
	{
		$_SESSION['e_pass']="Błędne hasło";
	}
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title>Usuwanie punktów | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1>Zerowanie punktów użytkowników</h1>
			</header>

			<nav>
				<a href="zarzadzanie_uzytkownikami" class="button"><i class="icon-back"></i>&nbsp;Powrót</a>
			</nav>

			<span class="alert red">UWAGA!!! Ta operacja jest nieodwracalna!</span>

			<main>
				<form class="in-rows" method="post">
					<input type="password" name="pass" placeholder="Podaj hasło">
					<?php
						if (isset($_SESSION['e_pass']))
						{
							echo '<span class="alert red">'.$_SESSION['e_pass'].'</span>';
							unset($_SESSION['e_pass']);
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
