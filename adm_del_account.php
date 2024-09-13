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
	$self_del = false;
	$G_del = false;

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
	$passQuery= $db->prepare('SELECT password FROM users WHERE ID_user = :ID_user');
	$passQuery->bindValue(':ID_user', $ID_user, PDO::PARAM_INT);
	$passQuery->execute();

	$user_pass = $passQuery->fetch();

	if (password_verify($pass, $user_pass['password']))
	{
		if (strlen($ID_users) > 0) {
			$Query = $db->prepare("DELETE users, user_answers, codes
			FROM users
			LEFT JOIN user_answers ON users.ID_user = user_answers.user_id
			LEFT JOIN codes ON users.ID_user = codes.user_id
			WHERE users.ID_user IN ($ID_users)");
			$Query->execute();
		}

		if ($G_del == true) {
			$_SESSION['correct_move'] = 'Pomyślnie usunięto użytkowników z wyjątkiem konta Tadeusza Gajdzicy, ponieważ to konto jest nieusuwalne';
			header('Location: zarzadzanie_uzytkownikami');
			exit();
		}

		if ($self_del == true) {
			$_SESSION['end'] = true;
			header('Location: ./');
		}
		else {
			$_SESSION['correct_move'] = 'Pomyślnie usunięto użytkowników';
			header('Location: zarzadzanie_uzytkownikami');
		}
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
	<title>Usuwanie kont | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1>Usuwanie kont użytkowników</h1>
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
