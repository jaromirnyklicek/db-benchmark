<?php

/**
 * Nette Framework
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette\Application
 */



/**
 * JSON response used for AJAX requests.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Nette\Application
 */
class JsonResponse extends Object implements IPresenterResponse
{
	/** @var array|stdClass */
	private $payload;

	/** @var string */
	private $contentType;



	/**
	 * @param  array|stdClass  payload
	 * @param  string    MIME content type
	 */
	public function __construct($payload, $contentType = NULL)
	{
		if (!is_array($payload) && !($payload instanceof stdClass)) {
			throw new InvalidArgumentException("Payload must be array or anonymous class, " . gettype($payload) . " given.");
		}
		$this->payload = $payload;
		$this->contentType = $contentType ? $contentType : 'application/json';
	}



	/**
	 * @return array|stdClass
	 */
	final public function getPayload()
	{
		return $this->payload;
	}



	/**
	 * Sends response to output.
	 * @return void
	 */
	public function send()
	{
		/*if(isset($this->payload->snippets)) {			
			
			foreach($this->payload->snippets as $key=>$content) {				
				if(preg_match_all('#<script(.|\s)*?\/script>#i', $content, $m)) {
					foreach($m as $items) {
						foreach($items as $item) {
							if(preg_match('#(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)#i', $item, $x)) {								
								$this->payload->eval[] = $x[1].';';
							}
						}
					}			
				}
				$this->payload->snippets[$key] = preg_replace('#<script(.|\s)*?\/script>#', '', $content);
			}
		}*/
		Environment::getHttpResponse()->setContentType($this->contentType);
		Environment::getHttpResponse()->setExpiration(FALSE);
		echo json_encode($this->payload);
	}

}
