<?php

/**
 * 	CSVReader je objekt usnadnuji cteni z CSV souboru
 * 	Precte radek ze souboru a pokud jsou definovane sloupce, tak vytvori objekt s atributy
 *
 * Priklad:
 * 	$rd = new CSVReader(WWW_DIR.'/import.csv');
 * 	$rd->addColumn(0, 'code');
 * 	$rd->addColumn(1, 'title');
 * 	$rd->addColumn(2, 'price');
 * 	$rd->addColumn(3, 'firstname')
 * 		->addHelper(function($fullname){
 * 			return substr($fullname, 0, strpos(' '));
 * 		});
 * 	$rd->addColumn(3, 'lastname')
 * 		->addHelper(function($fullname){
 * 			return substr($fullname, strpos(' ') + 1);
 * 		});
 *
 * 	foreach($rd as $row) {
 * 		 echo $row->code;
 * 	}
 *
 *   $rd->getData();
 *   $rd->getErrors();
 *
 * @copyright (c) Via Aurea, s.r.o.
 */
class CSVReader extends Object implements Iterator
{
	/** Soubor pro cteni * */
	protected $file;

	/** preskocit prvni zadek se zahlavim * */
	protected $skipFirstLine = TRUE;

	/** oddelovac sloupcu * */
	protected $separator = ';';

	/** namapovani sloupci na atributy objektu * */
	protected $columnModel;

	/** sloupce s nastavenym atributem */
	protected $indexModel;

	/** pole zpráv o chybách * */
	protected $msg = array();

	/** data readeru na výstup* */
	protected $data = array();

	/** chyby readeru na výstup* */
	protected $errors = array();
	protected $skipped = FALSE;
	protected $fd = NULL;
	protected $row = NULL;
	protected $line = 0;

	/** Znakova sada souboru, bude prekonvertovano do UTF8 * */
	protected $charset = NULL;


	public function __construct($file = NULL, $charset = 'utf-8')
	{
		$this->setFile($file);
		$this->charset = $charset;

		$this->msg['less-line-error'] = _('Na řádku %d chybí sloupce.');
	}


	public function setFile($value)
	{
		$this->file = $value;
		return $this;
	}


	public function getFile()
	{
		return $this->file;
	}


	public function setSeparator($value)
	{
		$this->separator = $value;
		return $this;
	}


	public function getSeparator()
	{
		return $this->separator;
	}


	public function setCharset($value)
	{
		$this->charset = $value;
		return $this;
	}


	public function skipFirstLine($value)
	{
		$this->skipFirstLine = $value;
		return $this;
	}


	public function getData()
	{
		if (!$this->data) {
			$this->exec();
		}
		return $this->data;
	}


	public function getErrors()
	{
		if (!$this->errors) {
			$this->exec();
		}
		return $this->errors;
	}


	/**
	 * Naplní $this->data a $this->errors
	 */
	private function exec()
	{
		foreach ($this as $row) {

			foreach ($row->__ERRORS as $error) {
				$this->errors[] = $error;
			}
			unset($row->__ERRORS);
			unset($row->__DATA);

			$this->data[] = (array) $row;
		}
	}


	/**
	 * Precte dalsi zaznam ze souboru.
	 * Pokud je na konci vrati false
	 *
	 */
	public function readNext()
	{
		if ($this->fd === NULL) {
			$this->fd = fopen($this->file, 'r');
		}
		if (!$this->skipped && $this->skipFirstLine) {
			$this->readLine();
			$this->skipped = TRUE;
		}
		$line = $this->readLine();
		if ($line === FALSE || $line === "") {
			return FALSE;
		}

		// Rozparsuje aktuální řádek
		$row = $this->parseLine($line);

		// Aplikuje validační pravidla a vrátí chyby
		$rowErrors = $this->applyRulesOnRow($row);
		if ($rowErrors) {
			$row->__ERRORS = $rowErrors;
		}

		// Zkontroluje počet sloupců v řádku
		$columnCountError = $this->checkColumnsNumber($row);
		if ($columnCountError) {
			$row->__ERRORS[] = $columnCountError;
		}

		// Aplikuje callbacky
		$processedRow = $this->applyCallbacksOnRow($row);

		return $processedRow;
	}


	/**
	 * Kontroluje maximální namapovaný počet sloupců k datům z CSV
	 *
	 * @param type $row
	 * @return type
	 */
	protected function checkColumnsNumber($row)
	{
		if ($this->columnModel === NULL) {
			return NULL;
		}

		if (max(array_keys($this->indexModel)) > count($row->__DATA)) {
			return array(
				'row' => $this->line,
				'column' => NULL,
				'message' => sprintf($this->msg['less-line-error'], $this->line)
			);
		}
	}


	/**
	 * Aplikuje callbacky na řádku
	 *
	 * @param type $row
	 * @return type
	 */
	protected function applyCallbacksOnRow($row)
	{
		if ($this->columnModel === NULL) {
			return $row;
		}

		foreach ($this->columnModel as $name => $column) {
			$row->{$name} = $column->applyCallbacks($row->{$name}, $row);
		}

		return $row;
	}


	/**
	 * Aplikuje pravidla na řádku
	 *
	 * @param type $row
	 */
	protected function applyRulesOnRow($row)
	{
		if ($this->columnModel === NULL) {
			return NULL;
		}

		$errs = array();
		foreach ($this->columnModel as $name => $column) {
			$err = $column->applyRules($row->{$name}, $row);
			if ($err) {
				$errs[] = array(
					'row' => $this->line,
					'column' => $column->getIndex() + 1,
					'message' => $err
				);
			}
		}
		return $errs;
	}


	protected function readLine()
	{
		$this->line++;
		$l = iconv($this->charset, 'utf-8', fgets($this->fd));
		return $l;
	}


	/**
	 * Rozdeleni podle sloupcu a vraceni pole nebo objektu
	 *
	 * @param string $line
	 * @return array|object
	 */
	protected function parseLine($line)
	{
		$len = strlen($line);

		$cur_row = array();
		$cur_val = "";
		$state = "first item";

		for ($i = 0; $i < $len; $i++) {
			$ch = substr($line, $i, 1);
			if ($state == "first item") {
				if ($ch == '"') {
					$state = "we're quoted hea";
				} elseif ($ch == $this->separator) { //empty
					$cur_row[] = ""; //done with first one
					$cur_val = "";
					$state = "first item";
				} else {
					$cur_val .= $ch;
					$state = "gather not quote";
				}
			} elseif ($state == "we're quoted hea") {
				if ($ch == '"') {
					$state = "potential end quote found";
				} else {
					$cur_val .= $ch;
				}
			} elseif ($state == "potential end quote found") {
				if ($ch == '"') {
					$cur_val .= '"';
					$state = "we're quoted hea";
				} elseif ($ch == $this->separator) {
					$cur_row[] = trim($cur_val);
					$cur_val = "";
					$state = "first item";
				} else {
					$cur_val .= $ch;
					$state = "we're quoted hea";
				}
			} elseif ($state == "gather not quote") {
				if ($ch == $this->separator) {
					$cur_row[] = trim($cur_val);
					$cur_val = "";
					$state = "first item";
				} else {
					$cur_val .= $ch;
				}
			}
		}
		if (!empty($cur_val)) {
			$cur_row[] = trim($cur_val);
		}

		$arr = $cur_row;
		if ($this->columnModel == NULL) {
			return $arr;
		} else {
			$row = array();
			$row['__DATA'] = $arr;
			$row['__ERRORS'] = array();
			foreach ($this->columnModel as $name => $column) {
				$key = $column->getIndex();
				$row[$name] = isset($arr[$key]) ? $arr[$key] : NULL;
			}
			return (object) $row;
		}
	}


	/**
	 *  Vrati jaky index ma sloupec
	 */
	public function getColumnIndex($name)
	{
		if ($this->columnModel === NULL || !isset($this->columnModel[$name])) {
			return NULL;
		}
		return $this->columnModel[$name]->getIndex();
	}


	/**
	 * Pridani mapovani indexu sloupce na atribut objektu
	 *
	 * @param int $index
	 * @param string $name
	 */
	public function addColumn($index, $column)
	{
		$columnObject = new CSVColumn($column, $index);
		$this->columnModel[$column] = $columnObject;
		$this->indexModel[$index] = true;
		return $columnObject;
	}


	// Iterator interface

	function rewind()
	{
		if ($this->fd === NULL) {
			$this->fd = fopen($this->file, 'r');
		}
		fseek($this->fd, 0);
		$this->skipped = FALSE;
		$this->next();
	}


	function current()
	{
		return $this->row;
	}


	function key()
	{
		return $this->line;
	}


	function next()
	{
		$this->row = $this->readNext();
	}


	function valid()
	{
		return $this->row != NULL;
	}

}