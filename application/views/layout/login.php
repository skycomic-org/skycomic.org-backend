<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>SkyComic - 地表最快的漫畫網站</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.1.0/css/bootstrap-combined.min.css" rel="stylesheet">
	<link rel="stylesheet" href="<?= base_url('css/main.css') ?>">
	<?php $this->load->view('google'); ?>
	<?php $this->load->view('partial/meta') ?>
</head>

<body>
    <div id="header-menu" class="navbar navbar-fixed-top navbar-inverse"> 
      <div class="navbar-inner"> 
        <div class="container-fluid"> 
          <a class="btn btn-navbar" data-toggle="collapse" data-target="#topmenu"> 
            <span class="icon-bar"></span> 
            <span class="icon-bar"></span> 
            <span class="icon-bar"></span> 
          </a> 
          <a class="brand" href="<?php echo base_url(); ?>">SkyComic</a>
          <div class="nav-collapse"> 
            <ul class="nav"> 

			<li data-link="#/error-report"><a href="#/error-report">問題回報</a></li>
	
            </ul> 
          </div><!--/.nav-collapse --> 
        </div> 
      </div> 
    </div>

    <div class="container" id="container" style="margin-top: 60px;">
		<?php if (!ALLOW_REGISTER): ?>
		<div class="alert alert-warning">註冊暫不開放，造成不便敬請見諒！</div>
		<?php endif; ?>
		<?php if ( ($flash_error = $this->session->flashdata('error')) !== False ): ?>
			<div class="alert  alert-error" data-alert="alert">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
				<?php echo $flash_error; ?>
			</div><!-- /alert -->
		<?php endif; ?>
		<?php if ( ($flash_success = $this->session->flashdata('success')) !== False ): ?>
			<div class="alert  alert-success" data-alert="alert">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
				<?php echo $flash_success; ?>
			</div><!-- /alert -->
		<?php endif; ?>
		<?php if (isset($content)) echo $content; ?>
		<div style="text-align:center">
			<?= $this->load->view('partial/ad-bottom') ?>
		</div>
	</div><!-- /container -->
	
	<?php if (isset($js)): ?>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.2.2/underscore-min.js"></script>
	<script type="text/javascript" src="<?= CDN_LINK ?>js/libs/backbone/backbone-optamd3-min.js"></script>
	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery-scrollTo/1.4.3/jquery.scrollTo.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.1.0/js/bootstrap.min.js"></script>
	<script type="text/javascript">
		_gaq.push(['_trackPageview']);
		window.BASE_URL = "<?= base_url() ?>";
		window.JS_VERSION = "<?= JS_VERSION ?>";
	</script>
	<script data-main="<?= base_url() ?>js/<?= JS_VERSION ?>/<?= $js ?>" src="//cdnjs.cloudflare.com/ajax/libs/require.js/2.1.5/require.min.js"></script>
	<?php endif; ?>
	
	<?php $this->load->view('killie'); ?>
	<footer>
		<p style="text-align:center;">Skycomic © 2009-<?php echo date('Y'); ?></p>
	</footer>
</body>


</html>
