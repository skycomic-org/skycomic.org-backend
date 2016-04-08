<?php echo validation_errors('<div class="alert alert-error" data-alert="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>', '</div>'); ?>
<h2>請輸入您的Email, 我們將會寄重設密碼信件給您</h2>
<form action="<?php echo base_url(); ?>auth/forgotten_password" method="post">
	<fieldset>
		<div class="clearfix">
			<label for="email">Email</label>
			<div class="input">
				<input id="email" type="text" name="email" />
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
