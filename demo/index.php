<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, minimum-scale=1, initial-scale=1, user-scalable=yes">

	<title>grain-read-more demo</title>

	<script src="../bower_components/webcomponentsjs/webcomponents-lite.js"></script>

	<style>
		body {
			margin: 0;
		}
	</style>

</head>
<body>
<?php
	define('BOWER_PATH', __DIR__ . '/../bower_components');

	require_once (__DIR__ . '/../Tag.php');
	Tag::openTag('div', ['class' => 'something']);
		echo '<p>my content</p>';
	Tag::closeTag('div');

?>
</body>
</html>