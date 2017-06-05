<?php  if ( ! defined('_VALID_BBC')) exit('No direct script access allowed');

/*
EXAMPLE: membuat form Add/Edit
$form = _lib('pea',  'table_name');
$form->initEdit(!empty($_GET['id']) ? 'WHERE id='.$_GET['id'] : '');
#$form->edit->setLanguage();
#$form->edit->setColumn(2);

$form->edit->addInput('header','header');
$form->edit->input->header->setTitle(!empty($_GET['id']) ? 'Edit Data' : 'Add Data');

$form->edit->addInput('name', 'text', $noColumn=1);
$form->edit->input->name->setTitle('Nama');
#$form->edit->input->name->setRequire($require='any' / * any/email/url/phone/money/number * /, $is_mandatory=1);
#$form->edit->input->name->setLanguage();

$form->edit->addInput('email', 'text', $noColumn=2);
$form->edit->input->email->setTitle('Email Address');
#$form->edit->input->email->setRequire($require='email' / * any/email/url/phone/money/number * /, $is_mandatory=1);
#$form->edit->input->email->setLanguage();

$form->edit->action();
echo $form->edit->getForm();
*/
class phpAddAdmin extends phpEasyAdminLib
{
	var $onInsert;
	var $onInsertArgs;
	var $pendingQuery = array();
	var $insertId     = 0;

	//untuk menset message berhasil yang akan keluar
	var $setSuccessSaveMessage	="Success add new data.";
	var $setFailSaveMessage		="Failed add new data.";

	function __construct( $str_table, $str_table_id='id')
	{
		$this->initialize('add', $str_table, $str_table_id);

		$this->setSaveTool(true);
		$this->setResetTool(true);
		$this->setDeleteTool(false);

		$this->setSaveButton('submit_add', 'ADD' );
	}

	function onInsert ( $func_name_on_insert = '', $arr_on_insert_args = array() )
	{
		$this->onInsert		= $func_name_on_insert;
		$this->onInsertArgs	= $arr_on_insert_args;
	}

	function onSave ( $func_name_on_insert = '', $arr_on_insert_args = array() )
	{
		$this->onInsert		= $func_name_on_insert;
		$this->onInsertArgs	= $arr_on_insert_args;
	}

	function getInsertId()
	{
		return $this->insertId;
	}

	function getAddSuccessPage()
	{
		if (isset($_POST[$this->saveButton->name]) )
			return $this->getSuccessPage( $this->setSuccessSaveMessage, $this->setFailSaveMessage);
		else return '';
	}

	// getMainForm() mengembalikan form complete, tapi tanpa submit button, tanpa header title
	function getMainForm()
	{
		$this->arrInput	= get_object_vars( $this->input );

		// untuk menandai bahwa element yang dimasukkan ke multi, sebgai $isMulti=true
		$this->setMultiAll( $this->arrInput );

		$output  = '';
		$column  = array();
		// mendapatkan form-form nya row per row.
		foreach( $this->arrInput as $input )
		{
			if (!$input->isInsideMultiInput && !$input->isHeader)
			{
				$out = '';
				$defaultValue = $this->getDefaultValue($input);
				$inputField   = $input->getOutput( $defaultValue, $input->name, $this->setDefaultExtra($input));
				if ( $input->isInsideRow && $input->isInsideCell )
				{
					// dapatkan text bantuan
					if (!empty($input->textHelp))
					{
						$this->help->value[$input->name] = $input->textHelp;
					}
					if (!empty($input->textTip))
					{
						$this->tip->value[$input->name] = $input->textTip;
					}
					$out .= '<div class="form-group">';
					if (!($input->type=='multiinput'&&$input->isToogle))
					{
						$title = ucwords($input->title);
						if(!empty ( $this->help->value[$input->name] ))
						{
							$title .= ' '.help('<span style="font-weight: normal;">'.$this->help->value[$input->name].'</span>');
						}
						$out .= '<label>'.$title.'</label>';
					}
					$out .= $inputField;
					if(!empty($this->tip->value[$input->name]))
					{
						$out .= '<p class="help-block">'.$this->tip->value[$input->name].'</p>';
					}
					$out	.= '</div>';
				} // eof if ( $input->isInsideRow && $input->isInsideCell )
				else $out .= $inputField;
				if ($this->columnNumber > 1)
				{
					if (!empty($out))
					{
						if (empty($column[$input->noColumn]))
						{
							$column[$input->noColumn] = array();
						}
						$column[$input->noColumn][] = $out;
					}
				}else{
					$output .= $out;
				}
			} // eof if (!$input->isInsideMultiInput && !$input->isHeader)
		} // eof foreach( $this->arrInput as $input )
		if ($this->columnNumber > 1)
		{
			$output = '<div class="clearfix"></div>';
			$j = 12 / $this->columnNumber;
			for ($i=1; $i <= $this->columnNumber; $i++)
			{
				if (empty($column[$i]))
				{
					$column[$i] = array();
				}
				$output .= '<div class="col-md-'.$j.' col-sm-'.$j.'">'.implode('', $column[$i]).'</div>';
			}
			$output .= '<div class="clearfix"></div>';
		}
		return $output;
	} // end getMainForm()

	// getForm() adalah method utama
	// disini manggil action() dan getMainForm()
	// ini untuk ngambil form Form Secara complete, beserta action2nya
	function getForm()
	{
		$this->action();
		$out = $this->getAddSuccessPage();
		$out.= $this->getMainForm();
		$formHeader = $this->getHeaderType();
		if (!empty($formHeader))
		{
			$formHeader = '<div class="panel-heading"><h3 class="panel-title">'.$formHeader.'</h3></div>';
		}
		$footer = '';
		if (!empty($_GET['return']))
		{
			if (!empty($this->hideToolTitle))
			{
				$GLOBALS['sys']->nav_add($this->hideToolTitle);
			}
			$footer =	'<span type="button" class="btn btn-default btn-sm" onclick="document.location.href=\''
					 				.	$_GET['return'].'\';"><span class="glyphicon glyphicon-chevron-left"></span></span> ';
		}
		if	($this->saveTool)
		{
			$footer .= '<button type="submit" name="'.$this->saveButton->name.'" value="'.$this->saveButton->value;
			$footer .= '" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-'.$this->saveButton->icon.'"></span>';
			$footer .= $this->saveButton->label .'</button> ';
		}
		if ($this->resetTool)
		{
			$footer .= '<button type="reset" class="btn btn-warning btn-sm"><span class="glyphicon glyphicon-'.$this->resetButton->icon.'"></span>'.$this->resetButton->label.'</button> ';
		}
		if($this->isFormRequire)
		{
			$cls = ' class="formIsRequire"';
			link_js(_PEA_URL.'includes/formIsRequire.js', false);
		}else{
			$cls = '';
		}
		$out = <<<EOT
<form method="{$this->methodForm}" action="{$this->actionUrl}" name="{$this->formName}"{$cls} enctype="multipart/form-data" role="form">
	<div class="panel panel-default">
		{$formHeader}
		<div class="panel-body">{$out}</div>
		<div class="panel-footer">
			{$footer}
		</div>
	</div>
</form>
EOT;
		return $this->getHideFormToolStart().$out.$this->getHideFormToolEnd();
	}

	function actionOnInsert()
	{
		if ( !empty( $this->onInsert ) )
		{
			if(empty($this->onInsertArgs)) $this->onInsertArgs = $this->getInsertId();
			call_user_func( $this->onInsert, $this->onInsertArgs );
		}
	}

	function executePendingQuery()
	{
		foreach( $this->pendingQuery as $arrPending )
		{
			if ( !empty( $arrPending ) )
			foreach( $arrPending as $sql )
			{
				$sql = str_replace( '_INSERT_ID', $this->insertId, $sql );
				$sql = str_replace( '_PENDING_QUERY', '', $sql );
				if ( !empty($sql) )
				{
					$this->db->Execute( $sql );
				}
			}
		}
	}

	// aksi jika button submit di click
	function action()
	{
		// action dipanggil jika blom pernah dipanggil
		if ( !$this->isLoaded->action )
		{
			$this->isLoaded->action	= true;
			// $this->arrInput diambil jika memang blom ada
			if ( empty($this->arrInput) ) $this->arrInput	= get_object_vars( $this->input );
			// untuk menandai apakah form perlu validasi
			$this->setIsFormRequire();

			// dapatkan nama input pertama yang dikirim melalui post
			$arrKey = array_keys( $this->arrInput );
			$jmlKey = count( $arrKey );
			$search = true;
			$i      = 0;
			while ( $search && $i < $jmlKey )
			{
				if ( $this->arrInput[$arrKey[$i]]->isIncludedInUpdateQuery )
				{
					$firstName	= $this->arrInput[$arrKey[$i]]->name;
					$search	= false;
				}
				else
					$i++;
			}

			$into	= $values = '';
			$lang_text = array();
			// jika submit diklik atau first inputnya isset, do something
			if ( isset( $_POST[$this->saveButton->name] ) )
			{
				$formExecute = true;
				if ($this->isFormRequire)
				{
					foreach ($this->arrInput as $input)
					{
						if ($input->isRequire)
						{
							if($input->type=='file')
							{
								$text = @is_uploaded_file($_FILES[$input->name]['tmp_name']) ? '1' : '';
							}else{
								$text = $input->isMultiLanguage ? @current($_POST[$input->name]) : @$_POST[$input->name];
							}
							$req  = explode(' ', $input->isRequire);
							if (empty($text) && $text != '0')
							{
								if (empty($req[1]) || $req[1]=='true')
								{
									$this->setFailSaveMessage('"'.$input->title.'" must not empty!');
									$formExecute = false;
								}
							}else{
								switch ($req[0]) {
									case 'email':
										if (!is_email($text)) {
											$this->setFailSaveMessage('Please enter a valid email address in "'.$input->title.'"!');
											$formExecute = false;
										}
										break;
									case 'url':
										if (!is_url($text)) {
											$this->setFailSaveMessage('Please enter a valid URL in "'.$input->title.'"!');
											$formExecute = false;
										}
										break;
									case 'phone':
										if (!is_phone($text)) {
											$this->setFailSaveMessage('Please enter a valid phone number in "'.$input->title.'"!');
											$formExecute = false;
										}
										break;
									case 'money':
										if (!preg_match('~^[0-9]+(?:\.[0-9]+)?$~s', $text)) {
											$this->setFailSaveMessage('Please enter a valid money format in "'.$input->title.'"!');
											$formExecute = false;
										}
										break;
									case 'number':
										if (!preg_match('~^[0-9]+$~s', $text)) {
											$this->setFailSaveMessage('Please enter a valid number in "'.$input->title.'"!');
											$formExecute = false;
										}
										break;
								}
							}
						}
						if (!$formExecute)
						{
							$this->error = true;
							break;
						}
					}
				}
				if ($formExecute)
				{
					foreach ($this->arrInput as $input)
					{
						if($input->isMultiLanguage)
						{
							$last = '';
							foreach((array)$_POST[$input->name] AS $i => $v)
							{
								$t = $v ? $v : $last;
								if(@$input->nl2br)
								{
									$t = nl2br($t);
								}
								$lang_text[$i][$input->objectName] = $this->cleanSQL($t);
								$last = $t;
							}
						}else
						if ( $input->type != 'multiinput' )
						{
							// ini dibuat supaya bisa modular tiap element, sehingga untuk query manggil metode dari class element
							$arrValuesInto		= $input->getAddQuery();
							// ini kalau return nya membutuhkan eksekusi sql setelah dilakukan insert
							// biasanya agar bisa mendapatkan insert id nya dulu
							if ( isset( $arrValuesInto[0] ) && $arrValuesInto[0] == '_PENDING_QUERY' )
							{
								$this->pendingQuery[]	= $arrValuesInto;
							}else{
								if (!empty($arrValuesInto['into']) && !empty($arrValuesInto['value']))
								{
									$into   .= @$arrValuesInto['into'];
									$values .= @$arrValuesInto['value'];
								}
							}
						}
					}
					//menambahkan yang additional field dan valuenya
					foreach ( $this->extraField->field as $id => $field )
					{
						$into   .= $this->setQuoteSQL($field).', ';
						$values .= "'". $this->extraField->value[$id] ."', ";
					}
					// menghilangkan koma dibelakang tiap query, biar ga error
					$into	= $this->replaceTrailingComma( $into );
					$values	= $this->replaceTrailingComma( $values );

					if ( $values != '' && $into != '' )
					{
						$query 			= "INSERT INTO ". $this->table ." ($into) VALUES ($values)";
						$this->error= !$this->db->Execute($query);
						if ( $this->error )
						{
							$this->errorMsg	= $this->db->ErrorMsg();
						}else{
							$this->insertId	= $this->db->Insert_ID();
							if ($this->isMultiLanguage)
							{
								if ($this->tableId != 'id')
								{
									$this->langInsertId = $this->db->getOne("SELECT ".$this->tableId." FROM ".$this->table." WHERE id=".$this->insertId);
								}else{
									$this->langInsertId = $this->insertId;
								}
								if(count($lang_text) > 0)
								{
									$q = "SELECT `lang_id` FROM `$this->LanguageTable` WHERE `$this->LanguageTableId`=$this->langInsertId".$this->LanguageTableWhere;
									$r_lang_id = $this->db->getCol($q);
									foreach($lang_text AS $lang_id => $data)
									{
										$field = array();
										foreach((array)$data AS $var => $val)
										{
											$field[] = "`{$var}`='{$val}'";
										}
										$fields = implode(', ', $field);
										if(in_array($lang_id, $r_lang_id))
										{
											$q = "UPDATE `{$this->LanguageTable}` SET {$fields}
											WHERE `lang_id`={$lang_id} AND `{$this->LanguageTableId}`={$this->langInsertId}".$this->LanguageTableWhere;
										}else{
											foreach ($this->LanguageTableUpdate as $var => $val)
											{
											 $field[] = "`{$var}`='{$val}'";
											}
											$fields = implode(', ', $field);
											$q = "INSERT INTO `{$this->LanguageTable}` SET `lang_id`={$lang_id}, `{$this->LanguageTableId}`={$this->langInsertId}, {$fields}";
										}
										$this->db->Execute($q);
									}
								}
							}
							$this->actionOnInsert();
							if (!empty($this->pendingQuery))
							{
								$this->executePendingQuery();
							}
							foreach ($this->arrInput as $input)
							{
								if ($input->isIncludedInUpdateQuery)
								{
									$input->getAddAction($this->db, $this->insertId);
								}
							}
						} // if else ( $this->error )
					} // if ( $values != '' && $into != '' )
				} // if ($formExecute)
			} // if ( isset( $_POST[$this->saveButton->name] ) || isset( $_POST[$firstName] ) )
		} // if ( !$this->isLoaded->action )
	} // function action()
}