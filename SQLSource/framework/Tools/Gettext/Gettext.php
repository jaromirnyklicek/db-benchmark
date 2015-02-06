<?php
/*
	Parsovani .po souboru a kompilace do .mo
	Moznost nacitat preklady z databaze

/**
 * File::Gettext
 *
 * PHP versions 4 and 5
 *
 * @category   FileFormats
 * @package    File_Gettext
 * @author	   Michael Wallner <mike@php.net>
 * @copyright  2004-2005 Michael Wallner
 * @license    BSD, revised
 * @version    CVS: $Id: Gettext.php,v 1.7 2005/11/08 18:57:03 mike Exp $
 * @link	   http://pear.php.net/package/File_Gettext
 */


/**
 * File_Gettext
 *
 * GNU gettext file reader and writer.
 *
 * #################################################################
 * # All protected members of this class are public in its childs. #
 * #################################################################
 *
 * @author		Michael Wallner <mike@php.net>
 * @version		$Revision: 1.7 $
 * @access		public
 */
class File_Gettext
{
	/**
	 * strings
	 *
	 * associative array with all [msgid => msgstr] entries
	 *
	 * @access	protected
	 * @var		array
	*/
	var $strings = array();

	/**
	 * meta
	 *
	 * associative array containing meta
	 * information like project name or content type
	 *
	 * @access	protected
	 * @var		array
	 */
	var $meta = array();

	/**
	 * file path
	 *
	 * @access	protected
	 * @var		string
	 */
	var $file = '';

	public $table = 'gettext';
	public $tableMeta = 'gettext_meta';

	public function __construct()
	{
		$this->db = Database::singleton();
	}

	public function update($key, $string, $lang)
	{
		$sql = 'UPDATE '.$this->table.' SET '.$lang.' = "'.addcslashes($string, "\\\"").'" WHERE msgid = "'.Database::instance()->escape_str($key).'"';
		$this->db->query($sql);
	}

	public function keyexists($key)
	{
		$sql = 'SELECT id FROM '.$this->table.' WHERE msgid = "'.Database::instance()->escape_str($key).'"';
		$dbRes = $this->db->query($sql);
		return count($dbRes) > 0;
	}

	public function insertkey($key)
	{
		$sql = 'INSERT INTO '.$this->table.' (msgid) VALUES ("'.Database::instance()->escape_str($key).'")';
		$dbRes = $this->db->query($sql);
		return $dbRes;
	}

	public function saveMeta()
	{
		$sql = 'DELETE FROM '.$this->tableMeta;
		$this->db->query($sql);
		foreach ($this->meta as $k => $v) {
			$sql = 'INSERT INTO '.$this->tableMeta.' (msgid, msgstr) VALUES ("'.Database::instance()->escape_str($k).'", "'.Database::instance()->escape_str($v).'")';
			$this->db->query($sql);
		}
	}

	/**
	 * Factory
	 *
	 * @static
	 * @access	public
	 * @return	object	Returns File_Gettext_PO or File_Gettext_MO on success
	 *					or PEAR_Error on failure.
	 * @param	string	$format MO or PO
	 * @param	string	$file	path to GNU gettext file
	 */
	function factory($format, $file = '')
	{
		$format = strToUpper($format);
		$class = 'File_Gettext_' . $format;
		$obref = new $class($file);
		return $obref;
	}

	/**
	 * poFile2moFile
	 *
	 * That's a simple fake of the 'msgfmt' console command.  It reads the
	 * contents of a GNU PO file and saves them to a GNU MO file.
	 *
	 * @static
	 * @access	public
	 * @return	mixed	Returns true on success or PEAR_Error on failure.
	 * @param	string	$pofile path to GNU PO file
	 * @param	string	$mofile path to GNU MO file
	 */
	function poFile2moFile($pofile, $mofile)
	{
		if (!is_file($pofile)) {
			return File_Gettext::raiseError("File $pofile doesn't exist.");
		}


		$PO = new File_Gettext_PO($pofile);
		if (true !== ($e = $PO->load())) {
			return $e;
		}

		$MO = $PO->toMO();
		if (true !== ($e = $MO->save($mofile))) {
			return $e;
		}
		unset($PO, $MO);

		return true;
	}

	/**
	 * prepare
	 *
	 * @static
	 * @access	protected
	 * @return	string
	 * @param	string	$string
	 * @param	bool	$reverse
	 */
	function prepare($string, $reverse = false)
	{
		if ($reverse) {
			//$smap = array('\\', '"', "\n", "\t", "\r");
			//$rmap = array('\\\\', '\\"', '\\n"' . "\n" . '"', '\\t', '\\r');
			// zmena 27.10.2009 Novak
			$smap = array('"', "\n", "\t", "\r");
			$rmap = array('\\"', '\\n"' . "\n" . '"', '\\t', '\\r');
			return (string) str_replace($smap, $rmap, $string);
		} else {

			$smap = array('/\\\\"/');
			$rmap = array('&#34;');
			$string = (string) preg_replace($smap, $rmap, $string);



			$smap = array('/"\s+"/');
			$rmap = array('');
			$string = (string) preg_replace($smap, $rmap, $string);


			$smap = array('/&#34;/');
			$rmap = array('\"');
			$string = (string) preg_replace($smap, $rmap, $string);


			$smap = array('/"\s+"/', '/\\\\n/', '/\\\\r/', '/\\\\t/', '/\\\\"/');
			$rmap = array('', "\n", "\r", "\t", '"');
			return (string) preg_replace($smap, $rmap, $string);
		}
	}

	/**
	 * meta2array
	 *
	 * @static
	 * @access	public
	 * @return	array
	 * @param	string	$meta
	 */
	function meta2array($meta)
	{
		$array = array();
		foreach (explode("\n", $meta) as $info) {
			if ($info = trim($info)) {
				list($key, $value) = explode(':', $info, 2);
				$array[trim($key)] = trim($value);
			}
		}
		return $array;
	}

	/**
	 * toArray
	 *
	 * Returns meta info and strings as an array of a structure like that:
	 * <code>
	 *	 array(
	 *		 'meta' => array(
	 *			 'Content-Type'		 => 'text/plain; charset=iso-8859-1',
	 *			 'Last-Translator'	 => 'Michael Wallner <mike@iworks.at>',
	 *			 'PO-Revision-Date'  => '2004-07-21 17:03+0200',
	 *			 'Language-Team'	 => 'German <mail@example.com>',
	 *		 ),
	 *		 'strings' => array(
	 *			 'All rights reserved'	 => 'Alle Rechte vorbehalten',
	 *			 'Welcome'				 => 'Willkommen',
	 *			 // ...
	 *		 )
	 *	 )
	 * </code>
	 *
	 * @see		fromArray()
	 * @access	protected
	 * @return	array
	 */
	function toArray()
	{
		return array('meta' => $this->meta, 'strings' => $this->strings);
	}

	/**
	 * fromArray
	 *
	 * Assigns meta info and strings from an array of a structure like that:
	 * <code>
	 *	 array(
	 *		 'meta' => array(
	 *			 'Content-Type'		 => 'text/plain; charset=iso-8859-1',
	 *			 'Last-Translator'	 => 'Michael Wallner <mike@iworks.at>',
	 *			 'PO-Revision-Date'  => date('Y-m-d H:iO'),
	 *			 'Language-Team'	 => 'German <mail@example.com>',
	 *		 ),
	 *		 'strings' => array(
	 *			 'All rights reserved'	 => 'Alle Rechte vorbehalten',
	 *			 'Welcome'				 => 'Willkommen',
	 *			 // ...
	 *		 )
	 *	 )
	 * </code>
	 *
	 * @see		toArray()
	 * @access	protected
	 * @return	bool
	 * @param	array		$array
	 */
	function fromArray($array)
	{
		if (!array_key_exists('strings', $array)) {
			if (count($array) != 2) {
				return false;
			} else {
				list($this->meta, $this->strings) = $array;
			}
		} else {
			$this->meta = @$array['meta'];
			$this->strings = @$array['strings'];
		}
		return true;
	}

	/**
	 * toMO
	 *
	 * @access	protected
	 * @return	object	File_Gettext_MO
	 */
	function toMO()
	{
		$MO = new File_Gettext_MO;
		$MO->fromArray($this->toArray());
		return $MO;
	}

	/**
	 * toPO
	 *
	 * @access	protected
	 * @return	object		File_Gettext_PO
	 */
	function toPO()
	{
		$PO = new File_Gettext_PO;
		$PO->fromArray($this->toArray());
		return $PO;
	}

	/**
	 * Raise PEAR error
	 *
	 * @static
	 * @access	protected
	 * @return	object
	 * @param	string	$error
	 * @param	int		$code
	 */
	function raiseError($error = null, $code = null)
	{
		die($error);
		//include_once 'PEAR.php';
		//return PEAR::raiseError($error, $code);
	}
}


/**
 * File_Gettext_MO
 *
 * GNU MO file reader and writer.
 *
 * @author		Michael Wallner <mike@php.net>
 * @version		$Revision: 1.7 $
 * @access		public
 */
class File_Gettext_MO extends File_Gettext
{
	/**
	 * file handle
	 *
	 * @access	private
	 * @var		resource
	 */
	var $_handle = null;

	/**
	 * big endianess
	 *
	 * Whether to write with big endian byte order.
	 *
	 * @access	public
	 * @var		bool
	 */
	var $writeBigEndian = false;

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	object		File_Gettext_MO
	 * @param	string		$file	path to GNU MO file
	 */
	function File_Gettext_MO($file = '')
	{
		$this->file = $file;
	}

	/**
	 * _read
	 *
	 * @access	private
	 * @return	mixed
	 * @param	int		$bytes
	 */
	function _read($bytes = 1)
	{
		if (0 < $bytes = abs($bytes)) {
			return fread($this->_handle, $bytes);
		}
		return null;
	}

	/**
	 * _readInt
	 *
	 * @access	private
	 * @return	int
	 * @param	bool	$bigendian
	 */
	function _readInt($bigendian = false)
	{
		return current($array = unpack($bigendian ? 'N' : 'V', $this->_read(4)));
	}

	/**
	 * _writeInt
	 *
	 * @access	private
	 * @return	int
	 * @param	int		$int
	 */
	function _writeInt($int)
	{
		return $this->_write(pack($this->writeBigEndian ? 'N' : 'V', (int) $int));
	}

	/**
	 * _write
	 *
	 * @access	private
	 * @return	int
	 * @param	string	$data
	 */
	function _write($data)
	{
		return fwrite($this->_handle, $data);
	}

	/**
	 * _writeStr
	 *
	 * @access	private
	 * @return	int
	 * @param	string	$string
	 */
	function _writeStr($string)
	{
		return $this->_write($string . "\0");
	}

	/**
	 * _readStr
	 *
	 * @access	private
	 * @return	string
	 * @param	array	$params		associative array with offset and length
	 *								of the string
	 */
	function _readStr($params)
	{
		fseek($this->_handle, $params['offset']);
		return $this->_read($params['length']);
	}

	/**
	 * Load MO file
	 *
	 * @access	 public
	 * @return	 mixed	 Returns true on success or PEAR_Error on failure.
	 * @param	 string  $file
	 */
	function load($file = null)
	{
		if (!isset($file)) {
			$file = $this->file;
		}

		// open MO file
		if (!is_resource($this->_handle = @fopen($file, 'rb'))) {
			return parent::raiseError($php_errormsg . ' ' . $file);
		}
		// lock MO file shared
		if (!@flock($this->_handle, LOCK_SH)) {
			@fclose($this->_handle);
			return parent::raiseError($php_errormsg . ' ' . $file);
		}

		// read (part of) magic number from MO file header and define endianess
		switch ($magic = current($array = unpack('c', $this->_read(4))))
		{
			case -34:
				$be = false;
			break;

			case -107:
				$be = true;
			break;

			default:
				return parent::raiseError("No GNU mo file: $file (magic: $magic)");
		}

		// check file format revision - we currently only support 0
		if (0 !== ($_rev = $this->_readInt($be))) {
			return parent::raiseError('Invalid file format revision: ' . $_rev);
		}

		// count of strings in this file
		$count = $this->_readInt($be);

		// offset of hashing table of the msgids
		$offset_original = $this->_readInt($be);
		// offset of hashing table of the msgstrs
		$offset_translat = $this->_readInt($be);

		// move to msgid hash table
		fseek($this->_handle, $offset_original);
		// read lengths and offsets of msgids
		$original = array();
		for ($i = 0; $i < $count; $i++) {
			$original[$i] = array(
				'length' => $this->_readInt($be),
				'offset' => $this->_readInt($be)
			);
		}

		// move to msgstr hash table
		fseek($this->_handle, $offset_translat);
		// read lengths and offsets of msgstrs
		$translat = array();
		for ($i = 0; $i < $count; $i++) {
			$translat[$i] = array(
				'length' => $this->_readInt($be),
				'offset' => $this->_readInt($be)
			);
		}

		// read all
		for ($i = 0; $i < $count; $i++) {
			$this->strings[$this->_readStr($original[$i])] =
				$this->_readStr($translat[$i]);
		}

		// done
		@flock($this->_handle, LOCK_UN);
		@fclose($this->_handle);
		$this->_handle = null;

		// check for meta info
		if (isset($this->strings[''])) {
			$this->meta = parent::meta2array($this->strings['']);
			unset($this->strings['']);
		}

		return true;
	}

	/**
	 * Save MO file
	 *
	 * @access	public
	 * @return	mixed	Returns true on success or PEAR_Error on failure.
	 * @param	string	$file
	 */
	function save($file = null)
	{
		if (!isset($file)) {
			$file = $this->file;
		}

		// open MO file
		if (!is_resource($this->_handle = @fopen($file, 'wb'))) {
			return parent::raiseError($php_errormsg . ' ' . $file);
		}
		// lock MO file exclusively
		if (!@flock($this->_handle, LOCK_EX)) {
			@fclose($this->_handle);
			return parent::raiseError($php_errormsg . ' ' . $file);
		}

		// write magic number
		if ($this->writeBigEndian) {
			$this->_write(pack('c*', 0x95, 0x04, 0x12, 0xde));
		} else {
			$this->_write(pack('c*', 0xde, 0x12, 0x04, 0x95));
		}

		// write file format revision
		$this->_writeInt(0);

		$count = count($this->strings) + ($meta = (count($this->meta) ? 1 : 0));
		// write count of strings
		$this->_writeInt($count);

		$offset = 28;
		// write offset of orig. strings hash table
		$this->_writeInt($offset);

		$offset += ($count * 8);
		// write offset transl. strings hash table
		$this->_writeInt($offset);

		// write size of hash table (we currently ommit the hash table)
		$this->_writeInt(0);

		$offset += ($count * 8);
		// write offset of hash table
		$this->_writeInt($offset);

		// unshift meta info
		if ($meta) {
			$meta = '';
			foreach ($this->meta as $key => $val) {
				$meta .= $key . ': ' . $val . "\n";
			}
			$strings = array('' => $meta) + $this->strings;
		} else {
			$strings = $this->strings;
		}

		// write offsets for original strings
		foreach (array_keys($strings) as $o) {
			$len = strlen($o);
			$this->_writeInt($len);
			$this->_writeInt($offset);
			$offset += $len + 1;
		}

		// write offsets for translated strings
		foreach ($strings as $t) {
			$len = strlen($t);
			$this->_writeInt($len);
			$this->_writeInt($offset);
			$offset += $len + 1;
		}

		// write original strings
		foreach (array_keys($strings) as $o) {
			$this->_writeStr($o);
		}

		// write translated strings
		foreach ($strings as $t) {
			$this->_writeStr($t);
		}

		// done
		@flock($this->_handle, LOCK_UN);
		@fclose($this->_handle);
		return true;
	}
}

/**
 * File::Gettext
 *
 * PHP versions 4 and 5
 *
 * @category   FileFormats
 * @package    File_Gettext
 * @author	   Michael Wallner <mike@php.net>
 * @copyright  2004-2005 Michael Wallner
 * @license    BSD, revised
 * @version    CVS: $Id: PO.php,v 1.5 2005/11/08 18:57:06 mike Exp $
 * @link	   http://pear.php.net/package/File_Gettext
 */

/**
 * File_Gettext_PO
 *
 * GNU PO file reader and writer.
 *
 * @author		Michael Wallner <mike@php.net>
 * @version		$Revision: 1.5 $
 * @access		public
 */
class File_Gettext_PO extends File_Gettext
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	object		File_Gettext_PO
	 * @param	string		path to GNU PO file
	 */
	function File_Gettext_PO($file = '')
	{
		parent::__construct();
		$this->file = $file;
	}

	function loadFromDB($sql, $meta)
	{
		$dbRes = $this->db->query($sql);
		foreach($dbRes as $item) {
			$item = array_values((array)$item);
			if($item[0] != $item[1]) $this->strings[$item[0]] = trim($item[1]);
			else $this->strings[$item[0]] = "";
		}

		$dbRes = $this->db->query($meta);
		foreach($dbRes as $item) {
			$item = array_values((array)$item);
			$this->meta[$item[0]] = $item[1];
		}
	}
	/**
	 * Load PO file
	 *
	 * @access	public
	 * @return	mixed	Returns true on success or PEAR_Error on failure.
	 * @param	string	$file
	 */
	function load($file = null)
	{
		if (!isset($file)) {
			$file = $this->file;
		}

		// load file
		if (!$contents = file($file)) {
			return parent::raiseError($php_errormsg . ' ' . $file);
		}
		$contents = implode('', $contents);


		// match all msgid/msgstr entries
		$matched = preg_match_all(
			'/msgid\s+((?:".*(?<!\\\\)"\s*)+)\s+' .
			'msgstr\s+((?:".*(?<!\\\\)"\s*)+)/',
			$contents, $matches
		);
		unset($contents);

		if (!$matched) {
			return parent::raiseError('No msgid/msgstr entries found');
		}


		// get all msgids and msgtrs
		for ($i = 0; $i < $matched; $i++) {
			 $msgid = substr(trim($matches[1][$i]), 1, -1);
			 $a = parent::prepare($msgid);
			 $msgstr = substr(trim($matches[2][$i]), 1, -1);
			$this->strings[parent::prepare($msgid)] = parent::prepare($msgstr);
		}

		// check for meta info
		if (isset($this->strings[''])) {
			$this->meta = parent::meta2array($this->strings['']);
			unset($this->strings['']);
		}

		return true;
	}

	/**
	 * Save PO file
	 *
	 * @access	public
	 * @return	mixed	Returns true on success or PEAR_Error on failure.
	 * @param	string	$file
	 */
	function save($file = null)
	{
		if (!isset($file)) {
			$file = $this->file;
		}

		// open PO file
		if (!is_resource($fh = @fopen($file, 'w'))) {
			throw new Exception(_('Operace se nezdařila'));
		}
		// lock PO file exclusively
		if (!@flock($fh, LOCK_EX)) {
			@fclose($fh);
			throw new Exception(_('Operace se nezdařila'));
		}

		// write meta info
		if (count($this->meta)) {
			$meta = 'msgid ""' . "\nmsgstr " . '""' . "\n";
			foreach ($this->meta as $k => $v) {
				$meta .= '"' . $k . ': ' . $v . '\n"' . "\n";
			}
			fwrite($fh, $meta . "\n");
		}
		// write strings
		foreach ($this->strings as $o => $t) {
			if($t != "") {
				fwrite($fh,
					'msgid "'  . parent::prepare($o, true) . '"' . "\n" .
					'msgstr "' . parent::prepare($t, true) . '"' . "\n\n"
				);
			}
		}

		//done
		@flock($fh, LOCK_UN);
		@fclose($fh);
		return true;
	}
}
