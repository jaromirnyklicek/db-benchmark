<?php

/**
 * Detekce IP adresy uzivatele
 *
 * @author Michal Kvita <michal.kvita@viaaurea.cz> Úpravy
 * @deprecated since version r1071 - použij IpEnvDetector::detectIPBc($asStr);
 */
class IP
{

	public static function detectIP($asStr = TRUE)
	{
		return IpEnvDetector::detectIPBc($asStr);
	}

	public static function isv6()
	{
		return IpEnvDetector::detectIPv6Bc();
	}

}
