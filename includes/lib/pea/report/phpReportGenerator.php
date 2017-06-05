<?php
$formName		= $_GET['formName'];
$formType		= $_GET['formType'];
$reportType	= $_GET['reportType'];

$file	= "php". ucfirst(strtolower($formType)) . ucfirst(strtolower($reportType)) . ".php";
include_once( $file );

$class= 'php'.$formType.$reportType;
$obj	= new $class();

foreach($_GET AS $var => $val)
{
	$obj->$var = $val;
}

$obj->write();