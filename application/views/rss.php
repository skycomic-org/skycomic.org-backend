<?php echo'<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" xmlns:blogChannel="http://backend.userland.com/blogChannelModule">
<channel>
	<title>SkyComic最新漫畫!</title>
	<link>http://www.skycomic.org/</link>
	<description>最新漫畫 - SkyComic</description>
	<language>zh-tw</language>
	<lastBuildDate><?php echo date('D, d M Y H:i:s'); ?> GMT</lastBuildDate>
	<ttl>20</ttl>
<?php
	foreach($new as $row):
		$row = (array) $row;
?>
	<item>
		<title><?php echo $row['title']; ?> <?php echo $row['chapter']; ?></title>
		<link>http://www.skycomic.org/main#/browse/<?php echo $row['cid']; ?>/1</link>
		<guid isPermaLink="true">http://www.skycomic.org/main#/browse/<?php echo $row['cid']; ?>/1</guid>
		<pubDate><?php echo date('D, d M Y H:i:s',strtotime($row['update_time'])); ?> GMT</pubDate>
	</item>
<?php endforeach; ?>
</channel>
</rss>