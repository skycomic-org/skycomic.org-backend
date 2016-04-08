<!Doctype html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>
<body>
	<p>Dear <?php echo $name; ?></p>
	<p>首先先恭喜你成為SkyComic的一員,</p>
	<p>你在SkyComic註冊了一個新帳號「<?php echo $id; ?>」!</p>
	<p>但是要驗證email才能開始使用喔!</p>
	<p><a href="<?= base_url('auth/activate/' . $id . '/' . $auth) ?>">請點此</a>來啟用您的帳號!</p>
	<p>驗証成功之後呢，<a href="<?= base_url(); ?>">請點此</a>來登入，登入之後就可以欣賞SkyComic的漫畫了。</p>
	<p>如果未來某一天你忘記了密碼，<a href="<?= base_url('auth/forgotten_password/' . $id . '/' . $auth); ?>">請點此</a>更改密碼!</p>
</body>
</html>