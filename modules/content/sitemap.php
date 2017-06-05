<?php  if (!defined('_VALID_BBC')) exit('No direct script access allowed');

$sys->stop();
$_URL	= _URL;
$links= $content = array();
$links[] = $_URL;
$content[]= '<url><loc>'.$_URL.'</loc><priority>1.0</priority></url>';
/*=====================================
 * CREATE CONTENTS
 *===================================*/
$q = 'SELECT c.id, c.created, c.modified, c.revised, t.title FROM `bbc_content` AS c LEFT JOIN `bbc_content_text` AS t 
ON(t.content_id=c.id AND t.lang_id='.lang_id().') WHERE c.publish=1 ORDER BY id DESC LIMIT 0, 50';
$r = $db->cacheGetAll($q);
foreach($r AS $d)
{
	$link = content_link($d['id'], $d['title']);
	if(!in_array($link, $links))
	{
		$modified	= $d['revised'] > 0 ? $d['modified'] : $d['created'];
		$links[]	= $link;
		$content[]= '<url><loc>'.htmlentities($link).'</loc><lastmod>'.date('Y-m-d', strtotime($modified)).'</lastmod></url>';
	}
}

/*=====================================
 * CREATE CATEGORIES
 *===================================*/
$q = 'SELECT c.id, t.title FROM `bbc_content_cat` AS c LEFT JOIN `bbc_content_cat_text` AS t 
ON(t.cat_id=c.id AND t.lang_id='.lang_id().') WHERE c.publish=1 ORDER BY id ASC';
$r = $db->cacheGetAll($q);
foreach($r AS $d)
{
	$link = content_cat_link($d['id'], $d['title']);
	if(!in_array($link, $links))
	{
		$prior = $link == $_URL ? '1.0' : '0.6';
		$links[] = $link;
		$content[] = '<url><loc>'.htmlentities($link).'</loc></url>';
	}
}

/*=====================================
 * CREATE YOUR OWN MENUS
 *===================================*/
$r = $sys->menu_get_all();
foreach($r AS $d)
{
	$link = content_menu_link($d['link'], $d['seo'], $_URL);
	if(!in_array($link, $links))
	{
		$prior = $link == $_URL ? '1.0' : '0.6';
		$links[] = $link;
		$content[] = '<url><loc>'.htmlentities($link).'</loc></url>';
	}
}
/*=====================================
 * START DISPLAY XML PAGE
 *===================================*/
ob_start();
echo '<?xml version="1.0" encoding="UTF-8"?>';/*<?xml-stylesheet type="text/xsl" href="http://fisip.net/images/sitemap.xsl"?>';#*/?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
	xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php	echo implode('', $content);	?>
</urlset>
<?php
$output = ob_get_contents();
ob_end_clean();
header('Content-type: text/xml');
header('Content-length: ' . strlen($output));
echo $output;
die;
function content_menu_link($link, $seo, $main_url)
{
	if(empty($link)){
		$output = $main_url;
	}else
	if(preg_match('~^(?:ht|f)tps?://~', $link)){
		$output = $link;
	}else
	if(_SEO and !empty($seo)){
		$output = $main_url.$seo.'.html';
	}else{
		$output = $main_url.$link;
	}
	return $output;
}