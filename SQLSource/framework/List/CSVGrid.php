<?php

/**
 * Komponenta pro ulozeni dat do CSV souboru
 * Pracuje na stejnem pricipu jako DataGrid.
 *
 * Kvuli pametove narocnosti se pro kazdy radek delaji vazane dotazy zvlast.
 * (nelze totiz nacist vsechny ID do pameti a udelat pak jeden vazany dotaz jako u klasickeho datagridu)
 *
 *
 * @author Ondrej Novak
 * @copyright Copyright (c) 2009, Ondrej Novak
 */
class CSVGrid extends DataList
{
	/** @ int */
	protected $_limit = null;
	protected $itemsEnabled = TRUE;
	public $applyCallback = true;

	/**
	 * @var string oddelovac bunek tabulky
	 */
	public $defaultDelimiter = ';';

	/**
	 * V retazcoch sa vykonava vymena delimiteru za tento retazec - nahradza sa pred prevodom do charsetu win1250.
	 * Ak je tento efekt neziaduci, treba $defaultDelimiterReplacement nastavit na null alebo false.
	 * @var string defaultna nahrada za oddelovac
	 */
	public $defaultDelimiterReplacement = FALSE;


	protected function getLimit()
	{
		return NULL;
	}


	public function render($file = NULL, $delimiter = NULL, $delimiterReplacement = NULL)
	{
		$out = '';

		if ($delimiter === NULL) {
			$delimiter = $this->defaultDelimiter;
		}
		if ($delimiterReplacement === NULL) {
			$delimiterReplacement = $this->defaultDelimiterReplacement;
		}

		$this->initSource();

		if ($file) {
			$fp = fopen($file, "wb");
			if (!is_resource($fp)) {
				die("Cannot open $file");
			}
		}

		$header = array();
		foreach ($this->getColumnModel()->getVisibleColumns() as $column) {
			// MS Excel Bug - pokud zahlavi obsahuje sloupec ID, tak Excel spadne
			if ($column->title == 'ID') {
				$s = 'Id';
			} else {
				$s = $column->title;
			}
			$header[] = iconv('utf-8', 'windows-1250', $s);
		}
		$line = join($delimiter, $header);
		if ($file) {
			fwrite($fp, $line . "\r\n");
		} else {
			$out .= $line . "\r\n";
		}

		$db = Database::singleton();
		$dbRes = $db->query($this->source->getSql());

		foreach ($dbRes as $item) {

			foreach ($this->source->getBinds() as $bind) {
				$this->processBind($item, $bind);
			}
			foreach ($this->source->getSimpleBinds() as $bind) {
				$this->processBindSimple($item, $bind);
			}


			$row = array();
			foreach ($this->getColumnModel()->getVisibleColumns() as $column) {
				$column->setRow($item);
				$value = $column->render();
				$value = str_replace("\n", ' ', $value);
				$value = str_replace("\r", ' ', $value);
				//~ar zmena: je mozne vypnut toto nahradzovanie delimiteru
				if ($delimiterReplacement !== FALSE && $delimiterReplacement !== NULL) {
					$row[] = iconv('utf-8', 'windows-1250' . '//TRANSLIT', str_replace($delimiter, ',', $value));
				} else {
					$row[] = iconv('utf-8', 'windows-1250' . '//TRANSLIT', $value);
				}
			}

			if ($file) {
				fputcsv($fp, $row, $delimiter);
			} else {
				$line = join($delimiter, $row);
				$out .= $line . "\r\n";
			}
		}
		if ($file) {
			fclose($fp);
		} else {
			return $out;
		}
	}


	protected function processBind($item, $bind)
	{
		if (!is_array($bind->child)) {
			$sql = $bind->child;
			$alias = $bind->child;
		} else {
			$sql = key($bind->child);
			$alias = current($bind->child);
		}
		$sub = $sql . ' = ' . $item->{$bind->parent};
		$source = new SQLSource(sprintf($bind->sql, $sub));
		$item->{$bind->name} = $source->getItems();
	}


	protected function processBindSimple($item, $bind)
	{
		if (!is_array($bind->child)) {
			$sql = $bind->child;
			$alias = $bind->child;
		} else {
			$sql = key($bind->child);
			$alias = current($bind->child);
		}
		$sub = $sql . ' = ' . $item->{$bind->parent};
		$source = new SQLSource(sprintf($bind->sql, $sub));
		$items = $source->getItems();
		if ($items->count()) {
			$item->{$bind->name} = current($items[0]);
		} else {
			$item->{$bind->name} = $bind->default;
		}
	}


	protected function initSource()
	{
		$this->source->setFilter($this->getFilter());
		$this->source->setOrder($this->getOrderInfo());
		$this->source->setPage(1);
		$this->source->setLimit(NULL);
	}

}
