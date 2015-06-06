<?php
require_once MEDIAWIKI_PATH . 'mediawiki-zhconverter.inc.php';
function gb2big5 ($in) {
	return @MediaWikiZhConverter::convert($in, "zh-tw");
}
