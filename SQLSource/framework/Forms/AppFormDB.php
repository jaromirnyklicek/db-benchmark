<?php


/**
 * Databazovy form jako aplikacni komponenta
 *
 * @author	   Ondrej Novak
 */
class AppFormDB extends FormDB implements ISignalReceiver
{
	/**
	 * Nastavene parametry GETu prenese v action na stranku po odeslani.
	 * Nutne nastavovat az po pripojeni formulare k presenteru!
	 *
	 * @var mixed
	 */
	protected $traceGet = array();

	/**
	 * Nadpis formuláře
	 *
	 * @var string
	 */
	public $title = '';


	public function __construct(/* Nette\ */IComponentContainer $parent = NULL, $name = NULL)
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
	 * Nacita prametre pre redirect zo submitu.
	 *
	 * @param FormControl $button submit control
	 * @param array $implicitParams pociatocne parametre
	 * @return array
	 */
	protected function getRedirectParams(FormControl $button, array $implicitParams = array())
	{
		$presenter = $this->getPresenter();
		foreach ($this->traceGet as $param) {
			$implicitParams[$param] = $presenter->getParam($param);
		}
		return parent::getRedirectParams($button, $implicitParams);
	}

	/**
	 * Application form.
	 */


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
		foreach ($this->traceGet as $param) {
			$tArr[$param] = $presenter->getParam($param);
		}

		$this->setAction(new Link(
				$presenter, $this->lookupPath('Nette\Application\Presenter') . self::NAME_SEPARATOR . 'submit!', $tArr
		));
	}


	/**
	 * Detects form submission and loads PresenterRequest values.
	 * @return void
	 */
	public function processHttpRequest($foo = NULL)
	{

		$presenter = $this->getPresenter();
		if (!$presenter->isSignalReceiver($this, 'submit')) {
			return;
		}

		parent::processHttpRequest($foo);
	}

	/*	 * ******************* interface ISignalReceiver ****************d*g* */


	/**
	 * This method is called by presenter.
	 * @param  string
	 * @return void
	 */
	public function signalReceived($signal)
	{
		if ($signal === 'submit') {
			// nove se zpravoana v exec() resp. processHttpRequest();
			//$this->submit();
		} else {
			throw new BadSignalException("There is no handler for signal '$signal' in '{$this->getClass()}'.");
		}
	}

}
