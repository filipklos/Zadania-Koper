<?php
session_start();

if ((isset($_SESSION['logged'])) && ($_SESSION['logged'] == true))
{
	header('Location: ./');
	exit();
}

if ((isset($_POST['nick'])) || (isset($_POST['name'])) || (isset($_POST['surname'])) || (isset($_POST['class'])))
{
	$data_correct = true;

	$nick = $_POST['nick'];

	if ((strlen($nick)<3) || (strlen($nick)>20))
	{
		$data_correct = false;
		$_SESSION['e_nick']="Nick musi posiadać od 3 do 20 znaków!";
	}
	if (ctype_alnum($nick) == false)
	{
		$data_correct = false;
		$_SESSION['e_nick']="Nick może składać się tylko z liter i cyfr (bez polskich znaków)";
	}

	$name = $_POST['name'];
	$name = strtolower($name);
	$name = ucfirst($name);

	if ((strlen($name)<3) || (strlen($name)>20))
	{
		$data_correct = false;
		$_SESSION['e_name']="Imię musi posiadać od 3 do 20 znaków!";
	}
	$name = htmlentities($name, ENT_QUOTES, "UTF-8");


	$surname = $_POST['surname'];
	$surname = strtolower($surname);
	$surname = ucfirst($surname);

	if ((strlen($surname)<3) || (strlen($surname)>20))
	{
		$data_correct = false;
		$_SESSION['e_surname']="Nazwisko musi posiadać od 3 do 20 znaków!";
	}
	$surname = htmlentities($surname, ENT_QUOTES, "UTF-8");

	$class = $_POST['class'];
	if (!is_numeric($class)) {
		$data_correct = false;
		$_SESSION['e_class']="Klasa musi być liczbą od 1 do 4!";
	}
	else {
		if (($class<1) || ($class>4))
		{
			$data_correct = false;
			$_SESSION['e_class']="Klasa musi być liczbą od 1 do 4!";
		}
	}
	
	$password1 = $_POST['password1'];
	$password2 = $_POST['password2'];

	if ((strlen($password1)<8) || (strlen($password1)>30))
	{
		$data_correct = false;
		$_SESSION['e_pass1']="Hasło musi posiadać od 8 do 30 znaków";
	}
	if ($password1 != $password2)
	{
		$data_correct = false;
		$_SESSION['e_pass2']="Podane hasła się różnią";
	}

	$pass_hash = password_hash($password1, PASSWORD_DEFAULT);

	$_SESSION['form_name'] = $name;
	$_SESSION['form_surname'] = $surname;
	$_SESSION['form_nick'] = $nick;
	$_SESSION['form_class'] = $class;

	require_once "config/database.php";
	$userQuery = $db->prepare('SELECT ID_user FROM users WHERE username=:nick');
	$userQuery->bindValue(':nick', $nick, PDO::PARAM_STR);
	$userQuery->execute();

	$user_results = $userQuery->fetch();

	if ($user_results)
	{
		$data_correct = false;
		$_SESSION['e_nick']="Istnieje już konto o tym loginie";
	}

	if ($data_correct == true)
	{
		$Query = $db->prepare('INSERT INTO users VALUES(NULL, :nick, :pass_hash, 2,:u_name, :surname, :class, NULL, NOW())');
		$Query->bindValue(':nick', $nick, PDO::PARAM_STR);
		$Query->bindValue(':pass_hash', $pass_hash, PDO::PARAM_STR);
		$Query->bindValue(':u_name', $name, PDO::PARAM_STR);
		$Query->bindValue(':surname', $surname, PDO::PARAM_STR);
		$Query->bindValue(':class', $class, PDO::PARAM_INT);
		$Query->execute();

		$_SESSION['correct_move'] = 'Udana rejestracja, można się zalogować';
		header('Location: ./');
	}
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title>Tworzenie nowego konta | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
	<script src="https://www.google.com/recaptcha/api.js"></script>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1>Tworzenie nowego konta</h1>
			</header>
			
			<nav>
				<a href="./" class="button"><i class="icon-back"></i>&nbsp;Powrót</a>
			</nav>
			
			<main>
				<form class="in-rows" method="post">
				<input type="text" value="<?php
						if(isset($_SESSION['form_name']))
						{
							echo $_SESSION['form_name'];
							unset($_SESSION['form_name']);
						}
					?>" name="name" placeholder="Imię">
					<?php
						if (isset($_SESSION['e_name']))
						{
							echo '<span class="alert red">'.$_SESSION['e_name'].'</span>';
							unset($_SESSION['e_name']);
						}
					?>

					<input type="text" value="<?php
						if(isset($_SESSION['form_surname']))
						{
							echo $_SESSION['form_surname'];
							unset($_SESSION['form_surname']);
						}
					?>" name="surname" placeholder="Nazwisko">
					<?php
						if (isset($_SESSION['e_surname']))
						{
							echo '<span class="alert red">'.$_SESSION['e_surname'].'</span>';
							unset($_SESSION['e_surname']);
						}
					?>

					<div class="select_class">
						Klasa:
						<select name="class">
							<?php
								for ($num = 1; $num <= 4; $num++) {
									if(isset($_SESSION['form_class']) && ($_SESSION['form_class'] == $num)) {
										echo "<option value='$num' selected>$num</option>";
										unset($_SESSION['form_class']);
									}
									else {
										echo "<option value='$num'>$num</option>";
									}
								}
							?>
						</select>
					</div>
					<?php
						if (isset($_SESSION['e_class']))
						{
							echo '<span class="alert red">'.$_SESSION['e_class'].'</span>';
							unset($_SESSION['e_class']);
						}
					?>

					<input type="text" value="<?php
						if(isset($_SESSION['form_nick']))
						{
							echo $_SESSION['form_nick'];
							unset($_SESSION['form_nick']);
						}
					?>" name="nick" placeholder="Login">
					<?php
						if (isset($_SESSION['e_nick']))
						{
							echo '<span class="alert red">'.$_SESSION['e_nick'].'</span>';
							unset($_SESSION['e_nick']);
						}
					?>

					<input type="password" name="password1" placeholder="Hasło">
					<?php
						if (isset($_SESSION['e_pass1']))
						{
							echo '<span class="alert red">'.$_SESSION['e_pass1'].'</span>';
							unset($_SESSION['e_pass1']);
						}
					?>

					<input type="password" name="password2" placeholder="Powtórz hasło">
					<?php
						if (isset($_SESSION['e_pass2']))
						{
							echo '<span class="alert red">'.$_SESSION['e_pass2'].'</span>';
							unset($_SESSION['e_pass2']);
						}
					?>

					<input type="submit" value="Zarejestruj się">
				</form>
			</main>
		</div>
	</div>

	<?php include 'items/footer.php' ?>
</body>
</html>
