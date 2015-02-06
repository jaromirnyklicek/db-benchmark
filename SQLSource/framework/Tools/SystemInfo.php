<?php

final class SystemInfo {
  
  	/**
  	* Maximální upload souboru
  	* 
  	*/
	public static function getMaxUploadSize()
	{
		$postsize = (ini_get("post_max_size"));
		$uploadsize = (ini_get("upload_max_filesize"));		   
		$val = (int)trim($postsize) < (int)trim($uploadsize) ? $postsize : $uploadsize;
		$last = strtolower($val{strlen($val)-1});
		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}
	
	/**
  	* Zjisteni zateze serveru
  	* Vrati pole s 1min, 5min a 15min loadem
  	*/
	public static function getLoadAvg()
	{
		$stats = exec('uptime');
		if(empty($stats)) return NULL;
		preg_match('/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/', $stats, $regs);
		return array($regs[1], $regs[2], $regs[3]);
	}
}