<?php /*<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>
<rss version="2.0" xmlns:blogChannel="http://backend.userland.com/blogChannelModule">
<channel>
	<title>SkyComic 我的最愛漫畫!</title>
	<link>http://www.skycomic.org/</link>
	<description>我的最愛漫畫 - SkyComic</description>
	<language>zh-tw</language>
	<lastBuildDate><?php echo date('D, d M Y H:i:s'); ?> GMT</lastBuildDate>
	<ttl>20</ttl>
<?php
	foreach($data as $row): $row = (object) $row;
?>
	<item>
		<title><?= $row->title ?> <?= $row->chapter ?></title>
		<link>http://www.skycomic.org/main#/browse/<?= $row->cid ?>/1</link>
		<guid isPermaLink="true">http://www.skycomic.org/main#/browse/<?= $row->cid ?>/1</guid>
		<pubDate><?php echo date('D, d M Y H:i:s',strtotime($row->update_time)); ?> GMT</pubDate>
		<description><![CDATA[
			<?php foreach (range(1, $row->pages) as $page): ?>
			<p><img src="http://image.skycomic.org/images/comic/<?= $row->cid ?>/<?= $page ?>"/></p>
			<?php endforeach; ?>
		]]></description>
	</item>
<?php endforeach; ?>
</channel>
</rss>

*/?><?= '<?xml version="1.0" encoding="UTF-8"?>' ?>
<feed xmlns="http://www.w3.org/2005/Atom">

  <title>SkyComic 我的最愛漫畫!</title>
  <link href="http://www.skycomic.org/" />
  <link rel="self" href="http://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>" />
  <updated><?= date('Y-m-d', strtotime($data[0]['update_time'])) . 'T' . date('H:i:s', strtotime($data[0]['update_time'])) . 'Z' ?></updated>
  <author>
    <name>Skycomic.org</name>
  </author>
  <id>urn:uuid:<?= date('Y-m-d-H-i-s', strtotime($data[0]['update_time'])) ?></id>
<?php
	foreach($data as $row): $row = (object) $row;
?>
  <entry>

	<id>http://www.skycomic.org/main#/browse/<?= $row->cid ?>/1</id>
    <title><?= $row->title ?> <?= $row->chapter ?></title>
	<link href="http://www.skycomic.org/main#/browse/<?= $row->cid ?>/1" />
    <updated><?= date('Y-m-d', strtotime($row->update_time)) . 'T' . date('H:i:s', strtotime($row->update_time)) . 'Z' ?></updated>
    <content type="xhtml">
    	<div xmlns="http://www.w3.org/1999/xhtml">
		<?php foreach (range(1, $row->pages) as $page): ?>
		<p><img src="http://image.skycomic.org/images/comic/<?= $row->cid ?>/<?= $page ?>" /></p>
		<?php endforeach; ?>
		</div>
	</content>
  </entry>
<?php endforeach; ?>
</feed>