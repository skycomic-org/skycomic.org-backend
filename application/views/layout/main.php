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
          <div id="topmenu" class="nav-collapse"><?php if (isset($nav)) echo $nav; ?></div>
        </div>
      </div>
    </div>

	<div id="container">
		<?php if ($this->session->userdata('id') == 'guest'): ?>
		<div class="alert alert-info">
			<a class="close" href="#">&times;</a><span></span>
			<p>您現在使用的是guest帳號! 請 <a href="<?= base_url('auth/logout') ?>">點我註冊</a> 以使用我的最愛等功能喔:)</p>
		</div><!-- /alert -->
		<?php endif; ?>
		<?php if ( ($flash_error = $this->session->flashdata('error')) !== False ): ?>
			<div class="alert alert-error" data-alert="alert">
				<a class="close" href="#">&times;</a>
				<?php echo $flash_error; ?>
			</div><!-- /alert -->
		<?php endif; ?>
		<?php if ( ($flash_success = $this->session->flashdata('success')) !== False ): ?>
			<div class="alert alert-success" data-alert="alert">
				<a class="close" href="#">&times;</a>
				<?php echo $flash_success; ?>
			</div><!-- /alert -->
		<?php endif; ?>
		<div id="global-err" class="alert alert-error" style="display:none">
			<a class="close" href="#">&times;</a><span></span>
		</div><!-- /alert -->
		<?php if (isset($content)) echo $content; ?>

		<div id="Modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="Modal-Label" aria-hidden="true"></div>
</div>
	</div><!-- /container -->
	<?php $this->load->view('killie'); ?>
<footer>

	<div class="bottombar-wrapper">
		<div class="bottombar">
				<div id="bottom-div" class="container-fluid">
					<!--a href="#" class="brand">Comic queue</a-->
					<a data-nav="lt" data-false="true" href="#" class="arrow">&lt;&lt;</a>

					<?php if (!$this->agent->mobile()): ?>
					<div class="pull-left navbar" style="overflow:hidden; width:65%;height:40px;">
					<?php else: ?>
					<div class="pull-left navbar" style="overflow:hidden; width:50%;height:40px;">
					<?php endif; ?>
						<ul id="comic-queue-ul" class="nav" style="width:10000%;">
							<li><a href="#" data-false="true"><strong>請選擇想看的漫畫</strong></a></li>
						</ul>
					</div>
					<div id="comic-queue-outside" class="over-comic-queue">
						<p class="pull-right">
							<a class="btn" href="#/browse">開始觀看!</a>
						</p>
					</div>
					<div id="comic-queue-inside" class="over-comic-queue" style="display:none;">
						<p class="pull-right">
							<strong>page</strong>
							<span id="comic-page-now">1</span>
							<?php if (!$this->agent->mobile()): ?>
								<input id="comic-page-input" style="width:120px;" type="range" name="page" min="1" max="1" />
							<?php else: ?>
								<input id="comic-page-input" style="width:80px;" type="range" name="page" min="1" max="1" />
							<?php endif; ?>
							<span id="comic-page-max"></span>
							<button class="btn btn-primary">看完囉</button>
						</p>
					</div>
						<a data-nav="gt" data-false="true" href="#" class="arrow pull-right">&gt;&gt;</a>
				</div>
		</div><!-- /bottombar -->
	</div>
</footer>
<script type="text/javascript">
	window.BASE_URL = "<?= base_url() ?>";
	window.CDN_LINK = "<?= CDN_LINK ?>";
	window.THUMBNAIL = CDN_LINK + 'images/thumbnail/';
	window.JS_VERSION = "<?= JS_VERSION ?>";
	window.USERNAME = "<?= $this->session->userdata('id') ?>";
</script>
<script src="<?= CDN_LINK . 'js/libs/require-min.js' ?>"></script>
<script src="<?= CDN_LINK . 'js/main.js?v=' . JS_VERSION ?>"></script>
</body>
</html>
