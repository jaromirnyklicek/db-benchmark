<?php



/**
 * Routing debugger for Nette Framework.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2009 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @version    $Id: RoutingDebugger.php 230 2009-03-19 12:16:22Z david@grudl.com $
 */
class RoutingDebugger extends Object
{
	/** @var IRouter */
	private $router;

	/** @var IHttpRequest */
	private $httpRequest;

	/** @var Template */
	private $template;



	/**
	 * Dispatch a HTTP request to a routing debugger.
	 */
	public static function run()
	{
		$debugger = new self(Environment::getApplication()->getRouter(), Environment::getHttpRequest());
		$debugger->show();
		exit;
	}



	public function __construct(IRouter $router, IHttpRequest $httpRequest)
	{
		$this->router = $router;
		$this->httpRequest = $httpRequest;
	}



	/**
	 * Renders debuger output.
	 * @return void
	 */
	public function show()
	{
		$this->template = new Template;
		$this->template->setFile(dirname(__FILE__) . '/RoutingDebugger.phtml');
		$this->template->routers = array();
		$this->analyse($this->router);
		$this->template->render();
	}



	/**
	 * Analyses simple route.
	 * @param  IRouter
	 * @return void
	 */
	private function analyse($router)
	{
		if ($router instanceof MultiRouter) {
			foreach ($router as $subRouter) {
				$this->analyse($subRouter);
			}
			return;
		}

		$appRequest = $router->match($this->httpRequest);
		$matched = $appRequest === NULL ? 'no' : 'may';
		if ($appRequest !== NULL && !isset($this->template->router)) {
			$this->template->router = get_class($router) . ($router instanceof Route ? ' "' . $router->mask . '"' : '');
			$this->template->presenter = $appRequest->getPresenterName();
			$this->template->params = $appRequest->getParams();
			$matched = 'yes';
		}

		$this->template->routers[] = array(
			'matched' => $matched,
			'class' => get_class($router),
			'defaults' => $router instanceof Route || $router instanceof SimpleRouter ? $router->getDefaults() : array(),
			'mask' => $router instanceof Route ? $router->getMask() : NULL,
		);
	}

}
