<?php

require_once dirname(dirname(__FILE__)).'/config.php';
include_once _PEA_ROOT.'report/phpReport.php';
include_once _PEA_ROOT.'includes/write_excel/Worksheet.php';
include_once _PEA_ROOT.'includes/write_excel/Workbook.php';


/**
* Class for generate simple excel file, just send array data and array title
* and it class will create an excel file with your array data
* example :
*   $arrData[] = array('Data Siswa');
*   $arrData[] = array('Nama', 'Ogi Sigit Pornawan');
*   $arrData[] = array('Umur', 54);
*   $arrData[] = array('Tgl Lahir', '2002-09-23');
*
*   $excel = new phpDetailExcel( $fileName="report.xls", $worksheetName="report", $arrData );
*   $excel->setMaxColumnWidth(60);
*   $excel->setHeaderColor('yellow', 'black');
*   $excel->write();
*
* @author Ogi Sigit Pornawan <ogi.sigit.p@idwebhost.com>
* @package phpDetailExcel
*/
class phpEditExcel extends phpReport
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
	*   $excel = new phpDetailExcel( $fileName="report.xls", $worksheetName="report", $arrData );
	*
    * @access public
    * @param string	$fileName		Nama file excel hasil generate
    * @param string	$worksheetName	Nama worksheet dari Excel
	* @param array	$arrHeader		array header, yang nantinya jadi title di excelnya
    */
	function __construct( $fileName='excelReport.xls', $worksheetName='Excel Report', $arrData = array() )
	{
		$tgl	= date("Y-m-d");

		if ( $fileName == '' )		$fileName = "excelReport". $tgl .".xls";
		if ( $worksheetName == '' )	$worksheetName = "Excel Report ". $tgl;

		$this->type				= 'excel';
		$this->fileName			= $fileName;
		$this->worksheetName	= $worksheetName;
		$this->arrData			= $arrData;
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
		$workbook = new Workbook("-");

		// dapatkan format untuk cell biasa
		$cellFormat	=& $workbook->add_format();
		$cellFormat->set_align( "top" );
		$cellFormat->set_text_wrap();
		$cellFormat->set_border(1);

		// dapatkan format untuk header
		$headerFormat	=& $workbook->add_format();
		$headerFormat->set_color( $this->headerColor['font'] );
		$headerFormat->set_align( "top" );
		$headerFormat->set_pattern();
		$headerFormat->set_fg_color( $this->headerColor['bg'] );
		$headerFormat->set_border(1);
		$headerFormat->set_text_wrap();
		$headerFormat->set_bold();

		// dapatkan format untuk cell kiri
		$leftFormat	=& $workbook->add_format();
		$leftFormat->set_align( "top" );
		$leftFormat->set_border(1);
		$leftFormat->set_text_wrap();
		$leftFormat->set_bold();

		// Creating the first worksheet
		$worksheet1 =& $workbook->add_worksheet( $this->worksheetName );

		// set lebar colom
		$i = 0;
		foreach( $arrColWidth as $width )
		{
			$worksheet1->set_column( $i, $i, $width );
			$i++;
		}

		$lastRow	= 0;

		// buat data
		if ( !empty( $this->arrData ) )
		{
			$totRow = count( $this->arrData );
			foreach( $this->arrData as $dataRow )
			{
				$i = 0;
				// jika jumlah colom lebih dari satu, maka dianggap sebagai header
				if ( count( $dataRow ) > 1 )
				{
					foreach( $dataRow as $data )
					{
						if ( $i < 1 )
						{
							// agar yang kiri punya style sendiri
							$worksheet1->write( $lastRow, $i, $data, $leftFormat );
						}
						else
						{
							$worksheet1->write( $lastRow, $i, $data, $cellFormat );
						}
						$i++;
					}
				}
				else
				{
					foreach( $dataRow as $data )
					{
						$worksheet1->write( $lastRow, 0, $data, $headerFormat );
						$worksheet1->write( $lastRow, 1, '', $headerFormat );
						$worksheet1->merge_cells ( $lastRow, 0, $lastRow, 1 );
					}
				}
				$lastRow++;
			}
		}

		$workbook->close();

	} // eof writeExcel()

} // eof class phpMyExcel

/*
$arrData[] = array('Data Siswa');
$arrData[] = array('Nama', 'Ogi Sigit Pornawan');
$arrData[] = array('Umur', 54);
$arrData[] = array('Tgl Lahir', '2002-09-23');

$excel = new phpDetailExcel( $fileName="report.xls", $worksheetName="report", $arrData );
$excel->setMaxColumnWidth(60);
$excel->setHeaderColor('yellow', 'black');
$excel->write();
*/
?>