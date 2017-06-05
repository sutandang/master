<?php

require_once dirname(dirname(__FILE__)).'/config.php';
include_once _PEA_ROOT.'report/phpReport.php';
include_once _PEA_ROOT.'includes/write_excel/Worksheet.php';
include_once _PEA_ROOT.'includes/write_excel/Workbook.php';

/**
* Class for generate simple excel file, just send array data and array title
* and it class will create an excel file with your array data
* example :
*    $excel = new phpRollExcel( $fileName="report.xls", $worksheetName="report", $arrHeader, $arrData );
*    $excel->setMaxColumnWidth(60);
*    $excel->setHeaderColor('yellow', 'black');
*    $excel->write();
*
* @author Ogi Sigit Pornawan <ogi.sigit.p@idwebhost.com>
* @package phpRollExcel
*/
class phpRollExcel extends phpReport
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
    * @param string	$fileName		Nama file excel hasil generate
    * @param string	$worksheetName	Nama worksheet dari Excel
	* @param array	$arrHeader		array header, yang nantinya jadi title di excelnya
	* @param array	$arrHeader		array header, yang nantinya jadi title di excelnya
    */
	function __construct( $fileName='', $worksheetName='', $arrHeader=array(), $arrData = array() )
	{
		$tgl	= date("Y-m-d");

		if ( $fileName == '' )		$fileName = "excelReport". $tgl .".xls";
		if ( $worksheetName == '' )	$worksheetName = "Excel Report ". $tgl;

		$this->type          = "excel";
		$this->fileName      = $fileName;
		$this->worksheetName = $worksheetName;
		$this->arrHeader     = $arrHeader;
		$this->arrData       = $arrData;
		$this->setMaxColumnWidth();
		$this->setHeaderColor();
	}


	function headeringExcel( $filename )
	{
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=$filename" );
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
		header("Pragma: public");
	}

	function write()
	{
		// HTTP headers
		$this->headeringExcel( $this->fileName );

		// dapatkan lebar kolom, diambil dari data terpanjang
		$arrColWidth	= $this->getColumnWidth();

		// Creating a workbook
		$workbook = new Workbook("");

		// dapatkan format untuk cell biasa
		$cellFormat	=& $workbook->add_format();
		$cellFormat->set_align( "top" );
		$cellFormat->set_text_wrap();
		$cellFormat->set_right(1);
		$cellFormat->set_left(1);

		// dapatkan format untuk cell bottom
		$bottomFormat	=& $workbook->add_format();
		$bottomFormat->set_align( "top" );
		$bottomFormat->set_text_wrap();
		$bottomFormat->set_right(1);
		$bottomFormat->set_left(1);
		$bottomFormat->set_bottom(1);

		// Creating the first worksheet
		$worksheet1 =& $workbook->add_worksheet( $this->worksheetName );

		// dapatkan format untuk header
		if ( !empty( $this->arrHeader ) )
		{
			$headerFormat	=& $workbook->add_format();
			$headerFormat->set_align( "top" );
			$headerFormat->set_color( $this->headerColor['font'] );
			$headerFormat->set_pattern();
			$headerFormat->set_fg_color( $this->headerColor['bg'] );
			$headerFormat->set_border(1);
			$headerFormat->set_text_wrap();
			$headerFormat->set_bold();
		}

		// set lebar colom
		$i = 0;
		foreach( $arrColWidth as $width )
		{
			$worksheet1->set_column( $i, $i, $width );
			$i++;
		}

		$lastRow	= 0;

		// buat header
		if ( !empty( $this->arrHeader ) )
		{
			$i = 0;
			foreach( $this->arrHeader as $header )
			{
				$worksheet1->write_string( $lastRow, $i, $header, $headerFormat );
				$i++;
			}
			$lastRow++;
		}

		// buat data
		if ( !empty( $this->arrData ) )
		{
			$totRow = count( $this->arrData );
			foreach( $this->arrData as $dataRow )
			{
				$i = 0;
				foreach( $dataRow as $data )
				{
					$format  = ($lastRow == $totRow) ? $bottomFormat : $cellFormat;
					$worksheet1->write( $lastRow, $i, $data, $format );
					$i++;
				}
				$lastRow++;
			}
		}
		$workbook->close();
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