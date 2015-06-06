<div class="page-header hidden-phone">
	<h1>請登入以使用SkyComic <small>你可以使用 guest / guestguest 這組帳密來體驗 SkyComic!</small></h1>
</div>
<div class="row-fluid">
	<div class="span6">
		<?= validation_errors('<div class="alert alert-error" data-alert="alert"><button type="button" class="close" data-dismiss="alert">&times;</button>', '</div>'); ?>
		<h3 class="hidden-phone">使用Skycomic帳號登入</h3>
		<form id="register-login-form" action="<?= base_url(); ?>auth/login" method="post">
			<fieldset>
				<div class="clearfix">
					<label for="id">帳號</label>
					<div class="input">
						<input id="id" type="text" name="id" />
						<div class="register-fields help-inline">3~20位大小寫英文+數字</div>
					</div>
				</div>
				<div class="clearfix">
					<label for="pw">密碼</label>
					<div class="input">
						<input id="pw" type="password" name="pw" />
						<div class="register-fields help-inline">8~20位字元</div>
					</div>
				</div>
<?php if ( ALLOW_REGISTER ) : ?>
				<div class="register-fields">							
					<div class="clearfix">
						<label for="pw2">確認密碼</label>
						<div class="input">
							<input id="pw2" name="pw2" type="password" />
						</div>
					</div>
					<div class="clearfix">
						<label for="nickname">暱稱</label>
						<div class="input">
							<input id="nickname" type="text" name="nickname" />
							<span class="help-inline">討論區使用</span>
						</div>
					</div>
					<div class="clearfix">
						<label for="email">Email</label>
						<div class="input">
							<input id="email" name="email" type="text" />
							<span class="help-inline">特別注意請不要手滑</span>
						</div>
					</div>
					<div class="clearfix">
						<label for="name">姓名</label>
						<div class="input">
							<input id="name" name="name" type="text" />
						</div>
					</div>
					<div class="clearfix">
						<label for="relation">你從哪裡知道這個網站</label>
						<div class="input">
							<input id="relation" name="relation" type="text" />
						</div>
					</div>
					<div class="clearfix">
						<label for="captcha">驗證碼</label>
						<div class="input">
							<p>
								<img src="<?= base_url(); ?>auth/captcha" id="captcha-img" alt="RandomNumber">
								<a href="#" onclick="$('#captcha-img').get(0).src = '<?= base_url(); ?>auth/captcha/'+Math.random();$('#captcha').get(0).focus();">看不懂?換個文字吧!</a>
							</p>
							<p>
								<input id="captcha" name="captcha" type="text" />
							</p>
						</div>
					</div>
				</div>
<?php endif; ?>
				<div class="clearfix form-actions">
					<p>
						<button class="btn btn-primary" type="submit">登入</button>
<?php if ( ALLOW_REGISTER ): ?>
						<button class="btn btn-danger register-hide" type="submit">註冊</button>
<?php endif; ?>
						<a href="<?= base_url(); ?>auth/forgotten_password">忘記密碼?</a>
					</p>
				</div>
			</fieldset>
		</form>
	</div>
	<div class="span5">
		<h3>使用openid登入</h3>
		<a href="<?= base_url(); ?>auth/oauth/google"><img src="<?= CDN_LINK; ?>image/oauth/google.png" alt="google oauth"></a>
		<a href="<?= base_url(); ?>auth/oauth/facebook"><img src="<?= CDN_LINK; ?>image/oauth/facebook.png" alt="facebook oauth"></a>
		<a href="<?= base_url(); ?>auth/oauth/yahoo"><img src="<?= CDN_LINK; ?>image/oauth/yahoo.png" alt="yahoo oauth"></a>
	</div>
</div>
<script type="text/javascript">
//<![CDATA[
	window.data = window.data || {};
	window.data.Form_mem = {
		dom: '#register-login-form',
		data:<?= json_encode($form); ?>
	};
//]]>
</script>
