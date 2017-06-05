<?php  if ( ! defined('_VALID_BBC')) exit('No direct script access allowed');

if (!defined('_SYS'))
{
	if (defined('_ROOT'))
	{
		define('_SYS', _ROOT.'includes/system/');
	}else{
		define('_SYS', str_replace('\\', '/', __DIR__).'/includes/system/');
	}
}
if (!defined('_ROOT'))
{
	define('_ROOT', str_replace('\\', '/', __DIR__).'/');
}
class bbcSQL
{
	var $debug = 0;
	var $debug_tot = 0;
	var $dbOutput;
	var $resid;
	var $timestamp_sec= 900;
	var $tmp_is_cache = false;
	var $self, $self_stop, $now, $timestamp, $cache_dir;
	function __construct()
	{
		global $Bbc;
		$Bbc->debug[]    =& $this->dbOutput;
		$this->self      = $this->fixPath(__FILE__);
		$this->now       = time();
		$this->timestamp = strtotime('-'.$this->timestamp_sec.' SECOND');
		if (!defined('_SYS'))
		{
			$this->self_stop= $this->self;
		}else{
			$this->self_stop= $this->fixPath(_SYS.'layout.modules.php');
		}
		if (!defined('_CACHE'))
		{
			define('_CACHE', sys_get_temp_dir());
		}
		$this->set_cache(_CACHE);
	}
	function __destruct()
	{
		if (!empty($this->link))
		{
			@mysqli_close($this->link);
		}
	}
	function Connect($DB_SERVER, $DB_USER, $DB_PASSWORD, $DB_TABLE)
	{
		if (!function_exists('mysqli_connect'))
		{
			die('Sorry, your PHP does not support Mysql due to mysqli_connect is not available as a function');
		}else{
			try {
				$out = @mysqli_connect($DB_SERVER, $DB_USER, $DB_PASSWORD, $DB_TABLE);
			} catch (Exception $e) {
				die('Sorry, '.$e->getMessage());
			}
		}
		if (!$out)
		{
			$this->echoerror();
		}else{
			mysqli_set_charset($out, "utf8");
			$this->link = $out;
			$this->set_time();
		}
		return $out;
	}

	function Pconnect($DB_SERVER, $DB_USER, $DB_PASSWORD, $DB_TABLE)
	{
		return $this->Connect('p:'.$DB_SERVER, $DB_USER, $DB_PASSWORD, $DB_TABLE);
	}

	function set_time($offset = '')
	{
		if (empty($offset))
		{
			$now    = new DateTime();
			$mins   = $now->getOffset() / 60;
			$sgn    = ($mins < 0 ? -1 : 1);
			$mins   = abs($mins);
			$hrs    = floor($mins / 60);
			$mins  -= $hrs * 60;
			$offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);
		}
		$this->Execute("SET time_zone='$offset'");
	}

	function set_cache($dir)
	{
		$this->cache_dir= $dir;
		if(!is_dir($this->cache_dir))
		{
			@mkdir($this->cache_dir, 0777);
		}
	}

	function Execute($sql)
	{
		if (empty($sql))
		{
			return false;
		}
		if (!$this->link)
		{
			if ($this->debug)
			{
				$this->dbOutput .= "<br /><b>Koneksi ke database gagal.</b>";
				return false;
			}
		}
		if ($this->resid)
		{
			@mysqli_free_result($this->resid);
		}
		if (preg_match('~[a-z]+\'[a-z]+~is', $sql))
		{
			$sql = preg_replace('~([a-z0-9]+)\'([a-z0-9]+)~is', '$1\\\'$2', $sql);
		}
		$result = @mysqli_query($this->link, $sql);
		$this->echoerror($sql);
		$this->resid = $result;
		$this->tmp_is_cache = false;
		return $result;
	}

	function Insert_ID()
	{
		$result = mysqli_insert_id($this->link);
		return $result;
	}

	function Affected_rows()
	{
		if(!$this->tmp_is_cache)
		{
			$result = @mysqli_num_rows($this->resid);
			if(!$result)
			{
				$result = mysqli_affected_rows($this->link);
			}
		}else{
			$result = $this->tmp_cache_count;
		}
		return $result;
	}

	function RecordCount()
	{
		return $this->Affected_rows();
	}

	function getOne($sql)
	{
		$this->Execute($sql);
		$row = array();
		if($this->resid)
		{
			$row = mysqli_fetch_array($this->resid);
		}
		return @$row[0];
	}

	function cacheGetOne($sql, $exp_sec = '', $path = '')
	{
		return $this->cache('getOne', $sql, $path, $exp_sec);
	}

	function getRow($sql)
	{
		$this->Execute($sql);
		$row = array();
		if($this->resid)
		{
			$row = mysqli_fetch_assoc($this->resid);
		}
		return $row;
	}

	function cacheGetRow($sql, $exp_sec = '', $path = '')
	{
		return $this->cache('getRow', $sql, $path, $exp_sec);
	}

	function getCol($sql)
	{
		$this->Execute($sql);
		$row = array();
		if($this->resid)
		{
			while ($r = mysqli_fetch_array($this->resid))
			{
				$row[] = @$r[0];
			}
		}
		return $row;
	}

	function cacheGetCol($sql, $exp_sec = '', $path = '')
	{
		return $this->cache('getCol', $sql, $path, $exp_sec);
	}

	function getAssoc($sql)
	{
		$this->Execute($sql);
		$out = array();
		if($this->resid)
		{
			while ($r = mysqli_fetch_assoc($this->resid))
			{
				$id = current($r);
				if(count($r) > 2 )
				{
					array_shift($r);
					$dt = $r;
				}else{
					$dt = @next($r);
				}
				$out[$id] = $dt;
			}
		}
		return $out;
	}

	function cacheGetAssoc($sql, $exp_sec = '', $path = '')
	{
		return $this->cache('getAssoc', $sql, $path, $exp_sec);
	}

	function getAll($sql)
	{
		$this->Execute($sql);
		$row = array();
		if($this->resid)
		{
			while ($r = mysqli_fetch_assoc($this->resid))
			$row[] = $r;
		}
		return $row;
	}

	function cacheGetAll($sql, $exp_sec = '', $path = '')
	{
		return $this->cache('getAll', $sql, $path, $exp_sec);
	}

	function cache($func, $query, $path = '', $sec = '')
	{
		// sample $timestamp = '-2 hour';
		if(empty($path)) $path = implode('/',str_split(md5($query), 3)).'.cfg';
		$is_write	= true;
		$output		= $out = array();
		$path_to	= $this->cache_dir.$path;
		$exp_sec	= $this->cache_time($sec);
		if(is_file($path_to))
		{
			$out= urldecode_r(json_decode($this->file_read($path_to), 1));
			if(!empty($out[$query][0]) && $out[$query][0] > $exp_sec)
			{
				$output = $out[$query][1];
				$this->tmp_is_cache		= true;
				$this->tmp_cache_count= count($output);
				$is_write	= false;
			}
		}
		if($is_write)
		{
			$output = $this->$func($query);
			$data		= array_merge((array)$out, array($query => array($this->now, $output)));
			if (!empty($data))
			{
				$this->file_write($path_to, json_encode(urlencode_r($data)));
			}
		}
		return $output;
	}

	function cache_time($sec)
	{
		if($sec === '')	$sec = 'null';
		if(!empty($this->tmp_cache_time[$sec]))
		{
			return $this->tmp_cache_time[$sec];
		}
		if($sec == 'null')
		{
			$exp_sec = strtotime('-'.$this->timestamp.' SECOND');
		}else
		if($sec == 0)
		{
			$exp_sec = 0;
		}else
		if(is_numeric($sec))
		{
			$exp_sec = strtotime('-'.$sec.' SECOND');
		}else{
			$exp_sec = strtotime('-'.$sec);
		}
		$this->tmp_cache_time[$sec] = $exp_sec;
		return $this->tmp_cache_time[$sec];
	}

	function cache_clean($data='')
	{
		/* FOR: $db->cache_clean(); */
		if (empty($data))
		{
			$this->path_delete($this->cache_dir);
		}else{
			/* FOR: $db->cache_clean('modules.cfg'); */
			if(is_file($this->cache_dir.$data))
			{
				$this->path_delete($this->cache_dir.$data);
			}else
			/* FOR: $db->cache_clean('config/'); */
			if (is_dir($this->cache_dir.$data))
			{
				$this->path_delete($this->cache_dir.$data);
			}else{
				/* FOR: $db->cache_clean(md5($q).'.cfg'); */
				if (substr($data, -4)=='.cfg')
				{
					$data = substr($data, 0, -4);
				}else{
					/* FOR: $db->cache_clean($q); */
					$data = md5($data);
				}
				$this->path_delete($this->cache_dir.implode('/',str_split($data, 3)).'.cfg');
			}
		}
	}

	function _fetch()
	{
		if (!$this->link)
		{
			if ($this->debug)
			$this->dbOutput .= "<br /><b>Koneksi ke database gagal!</b>";
			return false;
		}
		if (!$this->resid)
		{
			if ($this->debug)
			$this->dbOutput .= "<br /><b>Tak ada data yang didapat!</b>";
			return false;
		}
		$result = mysqli_fetch_array($this->resid, MYSQL_BOTH);
		$this->echoerror();
		return $result;
	}

	function echoerror($sql = '')
	{
		$dbOutput = '';
		if (!$this->debug)
		{
			return;
		}else{
			if(!empty($sql))
			{
				$this->debug_tot++;
				$dbOutput .= '<hr />SQL - '.$this->debug_tot.': '.htmlentities($sql);
			}
			if (mysqli_errno($this->link))
			{
				if (function_exists( 'debug_backtrace' ))
				{
					foreach(debug_backtrace() AS $dt)
					{
						if(!empty($dt['file']))
						{
							$dt['file'] = $this->fixPath($dt['file']);
							if($dt['file'] == $this->self_stop)
							{
								break;
							}else
							if(isset($dt['file']) && ($dt['file'] != $this->self) )
							{
								$dbOutput .= '<br />'.$dt['file'].'	line:'.$dt['line'];
							}
						}
					}
				}
				$dbOutput .= "<br /><span style=\"color:#ff0000;font-weight: bold;\">" . mysqli_errno($this->link);
				$dbOutput .= ": ". mysqli_error($this->link) ."</span><br />";
				echo $dbOutput;
				if (function_exists('iLog'))
				{
					iLog($dbOutput);
				}
			}
			$this->dbOutput .= $dbOutput;
		}
	}

	function fixPath($value)
	{
		$value = str_replace(array('\\', _ROOT), array('/', ''), $value);
		if (defined('_MST'))
		{
			$r = explode('|', _MST);
			foreach ($r as $p)
			{
				$p = trim($p);
				if (!empty($p))
				{
					$value = preg_replace('~^'.preg_quote($p, '~').'~s', '', $value);
				}
			}
		}
		return $value;
	}

	function ErrorMsg()
	{
		$dbOutput = '';
		if (mysqli_errno($this->link))
		{
			$dbOutput = mysqli_errno($this->link);
			$dbOutput.= ': '. mysqli_error($this->link);
		}
		return $dbOutput;
	}

	function echoquery($sql)
	{
		$this->Execute($sql);
		$index  = 0;
		$header = array('#');
		$rows   = array();
		while ($field = mysqli_fetch_field($this->resid))
		{
			$header[] = $field->name;
		}
		while ($dt = $this->_fetch())
		{
			$row = array(++$index);
			for ($i=0; $i<mysqli_num_fields($this->resid); $i++)
			{
				$row[] = htmlentities($dt[$i]);
			}
			$rows[] = $row;
		}
		$tHead = '<thead><tr><th>'.implode('</th><th>', $header).'</th></tr></thead>';
		$data  = array();
		foreach ($rows as $row)
		{
			$data[] = '<td>'.implode('</td><td>', $row).'</td>';
		}
		$tBody = '<tbody><tr>'.implode('</tr><tr>', $data).'</tr></tbody>';
		echo '<table class="table table-striped table-bordered table-hover">'.$tHead.$tBody.'</table>';
	}

	function file_read($file = '', $method = 'r')
	{
		if (!file_exists($file))
		{
			return FALSE;
		}
		if (function_exists('file_get_contents'))
		{
			return file_get_contents($file);
		}
		if (!$fp = @fopen($file, $method))
		{
			return FALSE;
		}
		flock($fp, LOCK_SH);
		$data = '';
		if (filesize($file) > 0)
		{
			$data =& fread($fp, filesize($file));
		}
		flock($fp, LOCK_UN);
		fclose($fp);
		return $data;
	}

	function file_write($path, $data='', $mode = 'w+')
	{
		if(!file_exists(dirname($path)))
		{
			$this->path_create(dirname($path));
		}
		if ( ! $fp = @fopen($path, $mode))
		{
			return FALSE;
		}
		flock($fp, LOCK_EX);
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);
		@chmod($path, 0777);
		return TRUE;
	}

	function path_delete($path)
	{
		if (file_exists($path))
		{
			@chmod($path,0777);
			if (is_dir($path))
			{
				$handle = opendir($path);
				while($filename = readdir($handle))
				{
					if ($filename != "." && $filename != "..")
					{
						$this->path_delete($path.'/'.$filename);
					}
				}
				closedir($handle);
				@rmdir($path);
			}else{
				@unlink($path);
			}
		}
	}

	function path_create($path, $chmod = 0777)
	{
		if(!empty($path))
		{
			if(file_exists($path))
			{
				$output = true;
			}else{
				$root = dirname($this->cache_dir).'/';
				if(!file_exists($root))
				{
					mkdir($root, $chmod);
					chmod($root, $chmod);
				}
				$path    = @preg_replace('~^'.$root.'~', '', $path);
				$tmp_dir = $root;
				$r       = explode('/', $path);
				foreach($r AS $dir)
				{
					if(!empty($dir))
					{
						$tmp_dir .= $dir.'/';
						if(!file_exists($tmp_dir))
						{
							@mkdir($tmp_dir, $chmod);
							@chmod($tmp_dir, $chmod);
						}
					}
				}
				$output = file_exists($tmp_dir);
			}
		}else{
			$output = false;
		}
		return $output;
	}
}
if (!function_exists('pr'))
{
	function pr($text='', $return = false)
	{
		$is_multiple = (func_num_args() > 2) ? true : false;
		if(!$is_multiple)
		{
			if(is_numeric($return))
			{
				if($return==1 || $return==0)
				{
					$return = $return ? true : false;
				}else{
					$is_multiple = true;
				}
			}
			if(!is_bool($return)) $is_multiple = true;
		}
		if($is_multiple)
		{
			echo "<pre>\n";
			echo "<b>1 : </b>";
			print_r($text);
			$i = func_num_args();
			if($i > 1)
			{
				$j = array();
				$k = 1;
				for($l=1;$l < $i;$l++)
				{
					$k++;
					echo "\n<b>$k : </b>";
					print_r(func_get_arg($l));
				}
			}
			echo "\n</pre>";
		}else{
			if($return)
			{
				ob_start();
			}
				echo "<pre>\n";
				print_r($text);
				echo "\n</pre>";
			if($return)
			{
				$output = ob_get_contents();
				ob_end_clean();
				return $output;
			}
		}
	}
}
if(!empty($_DB))
{
	foreach((array)$_DB AS $i => $d)
	{
		$i                = ($i > 0) ? $i : '';
		$GLOBALS['db'.$i] = new bbcSQL();
		// $ifconn           = $GLOBALS['db'.$i]->Pconnect($d['SERVER'], $d['USERNAME'], $d['PASSWORD'], $d['DATABASE']);
		$ifconn           = $GLOBALS['db'.$i]->Connect($d['SERVER'], $d['USERNAME'], $d['PASSWORD'], $d['DATABASE']);
		if (!$ifconn)
		{
			die('Error while connecting to Database "'.$d['DATABASE'].'" on Server');
		}
	}
	$_DB = array();unset($_DB);
}
