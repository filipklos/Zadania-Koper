<?php
session_start();

if ((!isset($_SESSION['logged'])) || ($_SESSION['logged'] == false) || ($_SESSION['group_name'] != 'admin'))
{
	header('Location: ./');
	exit();
}
if ((!isset($_POST['reset_id'])) && !isset($_SESSION['reset_id']))
{
	header('Location: zarzadzanie_uzytkownikami');
	exit();
}
if (isset($_POST['reset_id'])) {
	$_SESSION['reset_id'] = $_POST['reset_id'];
}


if (isset($_POST['pass']) && isset($_SESSION['reset_id'])) {
	$reset_id = $_SESSION['reset_id'];
	$data_correct = true;

	if(!is_numeric($reset_id)) {
		$data_correct = false;
		$_SESSION['e_id'] = "Nie udało się zmienić hasła użytkownikowi!";
	}

	$pass = $_POST['pass'];

	if ((strlen($pass)<8) || (strlen($pass)>30))
	{
		$data_correct = false;
		$_SESSION['e_pass']="Hasło musi posiadać od 8 do 30 znaków";
	}

	$pass_hash = password_hash($pass, PASSWORD_DEFAULT);

	if($data_correct == true){
		require_once "config/database.php";
		$userQuery = $db->prepare("SELECT group_id FROM users WHERE ID_user = :u_id");
		$userQuery->bindValue(':u_id', $reset_id, PDO::PARAM_INT);
		$userQuery->execute();

		$user_data = $userQuery->fetch();
		$user_group = $user_data['group_id'];

		if($user_group != NULL) {
			if($user_group != 1){

				$Query = $db->prepare('UPDATE users SET password = :pass_hash WHERE ID_user = :u_id');
				$Query->bindValue(':pass_hash', $pass_hash, PDO::PARAM_STR);
				$Query->bindValue(':u_id', $reset_id, PDO::PARAM_INT);
				$Query->execute();

				$_SESSION['correct_move'] = 'Zmieniono hasło użytkownika';
				header('Location: zarzadzanie_uzytkownikami');
			}
			else {
				$_SESSION['e_id'] = "Nie można zmienić hasła administratorowi!";
			}
		}
		else {
			$_SESSION['e_id'] = "Nie udało się zmienić hasła użytkownikowi!";
		}
	}
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title>Reset haseł | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1>Reset haseł użytkowników</h1>
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
					<input type="password" name="pass" placeholder="Ustaw nowe hasło">
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