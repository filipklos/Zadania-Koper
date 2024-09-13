<?php

require_once 'config/database.php';

try {

	if (isset($_GET['task'])) $taskId = intval($_GET['task']);
	else $taskId = '';

	if (isset($_GET['category'])) {
		$catQuery = $db->prepare("SELECT tasks.id FROM tasks WHERE category_id LIKE :category_id");
		$catQuery->bindValue(':category_id', $_GET['category'], PDO::PARAM_INT);
		$catQuery->execute();

		foreach ($catQuery->fetchAll() as $id) $taskId .= $id['id'] . ', ';
		$taskId = substr($taskId, 0, -2);
	}

	$tasksQuery = $db->prepare("SELECT tasks.id, category, title, content
		FROM tasks INNER JOIN categories ON category_id = categories.id
		WHERE tasks.id IN ($taskId) AND visible
		ORDER BY title");
	$tasksQuery->execute();
	
	$tasks = $tasksQuery->fetchAll();

} catch (PDOException $error) {

	echo "<script>window.close()</script>";

}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
	<meta charset="utf-8">
	<title>Drukuj</title>
	<?php include 'items/meta.php' ?>
	<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
	<script type="text/javascript" id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

	<style>
		h1.to-print, h3.to-print {
			margin: 5px;
			padding: 0;
			text-align: left;
		}
		h1.to-print {
		    display: inline;
		    font-style: normal;
		}
		body { background-color: #fff; }
		h3.to-print { display: inline-block; }
		div.to-print { margin-bottom: 50px; }
	</style>
</head>
<body>
<?php
	if (!empty($tasks))
	foreach ($tasks as $task) {
		$content = str_replace("\n", '<br>', $task['content']);
echo<<<END
	<h1 class="to-print">{$task['title']}</h1>
	<h3 class="to-print">Kategoria: {$task['category']}</h3>
	<hr>
	<div class="content to-print">$content</div>
END;
	}
?>

	<script>
		<?= isset($_GET['img']) && $_GET['img'] == 'no' ? 'document.querySelectorAll("img").forEach(el => el.removeAttribute("src"));' : '' ?>
		
		function main() {
			window.print();
			window.addEventListener('click', (e) => { window.close(); });
			window.addEventListener('keydown', (e) => { window.close(); });
		}

		setTimeout(main, 1000);
	</script>
</body>
</html>
