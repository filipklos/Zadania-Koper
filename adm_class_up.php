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
	$users_arr = $_SESSION['selected_id'];
	$ID_us = "";

	foreach($users_arr as $id_in_arr) {
		if (is_numeric($id_in_arr)){
			$ID_us .= $id_in_arr.", ";
		}
	}
	$ID_us = substr($ID_us, 0, -2);

	require_once "config/database.php";
	$classQuery = $db->prepare("SELECT class FROM users WHERE ID_user IN($ID_us)");
	$classQuery->execute();

	$user_cs = $classQuery->fetchAll();

	foreach ($user_cs as $cs){
		if ($cs['class'] == 4) $fourth_graders = true;
	}
}


if (isset($_POST['pass']) && isset($_SESSION['selected_id'])) {
	$pass = $_POST['pass'];
	$users_array = $_SESSION['selected_id'];
	$ID_user = $_SESSION['ID_user'];
	$ID_users = "";


	foreach($users_array as $id_in_array) {
		if (is_numeric($id_in_array)){
			$ID_users .= $id_in_array.", ";
		}
	}
	$ID_users = substr($ID_users, 0, -2);


	require_once "config/database.php";
	$passQuery = $db->prepare('SELECT password FROM users WHERE ID_user = :ID_user');
	$passQuery->bindValue(':ID_user', $ID_user, PDO::PARAM_INT);
	$passQuery->execute();

	$user_pass = $passQuery->fetch();

	if (password_verify($pass, $user_pass['password']))
	{
		$userQuery = $db->prepare("SELECT ID_user, class FROM users WHERE ID_user IN($ID_users)");
		$userQuery->execute();

		$user_class = $userQuery->fetchAll();

		$del_ID_users = "";

		foreach($user_class as $user) {
			$ind = array_search($user, $user_class);
			if($user['class'] != NULL) {

				$user['class']++;
				unset($user[0]);
				unset($user[1]);
				
				if ($user['class'] > 4) {
					$del_ID_users .= $user['ID_user'].", ";
					array_splice($user_class, $ind, 1);
				}
				else {
					$user_class[$ind] = $user;
				}
			}
			else {
				array_splice($user_class, $ind, 1);
			}
		}
		$del_ID_users = substr($del_ID_users, 0, -2);

		if (strlen($del_ID_users) > 0) {
			$Query = $db->prepare("DELETE users, user_answers, codes
			FROM users
			LEFT JOIN user_answers ON users.ID_user = user_answers.user_id
			LEFT JOIN codes ON users.ID_user = codes.user_id
			WHERE users.ID_user IN ($del_ID_users)");
			$Query->execute();
		}

		for ($class = 1; $class <= 4; $class++) {
			$ID_users = "";
			foreach($user_class as $user){
				if($user['class'] == $class) {
					$ID_users .= $user['ID_user'].", ";
				}
			}
			$ID_users = substr($ID_users, 0, -2);
			if ((strlen($ID_users) > 0)) {
				$Query = $db->prepare("UPDATE users SET class = :class_up WHERE ID_user IN($ID_users)");
				$Query->bindValue(':class_up', $class, PDO::PARAM_INT);
				$Query->execute();
			}
		}
		$_SESSION['correct_move'] = 'Pomyślnie zwiększono klasę użytkowników';
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
	<title>Zwiększanie klasy | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1>Zwiększanie klasy użytkowników</h1>
			</header>

			<nav>
				<a href="zarzadzanie_uzytkownikami" class="button"><i class="icon-back"></i>&nbsp;Powrót</a>
			</nav>

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
		<?php
		if (isset($fourth_graders)){
		unset($fourth_graders);
echo <<<END
		<div class="subcontainer">
			<span class="big-alert red">Uwaga!!! Na liście zaznaczonych użytkowników znalazły się osoby z 4-tej klasy.</span>
			<span class="big-alert red">Konta tych osób zostaną permanentnie usunięte!</span>
		</div>
END;
		
		}
		?>
	</div>

	<?php include 'items/footer.php' ?>
</body>
</html>
