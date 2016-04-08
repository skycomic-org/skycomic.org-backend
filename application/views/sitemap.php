<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
  <?php foreach ($urls as $url): ?>
  <url>
    <loc><?= $url->url ?></loc>
    <?php if (isset($url->image)): ?>
    <image:image>
       <image:loc><?= $url->image ?></image:loc>
    </image:image>
    <?php endif; ?>
  </url>
  <?php endforeach; ?>
</urlset>