<?php

require_once dirname(dirname(__FILE__)).'/config.php';
include_once _PEA_ROOT.'report/phpReport.php';

class phpEditHtml extends phpReport
{
	var $fileName;
	var $worksheetName;
	var $arrData;
	var $maxColumnWidth; 	// maximum column width,
	var $headerColor;

    /**
    * Konstruktor: Inisialisasi
	* example:
	*   $arrData[] = array('Data Siswa');
	*   $arrData[] = array('Nama', 'Ogi Sigit Pornawan');
	*   $arrData[] = array('Umur', 54);
	*   $arrData[] = array('Tgl Lahir', '2002-09-23');
	*
	*   $excel = new phpEditHtml( $fileName="report.html", $worksheetName="report", $arrData );
	*
    * @access public
    * @param string	$fileName		Nama file html hasil generate
    * @param string	$worksheetName	Nama worksheet dari Html
	* @param array	$arrHeader		array header, yang nantinya jadi title di excelnya
    */
	function __construct( $fileName='htmlReport.html', $worksheetName='Html Report', $arrData = array() )
	{
		$tgl	= date("Y-m-d");

		if ( $fileName == '' )		$fileName = "htmlReport". $tgl .".html";
		if ( $worksheetName == '' )	$worksheetName = "Html Report ". $tgl;

		$this->type				= 'html';
		$this->fileName			= $fileName;
		$this->worksheetName	= $worksheetName;
		$this->arrData			= $arrData;
		$this->setMaxColumnWidth();
		$this->setHeaderColor();
	}

	function write()
	{
		// buat data
		$out = '<tbody>';
		if ( !empty( $this->arrData ) )
		{
			foreach( $this->arrData as $dataRow )
			{
				// jika jumlah colom lebih dari satu, maka dianggap sebagai bukan header
				if ( count( $dataRow ) > 1 )
				{
					$out	.= '<tr>';
					foreach( $dataRow as $data )
					{
						$data = str_replace('src="images/', 'src="'._URL.'images/', $data);
						$out	.= '<td>'.$data.'</td>';
					}
					$out	.= '</tr>';
				}
				else
				{
					foreach( $dataRow as $data )
					{
						$out	.= '<tr>';
					  $out	.= '<td colspan=2><strong>'.$data.'</strong></td>';
						$out	.= '</tr>';
					}
				}
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
	} // eof writeExcel()
	function classtr(&$i)
	{
		$i++;
		if($i % 2){
			$output = ' bgcolor="#f1f1f1" class="row0"';
		}else{
			$output = ' bgcolor="#ffffff" class="row1"';
		}
		return $output;
	}
} // eof class phpEditHtml

/*
$arrData[] = array('Data Siswa');
$arrData[] = array('Nama', 'Ogi Sigit Pornawan');
$arrData[] = array('Umur', 54);
$arrData[] = array('Tgl Lahir', '2002-09-23');

$excel = new phpEditHtml( $fileName="report.html", $worksheetName="report", $arrData );
$excel->setMaxColumnWidth(60);
$excel->setHeaderColor('yellow', 'black');
$excel->write();
*/
?>