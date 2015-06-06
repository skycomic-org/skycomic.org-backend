<ul class="nav">
	<li data-link="#/browse"><a href="#/donation">贊助</a></li>
	<li data-link="#/guestbook"><a href="#/guestbook">錯誤回報</a></li>
</ul>
<ul class="nav pull-right">
	<li class="dropdown">
		<a href="#" id="config-btn" class="dropdown-toggle" data-toggle="dropdown"><img src="<?= base_url('image/icon/config.png') ?>"></a>
		<ul class="dropdown-menu">
			<li><a href="#" onclick="return false;">hi, <?php echo $this->session->userdata('nickname') ? $this->session->userdata('nickname') : $this->session->userdata('name'); ?></a></li>
			<li class="divider"></li>
			<li><a href="<?= base_url('auth/logout') ?>">登出</a></li>
		</ul>
	</li>
</ul>
<form id="search-form" class="navbar-form pull-right">
	<input id="search" type="search" name="search" placeholder="搜尋漫畫或作者!" />
	<button class="search-btn"></button>
</form>
