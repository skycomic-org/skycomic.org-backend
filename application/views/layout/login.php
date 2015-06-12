<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>SkyComic - 地表最快的漫畫網站</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.1.0/css/bootstrap-combined.min.css" rel="stylesheet">
	<link rel="stylesheet" href="<?= base_url('css/style.css?v=' . JS_VERSION); ?>">
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
		<li><a target="_blank" href="https://github.com/skycomic-org/skycomic.org/issues">錯誤回報</a></li>
		<li><a target="_blank" href="https://github.com/skycomic-org/skycomic.org">Github</a></li>
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
	<script type="text/javascript">
		_gaq.push(['_trackPageview']);
		window.BASE_URL = "<?= base_url() ?>";
		window.JS_VERSION = "<?= JS_VERSION ?>";
	</script>
	<script src="<?= base_url('js/libs/require-min.js') ?>"></script>
	<script src="<?= base_url('js/login.js?v=' . JS_VERSION) ?>"></script>
	
	<?php $this->load->view('killie'); ?>
	<footer>
		<p style="text-align:center;">Skycomic © 2009-<?php echo date('Y'); ?></p>
	</footer>
</body>


</html>
