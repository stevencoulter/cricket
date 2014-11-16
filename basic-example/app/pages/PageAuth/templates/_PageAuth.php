<html>
	<head>
		<?= $cricket->head(); ?>
		<script type="text/javascript" src="<?= $cricket->resource_url("/resources/jquery-1.10.2.js") ?>"></script>
	</head>
	<body>
		<center>
			Welcome, <?= $user->getUsername(); ?>
			<br>
			<button onclick="<?= $cricket->call('validate'); ?>">Validate</button>
			<br>
			<button onclick="<?= $cricket->call('logout'); ?>">Logout</button>
		</center>
	</body>
</html>