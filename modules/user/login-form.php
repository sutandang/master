<?php defined( '_VALID_BBC' ) or die( 'Restricted access' );

$output = false;
include _ROOT.'modules/user/login-action.php';
if($user->id > 0)
{
  redirect('index.php?mod='.$Bbc->home_user);
}
$user_url = $Bbc->uri;
if (!empty($_POST['url']))
{
  $user_url = $_POST['url'];
}else
if (!empty($_GET['return']))
{
  $user_url = $_GET['return'];
}else
if (!empty($_GET['url']))
{
  $user_url = $_GET['url'];
// }else
// if (!empty($_SERVER['HTTP_REFERER']))
// {
//   $user_url = $_SERVER['HTTP_REFERER'];
}
include tpl('login-form.html.php');

