<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Database error</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="/css/bootstrap.css">
</head>

<header>
	<div class="topbar-wrapper" style="z-index: 5;">
		<div class="topbar" data-dropdown="dropdown">
			<div class="topbar-inner">
				<div class="container">
					<h3><a href="/">SkyComic</a></h3>
				</div>
			</div><!-- /topbar-inner -->
		</div><!-- /topbar -->
	</div>
</header>

<body>
	<div class="container" style="position:relative;margin-top:60px;">
		<h1><?php echo $heading; ?></h1>
		<?php echo $message; ?>
	</div><!-- /container -->

<footer>
	<p style="text-align:center;">Skycomic © 2009-<?php echo date('Y'); ?></p>
</footer>
</body>

</html>
