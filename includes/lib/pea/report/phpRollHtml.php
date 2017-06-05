<?php

require_once dirname(dirname(__FILE__)).'/config.php';
include_once _PEA_ROOT.'report/phpReport.php';

class phpRollHtml extends phpReport
{
    /**
    * Konstruktor: Inisialisasi
	* example:
    * $arrHeader = array('Nama','Umur');
    * $arrData[] = array('ogy', 12);
    * $arrData[] = array('sigit', 54);
    * $arrData[] = array('ogi sigit pornawan testing excel report', 54);
    * $excel = new phpMyExcel( $fileName="report.xls", $worksheetName="report", $arrHeader, $arrData );
	*
    * @access public
    * @param string	$fileName		Nama file html hasil generate
    * @param string	$worksheetName	Nama worksheet dari HTML
	* @param array	$arrHeader		array header, yang nantinya jadi title di excelnya
	* @param array	$arrHeader		array header, yang nantinya jadi title di excelnya
    */
  var $htmlTable;
	function __construct( $fileName='', $worksheetName='', $arrHeader=array(), $arrData = array() )
	{
		$tgl	= date("Y-m-d");

		if ( $fileName == '' )		$fileName = "report". $tgl .".html";
		if ( $worksheetName == '' )	$worksheetName = "HTML Report ". $tgl;

		$this->type				= "html";
		$this->fileName			= $fileName;
		$this->worksheetName	= $worksheetName;
		$this->arrHeader		= $arrHeader;
		$this->arrData			= $arrData;
		$this->setMaxColumnWidth();
	}

	function write()
	{
		$out	= '<thead>';

		// buat header
		if ( !empty( $this->arrHeader ) )
		{
			$out	.= '<tr>';
			foreach( $this->arrHeader as $header )
			{
			  $out	.= '<th>'.$header.'</th>';
			}
			$out	.= '</tr>';
		}
		$out .= '</thead>';
		$out .= '<tbody>';

		// buat data
		if ( !empty( $this->arrData ) )
		{
			foreach( $this->arrData as $dataRow )
			{
				$out	.= '<tr>';
				foreach( $dataRow as $data )
				{
					$data = str_replace('src="images/', 'src="'._URL.'images/', $data);
					$out	.= '<td>'.$data.'</td>';
				}
				$out	.= '</tr>';
			}
		}
		$out .= '</tbody>';
		$out = <<<EOT
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>{$this->worksheetName}</title>

		<!-- Bootstrap CSS -->
		<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">

		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>
	<body>
		<table class="table table-striped table-bordered table-hover">
			{$out}
		</table>

		<!-- jQuery -->
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
		<!-- Bootstrap JavaScript -->
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
	</body>
</html>
EOT;
		echo $out;
	}
	function classtr($i)
	{
		$i++;
		if($i % 2){
			$output = ' class="row0" onmouseover="pointThis(this, \'#e2e2e2\');" onmouseout="pointThis(this, \'#f1f1f1\');"';
		}else{
			$output = ' class="row1" onmouseover="pointThis(this, \'#e2e2e2\');" onmouseout="pointThis(this, \'#ffffff\');"';
		}
		return $output;
	}
}
/*
$arrHeader = array('Nama','Umur');
$arrData[] = array('ogy', 12);
$arrData[] = array('sigit', 54);
$arrData[] = array('ogi sigit pornawan testing excel report', 54);

$excel = new phpRollExcel( $fileName="report.xls", $worksheetName="report", $arrHeader, $arrData );
$excel->setMaxColumnWidth(60);
$excel->setHeaderColor('yellow', 'black');
$excel->write();
*/