<?php echo validation_errors('<div class="alert-message block-message error" data-alert="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>', '</div>'); ?>
<h2>hi, <?php echo $id; ?>, 請輸入您想設定的新密碼</h2>
<form action="<?php echo base_url(); ?>auth/forgotten_password_complete/<?php echo $id; ?>/<?php echo $auth; ?>" method="post">
	<fieldset>
		<div class="clearfix">
			<label for="pw">新密碼</label>
			<div class="input">
				<input id="pw" type="password" name="pw" />
			</div>
		</div>
		<div class="clearfix">
			<label for="pw2">確認新密碼</label>
			<div class="input">
				<input id="pw2" type="password" name="pw2" />
			</div>
		</div>
		<div class="clearfix actions">
			<p>
				<button class="btn btn-primary" type="submit">送出</button>
				<button class="btn" type="reset">重填</button>
			</p>
		</div>
	</fieldset>
</form>
