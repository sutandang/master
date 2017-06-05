<?php  if(!defined('_VALID_BBC')) exit('No direct script access allowed');

/*
EXAMPLE: membuat form List beserta form filter nya
$form = _lib('pea',  'table_name');
$form->initSearch();

$form->search->addInput('keyword','keyword');
$form->search->input->keyword->addSearchField('field_names_with_comma', $isFullText);

$add_sql = $form->search->action();
$keyword = $form->search->keyword();

echo $form->search->getForm();

#$form = _lib('pea',  'table_name');
$form->initRoll($add_sql);

#$form->roll->setLanguage();
$form->roll->setSaveTool(true);

$form->roll->addInput('title','sqlplaintext');
$form->roll->input->title->setTitle('Title');
#$form->roll->input->title->setLanguage();

$form->roll->action();
echo $form->roll->getForm();
*/
include_once(_PEA_ROOT . 'nav.inc.php');
class phpRollAdmin extends phpEasyAdminLib
{
	var $tableId;
	var $nav;			// untuk nyimpen obyek dari class oNav (navigasi prev next)
	var $isChangeBc = true;	// apakah roll berubah bgcolornya saat on hover
	var $onDelete;
	var $onDeleteArgs;

	//men set method pada form
	var $onDeleteLoadLast;
	var $onEachDeleteLoadLast;
	var $onSaveLoadLast;
	var $onEachSaveLoadLast;
	var $isActionExecute;
	var $setFailSaveMessage;

	function __construct($str_table, $str_sql_condition = '', $str_table_id='id', $arr_folder= array())
	{
		$this->initialize('roll', $str_table, $str_table_id, $str_sql_condition);

		$this->setSaveTool(true);
		$this->setResetTool(false);
		$this->setDeleteTool(true);

		$this->isActionExecute = true;
		$this->orderUrl        = '';
		$this->setNumRows();
	}

	function setNumRows($int_num_rows = 0)
	{
		$this->intNumRows	= (!$int_num_rows) ? config('rules', 'num_rows') : $int_num_rows;
	}

	function setActionExecute($bool=true, $msg='')
	{
		$this->isActionExecute = $bool;
		if (!empty($msg))
		{
			if ($bool)
			{
				$this->setSuccessSaveMessage($msg);
			}else{
				$this->setFailSaveMessage($msg);
			}
		}
	}

	function onDelete ($func_name_on_delete = '', $arr_on_delete_args = array(), $LoadLast = false)
	{
		$this->onDelete 	= $func_name_on_delete;
		$this->onDeleteArgs	= $arr_on_delete_args;
		$this->onDeleteLoadLast = $LoadLast;
	}
	function onEachDelete ($func_name_on_each_delete = '', $arr_on_each_delete_args = array(), $LoadLast = false)
	{
		$this->onEachDelete 	= $func_name_on_each_delete;
		$this->onEachDeleteArgs	= $arr_on_each_delete_args;
		$this->onEachDeleteLoadLast = $LoadLast;
	}

	function onSave ($func_name_on_save = '', $arr_on_save_args = array(), $LoadLast = false)
	{
		$this->onSave 	= $func_name_on_save;
		$this->onSaveArgs	= $arr_on_save_args;
		$this->onSaveLoadLast = $LoadLast;
	}
	function onEachSave ($func_name_on_each_save = '', $arr_on_each_save_args = array(), $LoadLast = false)
	{
		$this->onEachSave 	= $func_name_on_each_save;
		$this->onEachSaveArgs	= $arr_on_each_save_args;
		$this->onEachSaveLoadLast = $LoadLast;
	}

	function getDeletedId()
	{
		$arrDeletedId	= array();
		$checkName		= $this->formName . '_delete';
		$idName		= $this->formName . '_'. $this->tableId;
		if (isset($_POST[$checkName]))
		{
			foreach($_POST[$checkName] as $id=>$true)
			{
				$is_disable = false;
				if (!empty($this->disableInput['system_delete_tool']))
				{
					foreach ((array)$this->disableInput['system_delete_tool'] as $exec)
					{
						eval('if('.$_POST[$idName][$id].' '.$exec[0].' '.$exec[1].'){$is_disable=true;}');
						if ($is_disable)
						{
							break;
						}
					}
				}
				if (!$is_disable)
				{
					array_push($arrDeletedId, $_POST[$idName][$id]);
				}
			}
		}
		return $arrDeletedId;
	}

	function setIsChangeBc($bool_change_bc = false) {
		$this->isChangeBc = $bool_change_bc;
	}

	function setResetTool($bool_reset_tool = false)
	{
		if ($bool_reset_tool == 'on') $bool_reset_tool = true;
		elseif ($bool_reset_tool == 'off') $bool_reset_tool = false;
		$this->resetTool	= $bool_reset_tool;
	}

	// untuk membuat checkbox untuk checkAll
	// khusus untuk input bertipe checkAll dan checkboxdelete
	function getCheckAll($input)
	{
		$out = '';
		if (@$input->isCheckAll)
		{
			if ($input->type == 'checkbox' || $input->type == 'checkboxdelete')
			{
				link_js(_PEA_ROOT . 'includes/checkAll.js', false);
				$out .= '<input class="'. $input->name .'" type="checkbox" onClick="checkAll(this,\''. $this->formName .'\');"> ';
			}
		} // eof if ($input->isCheckAll)
		return $out;
	}

	function getOrderUrl($input, $title)
	{
		// menentukan field yang di jadikan variable _GET untuk orderby
		$objectName = '';
		switch ($input->type)
		{
			case 'multiinput':
			case 'dependentdropdown':
				if (!empty($input->elements))
				{
					foreach ($input->elements as $i => $element)
					{
						if ($element->isIncludedInSelectQuery)
						{
							$objectName = $element->objectName;
							break;
						}
					}
				}
				break;
			default:
				if ($input->isIncludedInSelectQuery)
				{
					$objectName = $input->objectName;
				}
				break;
		}
		if (empty($objectName))
		{
			return array('start' => '', 'end' => '');
		}
		// mencari basic Url nya
		if ($this->orderUrl	== '')
		{
			$this->orderUrl = preg_replace('#\?.*#', '', $_SERVER['REQUEST_URI']).'?';
			if (isset($_GET))
			{
				foreach($_GET as $name => $val)
				{
					if ($name != $this->formName.'_asc' && $name != $this->formName.'_order' && !empty($val))
					{
						$this->orderUrl	.= $name.'='.$val .'&';
					}
				}
			}
		}
		if (@$_GET[$this->formName.'_order'] == $objectName && @$_GET[$this->formName.'_asc'] == '0')
		{
			$img = 'desc';
			$href['start'] = '<a href="'.substr($this->orderUrl,0,-1).'" title="Reset order">';
		}else{
			$asc   = $this->formName.'_asc=1';
			$order = $this->formName.'_order='.$objectName;
			$img   = '';
			// mencari field mana yang mau di order by
			if (isset($_GET[$this->formName.'_order']))
			{
				if ($_GET[$this->formName.'_order'] == $objectName)
				{
					if (isset($_GET[$this->formName.'_asc']))
					{
						$asc = ($_GET[$this->formName.'_asc'] == '1') ? $this->formName.'_asc=0' : $this->formName.'_asc=1';
						$img = ($_GET[$this->formName.'_asc'] == '1') ? 'asc' : 'desc';
					}
				}
			}
			$href['start'] = '<a href="'.$this->orderUrl.$order.'&'.$asc.'" title="Sort by column '.$title.'" >';
		}
		$href['start'] .= !empty($img) ? icon('fa-sort-alpha-'.$img, $img).' ' : '';
		$href['end']    = '</a>';
		return $href;
	}

	function getOrderQuery($query)
	{
		if (isset($_GET[$this->formName . '_order']))
		{
			// hanya untuk validasi aja
			$_GET[$this->formName . '_asc']	= (isset($_GET[$this->formName . '_asc'])) ? $_GET[$this->formName . '_asc'] : '1';
			$_GET[$this->formName . '_asc']	= ($_GET[$this->formName . '_asc'] == '0' || $_GET[$this->formName . '_asc'] == '1') ? $_GET[$this->formName . '_asc'] : '1';
			$asc	= ($_GET[$this->formName . '_asc'] == '0') ? 'DESC' : 'ASC';
			$orderQuery	= 'ORDER BY '. $_GET[$this->formName . '_order'] . ' ' . $asc;

			if (preg_match('~order by ~is', $query))
				$query	= preg_replace("/order by.*?\$/is", $orderQuery, $query);
			else
				$query	.= ' '.$orderQuery;
		}
		return $query;
	}

	function addSystemInput()
	{
		// secara otomatis ditambah hidden input berupa tableID,
		// sebagai primari key dari tiap row dalam form tersebut
		$this->addInput('system_id', 'hidden', $setDefault=0);
		$this->input->system_id->setFormName($this->formName);
		$this->input->system_id->setFieldname($this->tableId);

		// tombol tool untuk delete otomatis
		if ($this->deleteTool)
		{
			$this->addInput('system_delete_tool', 'checkboxdelete');
			$this->input->system_delete_tool->setName('delete');
			$this->input->system_delete_tool->setTitle(' Delete');
		}
	}

	function getSaveSuccessPage()
	{
		if (isset($_POST[$this->saveButton->name]))
			return $this->getSuccessPage($this->setSuccessSaveMessage, $this->setFailSaveMessage);
		else return '';
	}

	function getDeleteSuccessPage()
	{
		if (isset($_POST[$this->deleteButton->name]))
			return $this->getSuccessPage($this->setSuccessDeleteMessage, $this->setFailDeleteMessage);
		else return '';
	}

	function getReport()
	{
		$out	= '';
		if ($this->isReportOn)
		{
			$out .= '<span class="input-group-addon">Export:';
			$this->arrReport	= get_object_vars($this->report);
			$link = _PEA_URL . 'report/phpReportGenerator.php?formName='. $this->formName .'&formType=roll&reportType=';
			foreach($this->arrReport as $report)
			{
				$report->setHeader($this->reportData['header']);
				$report->setData($this->reportData['data']);
				$t = $report->type=='html' ? 'text' : $report->type;
				$out .= ' <i class="fa fa-file-'.$t.'-o fa-lg" onClick="document.location.href=\''.$link
						 .	$report->type.'&'.@http_build_query((array)$report).'\'" style="cursor: pointer" title="Export to '.ucfirst($report->type).'"></i>';
			}
			$out .= '</span>';
		}
		return $out;
	}

	// getMainForm() mengembalikan form complete, tapi tanpa submit button, tanpa navigasi, tanpa header title
	function getMainForm()
	{
		$this->arrInput	= get_object_vars($this->input);
		// untuk menandai bahwa element yang dimasukkan ke multi, sebgai $isMulti=true
		$this->setMultiAll($this->arrInput);

		$out				= '<tbody>';
		$strField2Select	= array($this->tableId);

		//Buat query untuk select, buat ngambil data yang mau ditampilkan di input
		$strField2Lang = array();

		foreach($this->arrInput as $input)
		{
			if ($input->isMultiLanguage)
			{
				$strField2Lang[] = $input->fieldName; // gak perlu di cleanSQL krn untuk nama key array dan sudah di clean pas diquery
			}else
			if ($input->isIncludedInSelectQuery && $input->type != 'multiinput')
			{
				$strField2Select[] = $this->setQuoteSQL($input->fieldName);
			}
		}
		$strField2Select 	= implode(', ', $strField2Select);

		$query	= '';
		// query dimasukkan object oNav, biar sekalaian untuk navigasinya
		$this->nav	= new oNav($query, $this->tableId , $this->intNumRows, 10, 'page', $this->db);

		// ini untuk mendapatkan suatu query, berdasarkan order link
		$this->nav->sqlCondition	= $this->getOrderQuery($this->sqlCondition);

		$table	= $this->table .' '. $this->sqlCondition;
		$query	= "SELECT $strField2Select FROM $table";
		$query	= $this->getOrderQuery($query);

		$this->nav->completeQuery	= $query;

		// mendapatkan form-form nya row per row.
		// dan kemudian memasukkan value dari query database kedalam input form masing2 yang sesuai
		$i 		= 0;
		$arrData = array();
		while ($arrResult = $this->nav->fetch())
		{
			$this->arrResult = $arrResult;
			$tableId         = $this->arrResult[$this->tableId];
			$out            .= '<tr data-id="'.$tableId.'">';
			foreach($this->arrInput AS $input)
			{
				if (!$input->isInsideMultiInput && !$input->isHeader)
				{
					// digunakan pada sqllinks
					if (preg_match ('~ as ~is',$this->tableId))
					{
						if (preg_match('~(.*) (as) (.*)~is', $this->tableId, $match))
						{
							$this->tableId=$match[3];
						}
					}
					if ($this->isMultiLanguage && !isset($this->load_lang[$i]) && !empty($strField2Lang))
					{
						$q = "SELECT `lang_id`, `".implode('`, `', $strField2Lang)."` FROM `$this->LanguageTable` WHERE `$this->LanguageTableId`={$tableId}".$this->LanguageTableWhere;
						$this->load_lang[$i] = 1;
						$r = $this->db->getAll($q);
						foreach($r AS $d)
						{
							foreach($strField2Lang AS $f)
							{
								$arrResult[$f][$d['lang_id']] = $d[$f];
							}
						}
					}
					$arrResult[$input->objectName] = $this->getDefaultValue($input, $arrResult, $i);
					// dapatkan array data report
					if ($this->isReportOn && $input->isIncludedInReport) $arrData[$i][]	= $input->getReportOutput($arrResult[$input->objectName]);

					if ($input->isInsideRow)
					{
						$out	.= '<td>';
					}
					$tmp = $input->getOutput($arrResult[$input->objectName], $input->name.'['.$i.']', $this->setDefaultExtra($input));
					if (!empty($this->disableInput[$input->objectName]))
					{
						$is_disable = false;
						foreach ((array)$this->disableInput[$input->objectName] as $exec)
						{
							eval('if($exec[1] '.$exec[0].' $arrResult[\''.$exec[2].'\']){$is_disable=true;}');
							if ($is_disable)
							{
								break;
							}
						}
						if ($is_disable)
						{
							$tmp = preg_replace(array('~(<input\s?)~is', '~(<select\s?)~is', '~(<textarea\s?)~is'), '$1 disabled ', $tmp);
							if ($input->objectName != 'system_delete_tool')
							{
								$tmp.= $this->setDisableInputRecovery($arrResult[$input->objectName], $input->name.'['.$i.']');
							}
						}
					}
					if ($input->isInsideRow)
					{
						$out	.= $tmp.'</td>';
					}else{
						if (preg_match('~hidden~is', $tmp))
						{
							$out .= $tmp;
						}else{
							$out .= '<div class="hidden">'.$tmp.'</div>';
						}
					}
				}
			} // end foreach
			$out .= '</tr>';
			$i++;
		}
		$out .= '</tbody>';
		if ($this->isReportOn) $this->reportData['data'] = $arrData;
		return $out;
	} // eof function getMainForm()

	// getForm() adalah method utama
	// disini manggil action() dan getMainForm()
	// ini untuk ngambil form ROll Secara complete, beserta action2nya
	function getForm()
	{
		$this->action();
		$mainForm	= $this->getMainForm();
		if($this->isFormRequire)
		{
			$cls = ' class="formIsRequire"';
			link_js(_PEA_URL.'includes/formIsRequire.js', false);
		}else{
			$cls = '';
		}

		$i = 0;
		$out = '';

		$out .= '<form method="'.$this->methodForm.'" action="'.$this->actionUrl.'" name="'. $this->formName .'"'.$cls.' enctype="multipart/form-data" role="form">';
		$out .= $this->getSaveSuccessPage();
		$out .= $this->getDeleteSuccessPage();

		$hover= $this->isChangeBc ? ' table-hover' : '';
		$out .= '<table class="table table-striped table-bordered'.$hover.'">';
		$out .= '<thead><tr>';

		// ngambil tr title
		$numColumns = 0;

		foreach($this->arrInput as $input)
		{
			if ($input->isInsideRow && !$input->isInsideMultiInput && !$input->isHeader)
			{
				// buat array data untuk report
				if ($this->isReportOn && $input->isIncludedInReport)
				{
					$arrHeader[]	= $input->title;
				}
				// dapatkan text bantuan
				if (!empty($input->textHelp))
				{
					$this->help->value[$input->name] = $input->textHelp;
				}
				if (!empty($input->textTip))
				{
					$this->tip->value[$input->name] = $input->textTip;
				}
				$label = '';
				if (@$input->isCheckAll)
				{
					$label = $this->getCheckAll($input);
				}
				$href   = $this->getOrderUrl($input, $input->title);
				if (!empty($this->tip->value[$input->name]))
				{
					$input->title = tip($input->title, $this->tip->value[$input->name]);
				}
				$label .= $href['start'].$input->title.$href['end'];
				$out .= '  <th>'.$label;
				if (!empty($this->help->value[$input->name]))
				{
					$out .= ' <span style="font-weight: normal;">'.help($this->help->value[$input->name],'bottom').'</span>';
				}
				$out		.= "</th>\n";
				$numColumns++;
			}
		}
		$out .= '</tr></thead>';
		$this->reportData['header']	= isset($arrHeader) ? $arrHeader : array();

		// ini colspan untuk TR bagian button submit dan navigasi
		// jika ada save tool maka colspannya dikurangi satu, kl enggak kurangi 2
		$numBottomColumns	= 1;
		$colspan = 0;
		if ($this->saveTool) $numBottomColumns++;
		if ($this->deleteTool) $numBottomColumns++;

		// ambil mainFormnya
		$out .= $mainForm;

		// ambil tr untuk button-button dan navigasi prev next
		$foot    = '';
		$getNav  = $this->nav->getNav();
		$colspan = $numColumns - $numBottomColumns + 1;
		$tdsave  = false;

		if (!empty($getNav))
		{
			if ($numColumns >= $numBottomColumns)
			{
				$tdsave = true;
	      $foot  .= '<td colspan="'.$colspan.'">'.$getNav.'</td>';
			}else{
				$foot .= '<td>'.$getNav;
			}
		}else $tdsave = true;

		if ($this->saveTool)
		{
			if ($tdsave)
			{
				$c     = empty($getNav) ? ' colspan="'.($colspan+1).'"' : '';
				$foot .= '<td'.$c.'>';
			}
			$foot.= '<button type="submit" name="'. $this->saveButton->name .'" value="'. $this->saveButton->value
					.	'" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-'.$this->saveButton->icon.'"></span>'
					. $this->saveButton->label .'</button>'."\n";
			if ($this->resetTool)
			{
				$foot .= '<button type="reset" class="btn btn-warning btn-sm"><span class="glyphicon glyphicon-'.$this->resetButton->icon.'"></span>'.$this->resetButton->label.'</button> ';
			}
			if ($tdsave)
			{
				$foot .= '</td>';
			}
		}else{
			if (empty($getNav))
			{
				$foot  .= '<td colspan="'.$colspan.'"></td>';
			}
		}
		if (!empty($getNav))
		{
			if (!$tdsave)
			{
				$foot .= '</td>';
			}
		}
		if ($this->deleteTool)
		{
			$foot .= '  <td>'."\n";
			$foot .= '		<button type="submit" name="'.$this->deleteButton->name.'" value="'. $this->deleteButton->value.'" class="btn btn-danger btn-sm"';
			$foot .= ' onclick="if (confirm(\'Are you sure want to delete selected row(s) ?\')) { return true; }else{ return false; }">';
			$foot .=	'<span class="glyphicon glyphicon-'.$this->deleteButton->icon.'"></span>'.$this->deleteButton->label .'</button> ';
			$foot .= '  </td>'."\n";
		}
		if (!empty($foot)) {
			$out .= "<tfoot><tr>{$foot}</tr></tfoot>";
		}
		$out .= "\n".'</form>'."\n";

		$out .= '</table>';
		$showall  = $this->nav->getViewAllLink();
		if (!empty($showall)) {
			$showall = '<span class="input-group-addon">'.$showall.'</span>';
		}
		$showall .= $this->nav->getGoToForm(false);
    $out.= '<form method="get" action="" role="form" style="margin-top:-20px;margin-bottom: 20px;">'
    		.	'<div class="input-group">'.$this->getReport().'<span class="input-group-addon">'
    		. $this->nav->getStatus().'</span>'.$showall.'</div></form>';
		$formHeader = $this->getHeaderType();
		if (!empty($formHeader))
		{
			$out = <<<EOT
<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">{$formHeader}</h3>
	</div>
	<div class="panel-body">
		{$out}
	</div>
</div>
EOT;
		}
    $out = $this->getHideFormToolStart().$out.$this->getHideFormToolEnd();
		return $out;
	}

	function actionOnDelete()
	{
		if (!empty($this->onDelete))
		{
			$this->onDeleteArgs = !empty($this->onDeleteArgs) ? $this->onDeleteArgs : $this->getDeletedId();
			call_user_func($this->onDelete, $this->onDeleteArgs);
		}
	}
	function actionOnEachDelete($id)
	{
		if (!empty($this->onEachDelete))
		{
			call_user_func($this->onEachDelete, $id);
		}
	}
	function actionOnSave()
	{
		if (!empty($this->onSave))
		{
			call_user_func($this->onSave, $this->onSaveArgs);
		}
	}
	function actionOnEachSave($id)
	{
		if (!empty($this->onEachSave))
		{
			call_user_func($this->onEachSave,$id);
		}
	}
	// INI UNTUK MENGAMANKAN INPUT UNTUK JAGA2 JIKA DIUBAH OLEH USER
	function actionSecurity()
	{
		$nav = new oNav('SELECT '.$this->tableId.' FROM '.$this->table.' '.$this->sqlCondition, $this->tableId , $this->intNumRows, 10, 'page', $this->db);
		$q   = $this->getOrderQuery($nav->completeQuery);
		if (preg_match('~ order by ~is', $q))
		{
			$nav->int_cur_page = !empty($_GET[$nav->string_name]) ? $_GET[$nav->string_name] : 1;
			$nav->cur_sql_pos  = ($nav->int_cur_page-1)*$nav->int_max_rows;

			$q .= ' LIMIT '.$nav->cur_sql_pos.', '.$nav->int_max_rows;
			$r  = $this->db->getCol($q);

			foreach ($r as $i => $d)
			{
				$_POST[$this->input->system_id->name][$i] = $d;
			}
		}
	}
	function action()
	{
		if ($this->isLoaded->action) return false;
		else $this->isLoaded->action = true;

		//menambah input hidden id dan delete tool
		$this->addSystemInput();

		if (empty($this->arrInput)) $this->arrInput	= get_object_vars($this->input);
		// untuk menandai apakah form perlu validasi
		$this->setIsFormRequire();

		if ($this->isActionExecute)
		{
			if (!isset($_POST[$this->tableId])) $_POST[$this->tableId] = array();

			// aksi yang dilakukan saat delete button di klik
			if (isset($_POST[$this->deleteButton->name]))
			{
				$this->actionSecurity();
				if (!$this->onDeleteLoadLast)	$this->actionOnDelete();
				if ($this->isActionExecute)
				{
					$ok = false;
					if (!empty($_POST[$this->formName.'_delete']))
					{
						$del_ids = $this->getDeletedId();
						ids($del_ids);
						if (!empty($del_ids))
						{
							$orderby  = '';
							/* CARI APAKAH ADA FIELD YANG PERLU DITANGANI SEBELUM DIHAPUS */
							foreach ($this->arrInput as $key => $input)
							{
								if ($input->isIncludedInDeleteQuery)
								{
									$q = $input->getDeleteSQL($del_ids); // untuk multifile, file, tags akan dihapus serta
									if (!empty($q))
									{
										$this->db->Execute($q);
									}
								}
								if ($input->type=='orderby')
								{
									$orderby = $input->objectName;
								}
							}
							$table = preg_replace('~((?:\s+as\s+.*?)?\s+left\s+join\s+.*?)$~is','',$this->table);
							$q = "DELETE FROM {$table} WHERE {$this->tableId} IN ({$del_ids})";
							$ok= $this->db->Execute($q);
							if ($ok)
							{
								if ($this->isMultiLanguage)
								{
									$q = "DELETE FROM ".$this->LanguageTable." WHERE `".$this->LanguageTableId."` IN (". $del_ids .")".$this->LanguageTableWhere;
									$this->db->Execute($q);
								}
								/* HAPUS SEMUA TABLE RELASI JIKA ADA YANG TER RELASI */
								foreach ($this->arrInput as $input)
								{
									if (!$input->isIncludedInDeleteQuery)
									{
										$q = $input->getDeleteQuery($del_ids);
										if (!empty($q))
										{
											$this->db->Execute($q);
										}
									}
								}
								/* URUTKAN KEMBALI PENGURUTAN JIKA DITEMUKAN INPUT ORDERBY */
								if (!empty($orderby))
								{
									$ord = ' ORDER BY '.$orderby.' ASC';
									$sql = $this->sqlCondition;
									if (!preg_match('~ order by ~is', $this->sqlCondition))
									{
										$sql.= $ord;
									}else{
										$sql = preg_replace('~(order by .*?)$~is', $ord, $sql);
									}
									$q = "SELECT {$this->tableId}, {$orderby} FROM {$this->table} {$sql}";
									$r = $this->db->getAll($q);
									$i = 0;
									foreach ($r as $dt)
									{
										$i++;
										if ($dt[$orderby]!=$i)
										{
											$q = "UPDATE {$this->table} SET `{$orderby}`=$i WHERE `{$this->tableId}`=".$dt[$this->tableId];
											$this->db->Execute($q);
										}
									}
								}
								if ($this->onDeleteLoadLast)	$this->actionOnDelete();
								if (!empty ($this->onEachDelete))
								{
									$check_name= $this->formName . "_delete";
									foreach($_POST[$check_name] as $i => $id)
									{
										if (!$this->onEachDeleteLoadLast)	$this->actionOnEachDelete($record_id);
										$record_id=$_POST[$this->input->system_id->name][$i];
										if ($this->onEachDeleteLoadLast)	$this->actionOnEachDelete($record_id);
									}
								}
							}
						}
					}
					$this->debug($ok, "", "BBC", "Class phpEasyAdmin query error on rollAction method(DELETE), please check your arguments when initiate phpEasyAdmin Class : ".mysqli_error($this->db->link));
				}
			}else
			if (isset($_POST[$this->saveButton->name])
				|| isset($_POST[$this->formName.'_orderby'])
				|| isset($_POST[$this->formName.'_file_delete_image']))
			{
				$formExecute = true;
				if ($this->isFormRequire && isset($_POST[$this->saveButton->name]))
				{
					foreach((array)$_POST[$this->input->system_id->name] as $i => $id)
					{
						foreach ($this->arrInput as $input)
						{
							if ($input->isRequire)
							{
								if($input->type=='file')
								{
									$text = @is_uploaded_file($_FILES[$input->name]['tmp_name'][$i]) ? '1' : @$_POST[$input->name][$i];
								}else{
									$text = $input->isMultiLanguage ? @current($_POST[$input->name][$i]) : @$_POST[$input->name][$i];
								}
								$req   = explode(' ', $input->isRequire);
								$i_row = strtoupper($input->title).' in line: '.money($i + 1);

								if (empty($text) && $text != '0')
								{
									if (empty($req[1]) || $req[1]=='true')
									{
										$this->setFailSaveMessage('"'.$i_row.'" must not empty!');
										$formExecute = false;
									}
								}else{
									switch ($req[0]) {
										case 'email':
											if (!is_email($text)) {
												$this->setFailSaveMessage('Please enter a valid email address in "'.$i_row.'"!');
												$formExecute = false;
											}
											break;
										case 'url':
											if (!is_url($text)) {
												$this->setFailSaveMessage('Please enter a valid URL in "'.$i_row.'"!');
												$formExecute = false;
											}
											break;
										case 'phone':
											if (!is_phone($text)) {
												$this->setFailSaveMessage('Please enter a valid phone number in "'.$i_row.'"!');
												$formExecute = false;
											}
											break;
										case 'money':
											if (!preg_match('~^[0-9]+(?:\.[0-9]+)?$~s', $text)) {
												$this->setFailSaveMessage('Please enter a valid money format in "'.$i_row.'"!');
												$formExecute = false;
											}
											break;
										case 'number':
											if (!preg_match('~^[0-9]+$~s', $text)) {
												$this->setFailSaveMessage('Please enter a valid number in "'.$i_row.'"!');
												$formExecute = false;
											}
											break;
									}
								}
							}
							if (!$formExecute)
							{
								$this->isActionExecute = $formExecute;
								$this->error           = true;
								break;
							}
						} // eo foreach ($this->arrInput as $input)
						if (!$formExecute)
						{
							break;
						}
					} // eo foreach((array)$_POST[$this->input->system_id->name] as $i => $id)
				} // eo if ($this->isFormRequire)
				if ($this->isActionExecute)
				{
					if (!empty($_POST[$this->input->system_id->name]))
					{
						// amankan input ID sebelum lakukan eksekusi
						$this->actionSecurity();
						// Jika onSave di eksekusi SEBELUM form action di proses
						if (!$this->onSaveLoadLast)
						{
							$this->actionOnSave();
						}
						if ($this->isActionExecute)
						{
							foreach((array)$_POST[$this->input->system_id->name] as $i => $id)
							{
								if (!$this->onEachSaveLoadLast)
								{
									$this->actionOnEachSave($id);
								}
								$lang_text = array();
								$query = "UPDATE ". $this->table ." SET ";
								foreach ($this->arrInput as $input)
								{
									if ($input->isMultiLanguage)
									{
										$last = '';
										if (!empty($_POST[$input->name][$i]))
										{
											foreach((array)$_POST[$input->name][$i] AS $l => $p)
											{
												$t = $p ? $p : $last;
												if (@$input->nl2br) $t = nl2br($t);
												$lang_text[$l][$input->objectName] = $this->cleanSQL($t);;
												$last = $t;
											}
										}
									}else{
										$query .= $input->getRollUpdateQuery($i);
									}
									$this->setSuccessSaveMessage .= $input->status;
								}
								//menambahkan yang additional field dan valuenya
								foreach ($this->extraField->field as $i => $f)
								{
									$query .= '`'.$f .'`=\''.$this->extraField->value[$i].'\', ';
								}
								$query = $this->replaceTrailingComma($query) ." WHERE ". $this->tableId ." = '". $id ."' ";

								$this->error	= !$this->db->Execute($query);
								if (!$this->error && $this->isMultiLanguage && count($lang_text) > 0)
								{
									$q = "SELECT `lang_id` FROM `{$this->LanguageTable}` WHERE `{$this->LanguageTableId}`={$id}{$this->LanguageTableWhere}";
									$r_lang_id = $this->db->getCol($q);
									foreach($lang_text AS $lang_id => $value)
									{
										$field = array();
										foreach($value AS $f => $v)
										{
											$field[] = "`{$f}`='{$v}'";
										}
										if (!empty($field))
										{
											$fields = implode(', ', $field);
											if (in_array($lang_id, $r_lang_id))
											{
												$q = "UPDATE `{$this->LanguageTable}` SET {$fields} WHERE `lang_id`={$lang_id} AND `{$this->LanguageTableId}`={$id}{$this->LanguageTableWhere}";
											}else{
												foreach ($this->LanguageTableUpdate as $var => $val)
												{
													$field[] = "`{$var}`='{$val}'";
												}
												$fields = implode(', ', $field);
												$q = "INSERT INTO `{$this->LanguageTable}` SET `lang_id`={$lang_id}, `{$this->LanguageTableId}`={$id}, {$fields}";
											}
										}else{
											$q = '';
										}
										$this->db->Execute($q);
									}
								}
								if ($this->onEachSaveLoadLast)
								{
									$this->actionOnEachSave($id);
								}
								if ($this->error)
								{
									$this->errorMsg	= $this->db->ErrorMsg();
								}
							} // eo foreach((array)$_POST[$this->input->system_id->name] as $i => $id)
							// Jika onSave di eksekusi SETELAH form action di proses
							if ($this->onSaveLoadLast)
							{
								$this->actionOnSave();
							}
						} // eo if ($this->isActionExecute)
					} // eo if (!empty($_POST[$this->input->system_id->name]))
				} // eo if ($formExecute)
			} // eo elseif (isset($_POST[$this->saveButton->name]) || isset($_POST[$this->formName.'_file_delete_image']))
		} // eo if ($this->isActionExecute)
	} // eo action() method
} // eo class