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
 * @license    http://nettephp.com/license	Nette license
 * @link	   http://nettephp.com
 * @category   Nette
 * @package    Nette\Forms
 * @version    $Id: FileUpload.php 182 2008-12-31 00:28:33Z david@grudl.com $
 */

/*namespace Nette\Forms;*/



/*use Nette\Web\HttpUploadedFile;*/



/**
 * Text box and browse button that allow users to select a file to upload to the server.
 *
 * @author	   David Grudl
 * @copyright  Copyright (c) 2004, 2009 David Grudl
 * @package    Nette\Forms
 */
class FileUpload extends FormControl
{
	/**
	 * Extension validator: Not allowed extensions
	 * @var array
	 */
	public static $notAllowedExtensions = array('php', 'phtml', 'inc');

	protected $showUploadMaxSize = FALSE;

	/**
	 * @param  string  label
	 */
	public function __construct($label)
	{
		$this->monitor('Nette\Forms\Form');
		parent::__construct($label);
		$this->control->type = 'file';

		$this->addRule(array($this, 'validateUPLOAD_ERR_INI_SIZE'), sprintf(_('Překročena maximální velikost souboru %s.'), string::bytes(SystemInfo::getMaxUploadSize())));
		$this->addRule(array($this, 'validateUPLOAD_ERR_FORM_SIZE'), _('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.'));
		$this->addRule(array($this, 'validateUPLOAD_ERR_PARTIAL'), _('The uploaded file was only partially uploaded.'));
		$this->addRule(array($this, 'validateUPLOAD_ERR_NO_TMP_DIR'), _('Missing a temporary folder.'));
		$this->addRule(array($this, 'validateUPLOAD_ERR_CANT_WRITE'), _('Failed to write file to disk.'));
		$this->addRule(array($this, 'validateUPLOAD_ERR_EXTENSION'), _('A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop.'));

		// Extension validation.
		$this->addCondition(Form::FILLED)
			 ->addRule(~Form::EXTENSION, _('Nepovolený typ souboru.'), self::$notAllowedExtensions);
	}

	public function setShowUploadMaxSize($value = TRUE)
	{
		$this->showUploadMaxSize = $value;
		return $this;
	}

	public function getShowUploadMaxSize()
	{
		return $this->showUploadMaxSize;
	}

	/**
	 * Generates control's HTML element.
	 * @return Nette\Web\Html
	 */
	public function getControl()
	{
		$control = Html::el();
		$control->add(parent::getControl());
		if($this->showUploadMaxSize) {
			$control->add('<br/>'._('Max. velikost souboru').': '.String::bytes(SystemInfo::getMaxUploadSize()));
		}
		return $control;
	}

	/**
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 * @param  IComponent
	 * @return void
	 */
	protected function attached($form)
	{
		if ($form instanceof Form) {
			$form->getElementPrototype()->enctype = 'multipart/form-data';
		}

		if ($form instanceof FormBindDB) {
			$form->getMainForm()->getElementPrototype()->enctype = 'multipart/form-data';
		}
	}

	/**
	 * Sets control's value.
	 * @param  array|Nette\Web\HttpUploadedFile
	 * @return void
	 */
	public function setValue($value)
	{
		if (is_array($value)) {
			$this->value = new HttpUploadedFile($value);

		} elseif ($value instanceof HttpUploadedFile) {
			$this->value = $value;

		} else {
			// TODO: or create object?
			$this->value = NULL;
		}
	}

	/**
	 * Filled validator: has been any file uploaded?
	 * @param  IFormControl
	 * @return bool
	 */
	public static function validateFilled(IFormControl $control)
	{
		$file = $control->getValue();
		return $file instanceof HttpUploadedFile && $file->isOK();
	}

	/**
	 * FileSize validator: is file size in limit?
	 * @param  FileUpload
	 * @param  int	file size limit
	 * @return bool
	 */
	public static function validateFileSize(FileUpload $control, $limit)
	{
		$file = $control->getValue();
		return $file instanceof HttpUploadedFile && $file->getSize() <= $limit;
	}

	/**
	 * MimeType validator: has file specified mime type?
	 * @param  FileUpload
	 * @param  string  mime type
	 * @return bool
	 */
	public static function validateMimeType(FileUpload $control, $mimeType)
	{
		$file = $control->getValue();
		if ($file instanceof HttpUploadedFile) {
			$type = $file->getContentType();
			if (!$type) {
				return FALSE; // cannot verify :-(
			}
			$mimeTypes = explode(',', $mimeType);
			if (in_array($type, $mimeTypes, TRUE)) {
				return TRUE;
			}
			if (in_array(preg_replace('#/.*#', '/*', $type), $mimeTypes, TRUE)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Image validator: is file image?
	 * @param  UploadControl
	 * @return bool
	 */
	public static function validateImage(FileUpload $control)
	{
		$file = $control->getValue();
		return $file instanceof HttpUploadedFile && $file->isImage();
	}

	/**
	 * Extension validator: is file extension allowed?
	 * @param FileUpload	$control
	 * @param array			$extensions
	 * @return bool
	 */
	public static function validateExtension(FileUpload $control, $extensions = array())
	{
		$file = $control->getValue();
		if($file instanceof HttpUploadedFile) {
			$ext = strtolower(ltrim(strrchr($file->getName(), "."), "."));

			if($ext === false) {
				return TRUE;
			}

			return in_array($ext, $extensions);
		}

		return FALSE;
	}

	public static function validateUPLOAD_ERR_INI_SIZE($control)
	{
		$file = $control->getValue();
		return !($file instanceof HttpUploadedFile) || $file->getError() != UPLOAD_ERR_INI_SIZE;
	}

	public static function validateUPLOAD_ERR_FORM_SIZE($control)
	{
		$file = $control->getValue();
		return !($file instanceof HttpUploadedFile) || $file->getError() != UPLOAD_ERR_FORM_SIZE;
	}

	public static function validateUPLOAD_ERR_PARTIAL($control)
	{
		$file = $control->getValue();
		return !($file instanceof HttpUploadedFile) || $file->getError() != UPLOAD_ERR_PARTIAL;
	}

	public static function validateUPLOAD_ERR_NO_TMP_DIR($control)
	{
		$file = $control->getValue();
		return !($file instanceof HttpUploadedFile) || $file->getError() != UPLOAD_ERR_NO_TMP_DIR;
	}

	public static function validateUPLOAD_ERR_CANT_WRITE($control)
	{
		$file = $control->getValue();
		return !($file instanceof HttpUploadedFile) || $file->getError() != UPLOAD_ERR_CANT_WRITE;
	}

	public static function validateUPLOAD_ERR_EXTENSION($control)
	{
		$file = $control->getValue();
		return !($file instanceof HttpUploadedFile) || $file->getError() != UPLOAD_ERR_EXTENSION;
	}
}
