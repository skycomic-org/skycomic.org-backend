<?php
	$browser = $this->agent->browser();
	if (!in_array($browser, ["Chrome", "Firefox"])):
?>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.2/js/bootstrap.min.js"></script>
<div id="ie8sucks" class="modal fade">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	<h3>你知道你的瀏覽器已經太舊了嗎？</h3>
  </div>
  <div class="modal-body">
	<p>由於Skycomic採用新技術，已經不再支援舊型的瀏覽器，因此建議您升級成新款瀏覽器，以獲得最佳體驗。</p>
	<div style="float:left;text-align:center">
		<a target="_blank" href="https://www.google.com/intl/zh-TW/chrome/browser/">
			<img width="128" src="<?= base_url('image/iesucks/chrome.png') ?>" alt=""><br>
			Chrome
		</a>
	</div>
	<div style="float:left;text-align:center">
	<a target="_blank" href="http://moztw.org/firefox/">
		<img width="128" src="<?= base_url('image/iesucks/firefox.png') ?>" alt=""><br>
		Firefox
	</a>
	</div>
	<div style="clear:left">
	</div>
  </div>
  <div class="modal-footer">
	<a href="#" class="btn">關閉</a>
  </div>
</div>

<script type="text/javascript">
//<![CDATA[
	$('#ie8sucks').modal({show:true})
//]]>
</script>

<?php
	endif;
?>
