<?php
session_start();
if ((!isset($_SESSION['logged'])) || ($_SESSION['logged'] == false))
{
	header('Location: ./');
	exit();
}
if (isset($_POST['password1']))
{
	$password1 = $_POST['password1'];
	$password2 = $_POST['password2'];
	$ID_user = $_SESSION['ID_user'];
	$data_correct = true;


	if ((strlen($password2)<8) || (strlen($password2)>30))
	{
		$data_correct = false;
		$_SESSION['e_pass2']="Hasło musi posiadać od 8 do 30 znaków";
	}
	$pass_hash = password_hash($password2, PASSWORD_DEFAULT);

	require_once "config/database.php";
	$userQuery = $db->prepare('SELECT password FROM users WHERE ID_user = :ID_user');
	$userQuery->bindValue(':ID_user', $ID_user, PDO::PARAM_INT);
	$userQuery->execute();

	$user_results = $userQuery->fetch();
	if (password_verify($password1, $user_results['password']))
	{
		if ($data_correct == true)
		{
			$Query = $db->prepare('UPDATE users SET password = :pass_hash WHERE ID_user = :ID_user');
			$Query->bindValue(':pass_hash', $pass_hash, PDO::PARAM_STR);
			$Query->bindValue(':ID_user', $ID_user, PDO::PARAM_INT);
			$Query->execute();
			
			$_SESSION['correct_move'] = 'Hasło zostało zmienione';
			header('Location: ./');
		}
	}
	else
	{
		$_SESSION['e_pass1']="Błędne hasło";
	}
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title>Zmiana hasła | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1>Zmiana hasła</h1>
			</header>

			<nav>
				<a href="./" class="button"><i class="icon-back"></i>&nbsp;Powrót</a>
			</nav>

			<main>
				<form class="in-rows" method="post">
					<input type="password" name="password1" placeholder="Podaj obecne hasło">
					<?php
						if (isset($_SESSION['e_pass1']))
						{
							echo '<span class="alert red">'.$_SESSION['e_pass1'].'</span>';
							unset($_SESSION['e_pass1']);
						}
					?>

					<input type="password" name="password2" placeholder="Podaj nowe hasło">
					<?php
						if (isset($_SESSION['e_pass2']))
						{
							echo '<span class="alert red">'.$_SESSION['e_pass2'].'</span>';
							unset($_SESSION['e_pass2']);
						}
					?>

					<input type="submit" value="Zmień hasło">
				</form>
			</main>
		</div>
	</div>

	<?php include 'items/footer.php' ?>
</body>
</html>
