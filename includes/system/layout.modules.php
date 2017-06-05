<?php  if ( ! defined('_VALID_BBC')) exit('No direct script access allowed');

ob_start();
if(!file_exists($Bbc->mod['root'].'_switch.php'))
{
	echo "invalid modules <b>".$Bbc->mod['name']."</b>";
}else{
	$Bbc->currDir = getcwd().'/';
	chdir($Bbc->mod['root']);
	if(_ADMIN == '' && is_file('_function.php'))
	{
		include_once '_function.php';
	}else
	if(_ADMIN != '')
	{
	  if(is_file('../_function.php'))
	  {
		  include_once '../_function.php';
		}
		if(is_file('_function.php'))
		{
		  include_once '_function.php';
		}
	}
	if(is_file('_setting.php'))
	{
		include '_setting.php';
	}
	if (!defined('_AsYnCtAsK'))
	{
		include '_switch.php';
	}
	chdir($Bbc->currDir);
}
$Bbc->content = ob_get_contents();
ob_end_clean();
if (defined('_AsYnCtAsK'))
{
	$sys->layout = _ROOT.'templates/admin/none.php';
}else{
	if(_ADMIN == '')
	{
		if(_SEO)
		{
			function module_replace($matches)
			{
				$matches[3] = (empty($matches[3])) ? '' : site_url($matches[3]);
				$output = $matches[1].'='.$matches[2].$matches[3].$matches[4];
				return $output;
			}
			$Bbc->regex = '~(href|action)=(["\'])?(.*?)(["\'>][\s]{0,}){1}~is';
			$Bbc->content = preg_replace_callback($Bbc->regex, "module_replace", $Bbc->content);
		}
		if($sys->is_stop || !empty($_GET['is_ajax']))
		{
			$Bbc->content = preg_replace('~(\ssrc=["|\']?)(blocks|images|includes|modules|templates)/~is', '$1'._URL.'$2/', $Bbc->content);
			echo $Bbc->content; exit;
		}
		if(!$Bbc->is_mobile)
		{
			$sys->block_fetch();
		}
	}else{
		if($sys->is_stop || !empty($_GET['is_ajax']))
		{
			echo $Bbc->content; exit;
		}
	}
}
