<?php  if (!defined('_VALID_BBC')) exit('No direct script access allowed');

// Ini adalah block untuk berbagai keperluan dalam menampilkan content baik itu article, gallery, download, video maupun audio. bisa anda tentukan berdasarkan category, latest, popular dan related content
$Content = _class('content');
$cat     = array('publish' => 1);

if (!isset($config['cat_id']))
{
	$config['cat_id'] = -2;
}
if (!isset($config['type_id']))
{
	$config['type_id'] = @intval($config['type_id']);
}
if($config['type_id'] > 0)
{
	$add_sql = 'AND c.type_id='.$config['type_id'];
}else $add_sql = '';
if(@$config['kind_id'] > -1)
{
	switch ($config['kind_id'])
	{
		case '0':
		case '1':
		case '2':
		case '3':
		case '4':
			$add_sql .= empty($add_sql) ? '' : ' ';
			$add_sql .= 'AND c.kind_id='.$config['kind_id'];
			break;
	}
}
$limit = $config['tot_list'] ? 'LIMIT 0, '.$config['tot_list'] : '';
if($config['type_id'] == -1) // RELATED CONTENT
{
	$cat['publish'] = 0;
	if(@$_GET['mod']=='content.detail')
	{
		$data = content_related(@$_GET['id'], $config['tot_list'], $add_sql);
		$cat  = array(
			'publish'    => ($data['total']>0 ? 1 : 0),
			'list'       => $data['list'],
			'total'      => $data['total'],
			'total_page' => 1,
			);
	}
}else
if($config['type_id'] == -2) // INSERT IDs
{
	$ids = $config['ids'];
	ids($ids);
	$q="SELECT c.*, t.title, t.intro, t.content FROM bbc_content AS c
			LEFT JOIN bbc_content_text AS t ON (c.id=t.content_id AND t.lang_id=".lang_id().")
			WHERE c.publish=1 AND c.id IN($ids) ORDER BY c.id DESC $limit";
	$arr				= $db->cacheGetAssoc($q);
	$cat['list']= array();
	if(!empty($ids))
	{
		foreach(explode(',', $ids) AS $i)
		{
			$data = array();
			if (!empty($arr[$i]))
			{
				$data       = $arr[$i];
				$data['id'] = $i;
			}else{
				$data = content_fetch($i);
			}
			if (!empty($data))
			{
				$cat['list'][] = $data;
			}
		}
	}
	$cat['total']      = count($cat['list']);
	$cat['total_page'] = 1;
}else
if($config['type_id'] == -3) // Front Page
{
	$cat['list']			= content_frontpage();
	$cat['total']			= count($cat['list']);
	$cat['total_page']= 1;
}else
if($config['cat_id']==-1) // popular content
{
	$timestamp = strtotime('-'.$config['popular']);
	if ($timestamp > 0)
	{
		$date = date('Y-m-d', $timestamp);
		$add_sql .= " AND (c.`modified` >'{$date}' || c.`created` > '{$date}')";
	}
	$q="SELECT c.*, t.title, t.intro, t.content FROM bbc_content AS c
			LEFT JOIN bbc_content_text AS t ON (c.id=t.content_id AND t.lang_id=".lang_id().")
			WHERE c.publish=1 {$add_sql} ORDER BY hits DESC {$limit}";
	$cat['list']       = $db->cacheGetAll($q);
	$cat['total']      = count($cat['list']);
	$cat['total_page'] = 1;
}else
if($config['cat_id']==-2) // latest content
{
	$q="SELECT SQL_CALC_FOUND_ROWS c.*, t.title, t.intro, t.content FROM bbc_content AS c
			LEFT JOIN bbc_content_text AS t ON (c.id=t.content_id AND t.lang_id=".lang_id().")
			WHERE c.publish=1 {$add_sql} ORDER BY c.id DESC $limit";
	$cat['list']       = $db->cacheGetAll($q);
	$cat['total']      = $db->cacheGetOne("SELECT FOUND_ROWS(), 'latest'");
	$cat['total_page'] = ceil($cat['total'] / $config['tot_list']);
}else
if(is_numeric($config['cat_id']))
{
	$config['add_sql'] = $add_sql;
	$cat = content_cat_list($config['cat_id'], 0, $config);
}else {
	$cat['publish'] = false;
}
if($cat['publish'])
{
	foreach($cat['list'] AS $i => $d)
	{
		$cat['list'][$i]['label'] = content_title($d['title'], @intval($config['limit_title']), @$config['limit_title_by']);
	}
	include tpl(@$config['template'].'.html.php', 'default.html.php');
}