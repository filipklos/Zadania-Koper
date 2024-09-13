<?php
session_start();

if ((!isset($_SESSION['logged'])) || ($_SESSION['logged'] == false) || ($_SESSION['group_name'] != 'admin'))
{
	header('Location: ./');
	exit();
}

require_once "config/database.php";
$usersQuery = $db->query('SELECT ID_group, group_name FROM user_groups');
$usersArray = $usersQuery->fetchAll();

if (isset($_POST['nick']) || isset($_POST['password1']) || isset($_POST['password2']) || isset($_POST['input_list']))
{
	$data_correct = true;

	$nick = $_POST['nick'];
	$ID_user = $_SESSION['ID_user'];
	$password1 = $_POST['password1'];
	$password2 = $_POST['password2'];
	$chosen_group = $_POST['input_list'];

	$name = $_POST['name'];
	$name = strtolower($name);
	$name = ucfirst($name);

	$surname = $_POST['surname'];
	$surname = strtolower($surname);
	$surname = ucfirst($surname);

	$class = $_POST['class'];
	$chosen_group = htmlentities($chosen_group, ENT_QUOTES, "UTF-8");

	if ((strlen($nick)<3) || (strlen($nick)>20))
	{
		$data_correct = false;
		$_SESSION['e_nick']="Nick musi posiadać od 3 do 20 znaków";
	}
	if (ctype_alnum($nick) == false)
	{
		$data_correct = false;
		$_SESSION['e_nick']="Nick może składać się tylko z liter i cyfr (bez polskich znaków)";
	}

	if ((strlen($password1)<8) || (strlen($password1)>30))
	{
		$data_correct = false;
		$_SESSION['e_pass1']="Hasło musi posiadać od 8 do 30 znaków";
	}

	$pass_hash = password_hash($password1, PASSWORD_DEFAULT);

	$_SESSION['form_nick'] = $nick;
	$_SESSION['form_password1'] = $password1;
	$_SESSION['form_name'] = $name;
	$_SESSION['form_surname'] = $surname;
	$_SESSION['form_class'] = $class;
	if (isset($_SESSION['correct_up'])) unset($_SESSION['correct_up']);

	$form_correct = false;

	foreach ($usersArray as $user_in_array)
	{
		if ($user_in_array['group_name'] == $chosen_group)
		{
			$form_correct = true;
			$selected_group_id = $user_in_array['ID_group'];
			$_SESSION['form_group'] = $selected_group_id;
			unset($_SESSION['e_list']);
			break;
		}
		else
		{
			$_SESSION['e_list'] = "Błędna rola";
		}
	}

	if ($form_correct == false) $data_correct = false;

	$nickQuery = $db->prepare('SELECT ID_user FROM users WHERE username=:nick');
	$nickQuery->bindValue(':nick', $nick, PDO::PARAM_STR);
	$nickQuery->execute();

	$nick_results = $nickQuery->fetch();

	if ($nick_results)
	{
		$data_correct = false;
		$_SESSION['e_nick']="Istnieje już konto o tym loginie!";
	}

	if (($selected_group_id != 1) && (isset($selected_group_id)))
	{
		if ((strlen($name)<3) || (strlen($name)>20))
		{
			$data_correct = false;
			$_SESSION['e_name']="Imię musi posiadać od 3 do 20 znaków!";
		}
		$name = htmlentities($name, ENT_QUOTES, "UTF-8");

		if ((strlen($surname)<3) || (strlen($surname)>20))
		{
			$data_correct = false;
			$_SESSION['e_surname']="Nazwisko musi posiadać od 3 do 20 znaków!";
		}
		$surname = htmlentities($surname, ENT_QUOTES, "UTF-8");

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
	}
	else
	{
		$name = NULL;
		$surname = NULL;
		$class = NULL;
	}

	$userQuery = $db->prepare('SELECT password FROM users WHERE ID_user = :ID_user');
	$userQuery->bindValue(':ID_user', $ID_user, PDO::PARAM_INT);
	$userQuery->execute();

	$user_results = $userQuery->fetch();

	if (password_verify($password2, $user_results['password']))
	{
		if ($data_correct == true)
		{
			$Query = $db->prepare('INSERT INTO users VALUES(NULL, :nick, :pass_hash, :selected_group_id, :u_name, :surname, :class, NULL, NOW())');
			$Query->bindValue(':nick', $nick, PDO::PARAM_STR);
			$Query->bindValue(':pass_hash', $pass_hash, PDO::PARAM_STR);
			$Query->bindValue(':selected_group_id', $selected_group_id, PDO::PARAM_INT);
			$Query->bindValue(':u_name', $name, PDO::PARAM_STR);
			$Query->bindValue(':surname', $surname, PDO::PARAM_STR);
			$Query->bindValue(':class', $class, PDO::PARAM_INT);
			$Query->execute();
			
			
			$_SESSION['correct_up'] = 'Konto zostało dodane';
			header('Location: '.$_SERVER['PHP_SELF']);
		}
	}
	else
	{
		$_SESSION['e_pass2']="Błędne hasło";
	}
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title>Tworzenie konta | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1>Tworzenie konta</h1>
			</header>
			
			<nav>
				<a href="zarzadzanie_uzytkownikami" class="button"><i class="icon-back"></i>&nbsp;Powrót</a>
			</nav>

			<?php
				if (isset($_SESSION['correct_up']))
				{
					echo '<span class="alert green">'.$_SESSION['correct_up'].'</span>';
					if (!isset($data_correct)) unset($_SESSION['correct_up']);
				}
			?>

			<span>Imię, nazwisko i klasę należy podać tylko dla kont użytkowników (nie dla adminów).</span>

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
					?>" name="nick" placeholder="Podaj login">
					<?php
						if (isset($_SESSION['e_nick']))
						{
							echo '<span class="alert red">'.$_SESSION['e_nick'].'</span>';
							unset($_SESSION['e_nick']);
						}
					?>
				
					<input type="password" value="<?php
						if(isset($_SESSION['form_password1']))
						{
							echo $_SESSION['form_password1'];
							unset($_SESSION['form_password1']);
						}
					?>" name="password1" placeholder="Podaj hasło">
					<?php
						if (isset($_SESSION['e_pass1']))
						{
							echo '<span class="alert red">'.$_SESSION['e_pass1'].'</span>';
							unset($_SESSION['e_pass1']);
						}
					?>
					
					<div class="select_group">
						Grupa:
						<select name="input_list">
							<?php
								foreach ($usersArray as $user_in_array) {
									if ((isset($_SESSION['form_group']))) {
										if ($user_in_array['ID_group'] == $_SESSION['form_group']){
											echo "<option value='{$user_in_array['group_name']}' selected>{$user_in_array['group_name']}</option>";
										}
										else {
											echo "<option value='{$user_in_array['group_name']}'>{$user_in_array['group_name']}</option>";
										}
									}
									else {
										if (($user_in_array['ID_group'] == 2)) {
											echo "<option value='{$user_in_array['group_name']}' selected>{$user_in_array['group_name']}</option>";
										}
										else {
											echo "<option value='{$user_in_array['group_name']}'>{$user_in_array['group_name']}</option>";
										}
									}
								}
								if (isset($_SESSION['form_group'])) unset($_SESSION['form_group']);
							?>
						</select>
					</div>
					<?php
						if (isset($_SESSION['e_list']))
						{
							echo '<span class="alert red">'.$_SESSION['e_list'].'</span>';
							unset($_SESSION['e_list']);
						}
					?>
				
					<input type="password" name="password2" placeholder="Podaj swoje hasło">
					<?php
						if (isset($_SESSION['e_pass2']))
						{
							echo '<span class="alert red">'.$_SESSION['e_pass2'].'</span>';
							unset($_SESSION['e_pass2']);
						}
					?>

					<input type="submit" value="Stwórz konto">
				</form>
			</main>
		</div>
	</div>

	<?php include 'items/footer.php' ?>
</body>
</html>
