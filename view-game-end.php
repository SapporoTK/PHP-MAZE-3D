<?php if( !defined("PHP_MAZE_VERSION") ) exit(); ?>
<!DOCTYPE html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>PHP-MAZE / Powerd by planet-green.com</title>
	<link rel="stylesheet" type="text/css" href="maze.css">
</head>
<body id="game-end">
<div id="main">
	<div id="your-score">
		あなたのスコア ：<?php echo sprintf("%05d",$_SESSION['score']); ?>
	</div>

	<a href="maze.php">もう一度プレイする</a><br />

	<div id="scores">
		<?php printScore($r_name,$r_score);	/*スコア一覧を表示*/ ?>
	</div>
</div>
</body>
</html>