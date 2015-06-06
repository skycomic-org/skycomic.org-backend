<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>404 Page Not Found - SkyComic</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<link rel="stylesheet" href="/css/bootstrap.css">
	<link rel="stylesheet" href="/css/bootstrap-responsive.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
	
	
	<div class="navbar navbar-fixed-top"> 
      <div class="navbar-inner"> 
        <div class="container"> 
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"> 
            <span class="icon-bar"></span> 
            <span class="icon-bar"></span> 
            <span class="icon-bar"></span> 
          </a> 
          <a class="brand" href="/">SkyComic</a>
          <div class="nav-collapse"> 

          </div><!--/.nav-collapse --> 
        </div> 
      </div> 
    </div> 


	
	<div class="container" style="position:relative;margin-top:60px;">
		<h1><?php echo $heading; ?></h1>
		<?php echo $message; ?>
	</div><!-- /container -->

<footer>
	<p style="text-align:center;">Skycomic © 2009-<?php echo date('Y'); ?></p>
</footer>
</body>

</html>
