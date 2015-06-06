<div class="container-fluid">
	<div class="row-fluid">
		<div class="pull-left visible-desktop" id="sidebar"></div><!--/span-->
		<div class="pull-left" id="content">
			<div id="tracy-tabs" class="pull-left">
				<ul class="horizon-tabs">
					<?php if ($this->session->userdata('id') != 'guest'): ?>
					<li class="active"><a href="#/favorite"><img src="<?= base_url('image/icon/favorite.png'); ?>">最愛</a></li>
					<li><a href="#/history"><img src="<?= base_url('image/icon/history-comics.png'); ?>">紀錄</a></li>
					<?php endif; ?>
					<li><a href="#/discover"><img src="<?= base_url('image/icon/search-black.png'); ?>">探索</a></li>
					<li><a href="#/comics"><img src="<?= base_url('image/icon/all-comics.png'); ?>">所有</a></li>
				</ul>
			</div>
			<div id="real-content" class="pull-left"></div>
		</div><!--/span-->
	</div><!--/row-->
</div><!--/container-fluid-->
