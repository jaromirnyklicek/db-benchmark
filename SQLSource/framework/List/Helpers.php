<?php
/**
* Vykreslovaci callbackove funkce
* pro pouziti v Listech (DataGrid, DataList, DataView)
*
* Rozdíl mezi Helperem a Callbackem je takový, že Helper volá funkci pouze s jedním parametrem a to hodnotou sloupce.
* Kdežto Callback přidává jako druhý parametr DataRow, což je zabalený celý řádek. Lze tak v callbackové získat
* přístup ke všech sloupcům řádku.
*
* @author Ondrej Novak
* @copyright Copyright (c) 2009, Ondrej Novak
*/
class HelpersCore
{

	//******* Helpery *************/

	/**
	* Ignoruje hodnotu a misto ni vykresli obrazek (ikonku)
	*
	* @param mixed $value
	* @param string $img - url k obrazku
	* @param string $alt - tooltip
	*/
	public static function ico($value, $img, $alt = '', $class = NULL)
	{
		return Html::el('img')->src($img)->alt($alt)->title($alt)->class($class);
	}

	/**
	* Ignoruje hodnotu a misto ni vykresli pevný text
	*
	* @param mixed $value
	* @param string $text
	*/
	public static function fixedText($value, $text)
	{
		return $text;
	}

	/**
	* Prida onclick do HTML elementu
	*
	* @param Html $value
	* @param DataRow $dbRow
	* @param string $js - JS funkce
	* @param string $id - sloupec z dbrow jako parameter JS funkce
	*/
	public static function JSFunctionOnClick($value, $dbRow, $js, $id)
	{
		return $value->onclick($js.'('.$dbRow->$id.');return false;')->style('cursor:pointer');
	}


	/**
	* Obali do divu, ktery nezalamuje mezery
	*
	* @param string $value
	* @return string
	*/
	public static function nowrap($value)
	{
		return Html::el('div')->style('white-space: nowrap')->setHtml($value);
	}

	/**
	* Obali hodnotu do mailto odkazu
	*
	* @param string $value
	* @return string
	*/
	public static function mailto($value)
	{
		return Html::el('a')->href('mailto:'.$value)->setText($value);
	}

	/**
	* Obali do divu se stylem
	*/
	public static function divStyle($value, $style = '', $tag = 'div')
	{
		return Html::el($tag)->style($style)->setHtml($value);
	}


	/**
	* Obali do divu s CSS tridou
	*/
	public static function divClass($value, $css = '')
	{
		return Html::el('div')->class($css)->setHtml($value);
	}

	/**
	* Obali do HTML elementu
	*/
	public static function htmlEl($value, $el = NULL)
	{
		if($el === NULL) $el = Html::el();
		return $el->setHtml($value);
	}

	/**
	* Obali do divu, ktery ma pevnou sirku s overflow: hidden.
	*/
	public static function oneline($value)
	{
		return self::divstyle($value, 'overflow: hidden; height: 14px');
	}

	/**
	* Do Html objektu prida confirm() na udalost onclick.
	*/
	public static function jsConfirm($value, $text = 'Přejete si pokračovat?')
	{
		return $value->onclick('return confirm("'.addcslashes($text, '"').'")');
	}

	/**
	* IP adresa prevedena z cisla
	*/
	public static function IP($value)
	{
		return is_numeric($value) ? long2ip($value) : $value;
	}

	/**
	* Překlad přes Gettext
	*/
	public static function gettext($value)
	{
		$s = gettext($value);
		$numargs = func_num_args();
		if($numargs > 1) {
			$args = array_slice(func_get_args(), 1);
			return vsprintf($s, $args);
		}
		else return $s;
	}



	/**
	* Prevede pres dbinfo
	*
	* @param mixed $value
	* @param string $key
	* @return string
	*/
	public static function dbinfo($value, $key)
	{
		if($value == NULL) return '';
		return @dbinfo::get($key, $value);
	}

	/**
	* Spoji pole oddelovacem ($separator) do retezce.
	*
	* @param array $value
	* @param string $separator
	* @param int $limit - pocet prvnim x zaznamu
	* @return string
	*/
	public static function arrayJoin($value, $separator = ', ', $limit = NULL)
	{
		if($value == NULL) return '';

		if($limit && count($value) > $limit) {
			$newvalue = array();
			for($i = 0; $i < $limit; $i++) {
				$newvalue[] = array_shift($value);
			}
			$value = $newvalue;
		}
		return join($separator, $value);
	}

	/**
	* Spoji pole objektu oddelovacem ($separator) do retezce.
	* Z objektu vybere pouze atribut $member.
	*
	* @param array $value
	* @param string $member
	* @param string $separator
	* @return string
	*/
	public static function arrayObjectJoin($value, $member = 'title', $separator = ', ')
	{
		if($value == NULL) return '';
		$arr = array();
		foreach($value as $item) $arr[] = $item->$member;
		return join($separator, $arr);
	}


	/**
	* Prevede objekt na string
	*
	* @param object $value
	* @return string
	*/
	public static function toString($value)
	{
		return (string)$value;
	}

	/**
	* Prevede vteriny na hodinovy format casu
	*
	* @param mixed $time
	*/
	public static function secondsToTime($time, $seconds = TRUE)
	{
		$sign = $time < 0;
		$time = abs($time);
		$arr = array(
		  "years" => 0, "days" => 0, "hours" => 0,
		  "minutes" => 0, "seconds" => 0,
		);
		if($time >= 3600){
		  $arr["hours"] = floor($time/3600);
		  $time = ($time%3600);
		}
		if($time >= 60){
		  $arr["minutes"] = floor($time/60);
		  $time = ($time%60);
		}
		$arr["seconds"] = floor($time);
		$s = '';
		$s .= $arr['hours'].':';
		if($arr['minutes'] < 10) $s .= '0';
		$s .= $arr['minutes'];
		if($seconds) {
			$s .= ':';
			if($arr['seconds'] < 10) $s .= '0';
			$s .= $arr['seconds'];
		}
		return ($sign ? '-' : '') . $s;
	}

	public static function minutesToTime($time)
	{
		if($time === NULL) return;
		return	self::secondsToTime($time * 60, FALSE);
	}

	//******* Callbacks ***********/

	public static function link($value, $data, $destination, $params = array(), $fixedparams = array(), $alt = NULL, $target = NULL)
	{
		$args = array();
		if(!is_array($params)) $params = array($params);
		if(!is_array($fixedparams)) $fixedparams = array($fixedparams);

		foreach ($params as $key => $p) {
			if(empty($key)) $args[] = $data->$p;
			else $args[$key] = $data->$p;
		}
		foreach ($fixedparams as $key => $p) {
			if(empty($key)) $args[] = $data->$p;
			else $args[$key] = $p;
		}
		$url = $data->getParent()->dataList->getPresenter()->link($destination, $args);
		//$ajaxlink = 'return !nette.action(this.href, this)';
		$el = Html::el('a');
		$el->href($url);
		$el->setHtml($value);
		if($alt != NULL) $el->title($alt);
		if($target != NULL) $el->target($target);
		return $el;
	}

	public static function linkDelete($value, $data, $destination, $params = array(), $m, $alt = '', $target = '')
	{
		$args = array();
		if(!is_array($params)) $params = array($params);
		foreach ($params as $key => $p) {
			if(empty($key)) $args[] = $data->$p;
			else $args[$key] = $data->$p;
		}
		$args[] = $m;
		$url = $data->getParent()->dataList->getPresenter()->link($destination, $args);
		$ajaxlink = 'return !nette.action(this.href, this)';
		return '<a href="'.$url.'">'.$value.'</a>';
	}



}
