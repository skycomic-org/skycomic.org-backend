<?php
function page_convert ($page) {
	if( $page < 10 ) return '00'.$page;
	if( $page < 100 ) return '0'.$page;
	return strval($page);
}

function unhtmlentities ($string) {
	// replace numeric entities
	$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
	$string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
	// replace literal entities
	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
	return strtr($string, $trans_tbl);
}

function toBig5 ($fname) {
	return @iconv('UTF-8', 'BIG5//TRANSLIT//IGNORE', $fname);
}

function toUTF8 ($fname) {
	return @iconv('BIG5', 'UTF-8', $fname);
}