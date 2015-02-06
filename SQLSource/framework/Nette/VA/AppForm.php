<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com
 * @category   Nette
 * @package    Nette\Application
 * @version    $Id: AppForm.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Application;*/


/**
 * Web form as presenter component.
 *
 * @author     David Grudl, Ondrej Novak
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Application
 */
class AppForm extends /*Nette\Forms\*/Form implements ISignalReceiver
{

    /**
    * Nastavene parametry GETu prenese v action na stranku po odeslani.
    * Nutne nastavovat az po pripojeni formulare k presenteru!
    * 
    * @var mixed
    */
    protected $traceGet = array();
    
       
	/**
	 * Application form constructor.
	 */
	public function __construct(/*Nette\*/IComponentContainer $parent = NULL, $name = NULL)
	{
		$this->monitor('Nette\Application\Presenter');
		parent::__construct($name, $parent);
	}

    public function setTraceGet($value)
    {
        $this->traceGet = $value;
        $this->setAppFormAction();
        return $this;
    }
    
    public function getTraceGet()
    {
        return $this->traceGet;
    }

	/**
	 * Returns the presenter where this component belongs to.
	 * @param  bool   throw exception if presenter doesn't exist?
	 * @return Presenter|NULL
	 */
	public function getPresenter($need = TRUE)
	{
		return $this->lookup('Nette\Application\Presenter', $need);
	}



	/**
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 * @param  IComponent
	 * @return void
	 */
	protected function attached($presenter)
	{
		if ($presenter instanceof Presenter) {
			$this->setAppFormAction();
		}
	}

    protected function setAppFormAction()
    {
        $presenter = $this->getPresenter();
        $tArr = array();
        foreach($this->traceGet as $param) $tArr[$param] = $presenter->getParam($param);

        $this->setAction(new Link(
                $presenter,
                $this->lookupPath('Nette\Application\Presenter') . self::NAME_SEPARATOR . 'submit!',
                $tArr
        ));    
    }
    

	/**
	 * Detects form submission and loads PresenterRequest values.
	 * @return void
	 */
	public function processHttpRequest($foo = NULL)
	{

		$presenter = $this->getPresenter();

		$this->submittedBy = FALSE;
		if (!$presenter->isSignalReceiver($this, 'submit')) return;

		$request = $presenter->getRequest();
		if ($request->isMethod('forward') || $request->isMethod('post') !== $this->isPost) return;

		$this->submittedBy = TRUE;
		if ($this->isPost) {
			// pokud je posilano POSTem, tak nesmi by POST prazdny.
			// Asi nejaky bug v IE pri ajaxovem postu
			// http://stackoverflow.com/questions/4796305/why-does-internet-explorer-not-send-http-post-body-on-ajax-call-after-failure
			// Tato kontrola vyhodi vyjimku a uzivateli se muze napsat, at to zkusi znovu.
			// Jinak to spadne dal v loadHttpData, kde se ocekava v postu _form
			if (count($request->getPost()) == 0) {
				throw new EmptyPostException('Empty post');
			}
			$this->loadHttpData(self::arrayAppend($request->getPost(), $request->getFiles()));

		} else {
			$this->loadHttpData($request->getParams());
		}        
	}



	/********************* interface ISignalReceiver ****************d*g**/



	/**
	 * This method is called by presenter.
	 * @param  string
	 * @return void
	 */
	public function signalReceived($signal)
	{
		if ($signal === 'submit') {
			$this->submit();

		} else {
			throw new BadSignalException("There is no handler for signal '$signal' in '{$this->getClass()}'.");
		}
	}

}
