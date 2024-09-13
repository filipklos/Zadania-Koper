<?php

session_start();

if ((!isset($_SESSION['logged'])) || ($_SESSION['logged'] == false) || ($_SESSION['group_name'] != 'admin'))
{
	header('Location: ./');
	exit();
}

require_once 'config/database.php';
require_once 'items/global_functions.php';

$sort = getFromPostOrCookie('compare_sort', '0');
$class = getFromPostOrCookie('compare_class', '0');
$filter = getFromPostOrCookie('compare_filter', '0');
$roll = getFromPostOrCookie('roll', '1');

try {
	isset($_GET['task']) ? $taskId = $_GET['task'] : throw new Exception('No data given');
	isset($_GET['user']) ? $userId = $_GET['user'] : throw new Exception('No data given');
}
catch (Exception $error) {
	header('Location: ./');
	exit();
}

setCookieFromPost('compare_sort');
setCookieFromPost('compare_class');
setCookieFromPost('compare_filter');

function is_newline($c) {
	return $c == '\n' || $c == '\r' || ord($c) == 10;
}

function is_operator($c) {
	$ascii = ord($c);
	return $c == '!' || (($ascii >= 35 && $ascii <= 47) && $c != '\'') || ($ascii >= 58 && $ascii <= 64) || (($ascii >= 91 && $ascii <= 96) && $c != '_')|| ($ascii >= 123 && $ascii <= 126);
}

function read_code($user_id, $task_id, $db) {
	$codeQuery = $db->prepare("SELECT code FROM codes WHERE user_id LIKE :user_id AND task_id LIKE :task_id");
	$codeQuery->bindValue(':user_id', $user_id, PDO::PARAM_INT);
	$codeQuery->bindValue(':task_id', $task_id, PDO::PARAM_INT);
	$codeQuery->execute();
	
	$code = $codeQuery->fetch()['code'];

	return $code;
}

function to_tokens($source) {
	$tokens = array();
	$word = '';

	$is_quoting = false;

	$len = strlen($source);

	for($i = 0; $i < $len; $i++) {
		$c = $source[$i];

		
		// If '$c' is the opening/closing of quotation
		if ($c == '"' || $c == '\'') {
			$is_quoting = !$is_quoting;
		
			if(!$is_quoting)
				$word .= $c;
		
			if (strlen($word) > 0)
			{
				array_push($tokens, $word);
				$word = '';
			}
		
			if ($is_quoting)
				$word .= $c;
		}
		else if ($is_quoting) {
			$word .= $c;
		}
		// If 'c' is a start of a comment
		else if ($c == '#') {
			while ($i < $len && !is_newline($source[$i])) $i++;
		}
		// If '$c' is a whitespace
		else if ($c == ' ' || is_newline($c)) {
			if (strlen($word) > 0) {
				array_push($tokens, $word);
				$word = '';
			}
		}
		// If '$c' is an operator or is quoting
		else if (is_operator($c)) {
			if (strlen($word) > 0) {
				array_push($tokens, $word);
				$word = '';
			}

			array_push($tokens, $c);
		}
		// If it is an ordinary character (a-z, A-Z, 0-9)
		else {
			$word .= $c;
		}
	}

	return $tokens;
}

function similarity($tokens_a, $tokens_b) {
	$token_count_a = array();
	$token_count_b = array();

	foreach ($tokens_a as $tok) {
		$ntok = strval($tok);
		
		if (array_key_exists($ntok, $token_count_a)) {
			$token_count_a[$ntok] += 1;
		} else {
			$token_count_a[$ntok] = 1;
		}
	}
	foreach ($tokens_b as $tok) {
		$ntok = strval($tok);

		if (array_key_exists($ntok, $token_count_b)) {
			$token_count_b[$ntok] += 1;
		} else {
			$token_count_b[$ntok] = 1;
		}
	}

	$allTokenCounts = count($token_count_a);
	$matchingTokenCounts = 0;

	$steps = 0;

	foreach ($token_count_a as $token => $count) {
		$steps++;
		if (array_key_exists($token, $token_count_b)) {
			if ($token_count_b[$token] == $count) {
				$matchingTokenCounts++;
			}
		}
	}

	try {
		return $matchingTokenCounts / $allTokenCounts;
	}
	catch (DivisionByZeroError $error) {
		header('Location: zarzadzanie_uzytkownikami');
		exit();
	}
}

function count_tokens($tokens) {
	$token_count = array();

	foreach ($tokens as $tok) {
		$ntok = strval($tok);
		
		if (array_key_exists($ntok, $token_count)) {
			$token_count[$ntok] += 1;
		} else {
			$token_count[$ntok] = 1;
		}
	}

	return $token_count;
}

try {
	$classSort = $class != 0 ? "AND users.class LIKE :class" : "";
	
	if ($filter == 1) {
		$completionFilter = "AND user_answers.max_points <> 2";
	} else if ($filter == 2) {
		$completionFilter = "AND user_answers.max_points = 2";
	} else {
		$completionFilter = "";
	}

	$answersQuery=$db->prepare("SELECT user_answers.points, MAX(answers.points) AS max_points, user_answers.user_id as user_id, codes.id as code_id, tasks.id, title, content, codes.code, category, color, user_answers.max_points AS max, name, surname
	FROM user_answers
	LEFT JOIN tasks
	ON user_answers.task_id=tasks.id
	LEFT JOIN codes
	ON user_answers.user_id=codes.user_id AND tasks.id=codes.task_id
	LEFT JOIN answers
	ON answers.task_id=tasks.id
	LEFT JOIN categories
	ON categories.id=tasks.category_id
	LEFT JOIN users
	ON users.ID_user=user_answers.user_id
	WHERE tasks.id LIKE :task_id AND user_answers.user_id NOT LIKE :user_id $classSort $completionFilter
	GROUP BY user_answers.user_id");

	$answersQuery->bindValue(':task_id', $taskId, PDO::PARAM_INT);
	$answersQuery->bindValue(':user_id', $userId, PDO::PARAM_INT);
	if ($class != 0) $answersQuery->bindValue(':class', $class, PDO::PARAM_INT);
	$answersQuery->execute();

	$user_answers = $answersQuery->fetchAll();

	$current_code = read_code($userId, $taskId, $db);
	$current_tokens = to_tokens($current_code);

	//foreach ($current_tokens as $token) {
	// 	echo "<p>|$token|</p>";
	//}

	foreach ($user_answers as $i => &$user_answer) {
		if ($user_answer['code']) {
			$user_tokens = to_tokens($user_answer['code']);
 
			$user_answer['similarity'] = round(similarity($current_tokens, $user_tokens) * 100, 1);
		} else {
			$user_answer['similarity'] = 0.0;
		}
	}
	
	unset($user_answer);
	
	if($sort == 0) {
		usort($user_answers, function($a, $b) {
			return $b['similarity'] - $a['similarity']; 
		});
	} else if($sort == 1) {
		usort($user_answers, function($a, $b) {
			return $a['similarity'] - $b['similarity']; 
		});
	}

	$userQuery=$db->prepare("SELECT username, name, surname FROM users WHERE ID_user LIKE :user_id");
	$userQuery->bindValue(':user_id', $userId, PDO::PARAM_INT);
	$userQuery->execute();

	$user = $userQuery->fetch();

	$taskQuery=$db->prepare("SELECT title FROM tasks WHERE id LIKE :task_id");
	$taskQuery->bindValue(':task_id', $taskId, PDO::PARAM_INT);
	$taskQuery->execute();

	$task = $taskQuery->fetch();

} catch (PDOException $error) {
	$user_answers = array();
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title>Porównanie | KOPER Zadania</title>
	<?php include 'items/meta.php' ?>
</head>
<body>
	<div class="container">
		<div class="subcontainer">
			<header>
				<h1>Porównanie rozwiązania od <?= $user['name'].' '.$user['surname'] ?> do pozostałych w zadaniu <?= $task['title'] ?></h1>
			</header>

			<?php
echo <<<END
\n				<article>
					<div class="frame" style="margin-bottom:20px">
END;
						if($current_code) {
echo <<<END
\n						<textarea class="text_long" name="code" readonly>
{$current_code}
						</textarea>
END;
						}
echo <<<END
\n					</div>
				</article>
END;
				?>

			<nav>
				<a href="zarzadzanie_uzytkownikami" class="button float"><i class="icon-back"></i>&nbsp;Powrót</a>

				<div class="button filter">
					Filtry
					<div class="roll"><svg><path d="<?php
						if ($roll) echo 'M 0 10 l 7 -5 l 7 5';
						else echo 'M 0 7 l 7 5 l 7 -5';
					?>"></path></svg></div>
				</div>

				<div class="area<?= $roll ? ' show-area':'' ?>">
					<form class="filter-form" action="" method="post">
						<div>
							<span class="filter-label">Filtry:</span>

							<input type="radio" name="compare_filter" id="filter0" value="0" <?= $filter == "0" ? 'checked':'' ?>>
							<label class="button filter-option" for="filter0"> Wszystkie </label>

							<input type="radio" name="compare_filter" id="filter1" value="1" <?= $filter == "1" ? 'checked':'' ?>>
							<label class="button filter-option" for="filter1"> Błędne </label>

							<input type="radio" name="compare_filter" id="filter2" value="2" <?= $filter == "2" ? 'checked':'' ?>>
							<label class="button filter-option" for="filter2"> Poprawne </label>
						</div>

						<div>
							<span class="filter-label">Klasa:</span>

							<input type="radio" name="compare_class" id="class0" value="0" <?= $class == "0" ? 'checked':'' ?>>
							<label class="button filter-option" for="class0"> Wszystkie </label>

							<input type="radio" name="compare_class" id="class1" value="1" <?= $class == "1" ? 'checked':'' ?>>
							<label class="button filter-option" for="class1"> 1 </label>

							<input type="radio" name="compare_class" id="class2" value="2" <?= $class == "2" ? 'checked':'' ?>>
							<label class="button filter-option" for="class2"> 2 </label>

							<input type="radio" name="compare_class" id="class3" value="3" <?= $class == "3" ? 'checked':'' ?>>
							<label class="button filter-option" for="class3"> 3 </label>

							<input type="radio" name="compare_class" id="class4" value="4" <?= $class == "4" ? 'checked':'' ?>>
							<label class="button filter-option" for="class4"> 4 </label>
						</div>

						<div>
							<span class="filter-label">Sortuj według:</span>

							<input type="radio" name="compare_sort" id="sort0" value="0" <?= $sort == "0" ? 'checked':'' ?>>
							<label class="button filter-option" for="sort0"> Największe podobieństwo </label>

							<input type="radio" name="compare_sort" id="sort1" value="1" <?= $sort == "1" ? 'checked':'' ?>>
							<label class="button filter-option" for="sort1"> Najmniejsze podobieństwo </label>
						</div>
					</form>
				</div>
			</nav>

			<main>
				<section>

				<?php
					foreach ($user_answers as $user_answer) {
echo <<<END
\n				<article>
					<div class="frame">
						<header>
							<div class="bar">

							{$user_answer['points']}/{$user_answer['max_points']}
							
							<div class="bar-options">
END;
							$is_html_code = false;
							if($user_answer['code']) {
								$is_html_code = preg_match("/<body[\S\s]+<\/body[\S\s]/i", $user_answer['code']) + preg_match("/<html[\S\s]+<\/html[\S\s]/i", $user_answer['code']);
							}

							if ($_SESSION['group_name'] == 'admin' && $is_html_code && $user_answer['code']) echo '<a class="bar-element" target="_blank" href="html_viewer.php?code_id='.$user_answer['code_id'].'"><i class="icon-external-link"></i>Wyświetl stronę</a></br>';
							if ($_SESSION['group_name'] == 'admin' && $user_answer['code']) echo '<a class="bar-element" target="_blank" href="porownanie?user='.$user_answer['user_id'].'&task='.$user_answer['id'].'"><i class="icon-compare"></i>Porównaj</a>';
echo <<<END
\n
							</div>

END;

							if ($user_answer['max'] !== null) echo '<div class="bar-icon">'.getResultIcon($user_answer['max']).'</div>';
echo <<<END
\n							</div>

							<h2>{$user_answer['name']} {$user_answer['surname']} {$user_answer['similarity']}%</h2>
						</header>
END;
						
						if($user_answer['code']) {
echo <<<END
\n						<textarea class="text_long" name="code" readonly>
{$user_answer['code']}
						</textarea>
END;
						} else {
echo <<<END
\n			<h5>Brak kodu!</h5>
END;
			}
echo <<<END
\n					</div>
				</article>
END;
					}
				?>

				</section>
			</main>
		</div>
	</div>

	<script>

	// To dziala tylko kiedy script.min.js lub script.js jest najpierw zawarty gdzies w pliku! Inaczej autoGrow nie jest zdefiniowany
	let textareas = document.getElementsByTagName('textarea');
	for(let i = 0; i < textareas.length; i++) {
		autoGrow(textareas[i]);
	}
	
	</script>

	<footer>
		<div class="footer">
			<span>&copy; 2022-<?= date('Y') ?> II LO im. M. Kopernika w Cieszynie;</span>
			<span>Projekt i wykonanie: Mateusz Antkiewicz</span>
		</div>
	</footer>
</body>
</html>
