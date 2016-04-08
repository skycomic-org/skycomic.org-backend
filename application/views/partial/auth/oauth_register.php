<?php echo validation_errors('<div class="alert alert-error" data-alert="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>', '</div>'); ?>
<h2>hi, <?php echo $name; ?>, 由於您是第一次使用，請填寫一些資料!</h2>
<form action="<?php echo base_url(); ?>auth/oauth_register" method="post">
	<fieldset>
		<div class="clearfix">
			<label for="nickname">暱稱</label>
			<div class="input">
				<input id="nickname" type="text" name="nickname" />
				<span class="help-inline">討論區使用</span>
			</div>
		</div>
		<div class="clearfix">
			<label for="relation">你從哪裡知道這個網站</label>
			<div class="input">
				<input id="relation" name="relation" type="text" />
			</div>
		</div>
		<div class="clearfix actions">
			<p>
				<button class="btn btn-primary" type="submit">註冊</button>
				<button class="btn" type="reset">重填</button>
			</p>
		</div>
	</fieldset>
</form>
