<?php

class GettextLatteMacro 
{
	public static function ngettext($s, $p, $n)
	{
		return sprintf(ngettext($s, $p, $n), $n);
	}
}