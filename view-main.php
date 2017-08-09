<?php if( !defined("PHP_MAZE_VERSION") ) exit(); ?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>PHP-MAZE / Powerd by planet-green.com</title>
	<link rel="stylesheet" type="text/css" href="maze.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
	<script type="text/javascript">
		var unique_key = "<?php echo $_SESSION['unique_key']; ?>";
	</script>
	<script type="text/javascript" src="maze.js"></script>

</head>
<body id="main">

<div id="app-title">PHP-MAZE v<?=PHP_MAZE_VERSION?></div>

<!--image-->
<div id="div-maze-img" style=" width: <?php echo VIEW_WIDTH; ?>px; height: <?php echo VIEW_HEIGHT; ?>px;">
	<img id="maze-img" style="display: none;" width="<?php echo VIEW_WIDTH; ?>" height="<?php echo VIEW_HEIGHT; ?>" />
</div>
<br />


<!--arrow buttons-->
<table cellspacing="0" cellpadding="0" border="0">
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="image" src="arrow_front.gif" alt="move" border="0" width="32" height="32" onClick='move("up");' />
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td>
			<input type="image" src="arrow_left.gif" alt="left" border="0" width="32" height="32" onClick='move("left");' />
		</td>
		<td>&nbsp;</td>
		<td>
			<input type="image" src="arrow_right.gif" alt="right" border="0" width="32" height="32" onClick='move("right");' />
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="image" src="arrow_back.gif" alt="back" border="0" width="32" height="32" onClick='move("back");' />
		</td>
		<td>&nbsp;</td>
	</tr>
</table>
<br />
<small>カーソルキー（矢印キー）でも操作できます。</small><br />

<br />
Powerd by <a href="https://planet-green.com/" target="_blank" rel="noopener">https://planet-green.com/</a>

<div id="entry-score" style="display: none;">
	<form id="form-entry-score" name="form-entry-score" method="POST" action="maze.php?mode=game_end">
		<input type="hidden" id="user_name" name="user_name" value="" />
	</form>
</div>

</body>
</html>
