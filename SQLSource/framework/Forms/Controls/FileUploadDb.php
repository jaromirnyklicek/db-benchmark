<?php
/**
 * Upload do databáze s napojením na ORM.
 * Před uložením naplní do ORM hodnoty $size, $file_orig, $type
 * Po uložení soubor přesune do Data
 *
 * @author     Ondrej Novak
 * @copyright  Copyright (c) 2009 Ondrej Novak
 * @package    Forms
 */

class FileUploadDb extends FileUpload
{

    public $columnSize = 'size';
    public $columnType = 'type';
    public $columnFile = 'file';

    public $dir = DATA_DIR;

    public function setDir($value)
    {
        $this->dir = $value;
        return $this;
    }

    public function getDir()
    {
        return $this->dir;
    }

    public function beforeSave($orm, $form)
    {
        $file = $this->getValue();
        if($file != NULL && $file->isOK()) {
            // $exts = split("[/\\.]", $file->getName());
            $exts = preg_split("/\./", $file->getName()); // PHP 5.3 deprecated split() nahrazeno za preg_split()
            $n = count($exts)-1;

            $orm->{$this->columnFile} = $file->getName();
            $orm->{$this->columnSize} = $file->getSize();
            if($n == 0) $orm->{$this->columnType} = '';
            else $orm->{$this->columnType} = $exts[$n];
        }
    }

    public function save($orm)
    {
        $file = $this->getValue();
        if($file != NULL && $file->isOK()) {
        	if($orm->{$this->columnType} == "") {
        		$m = $this->dir.'/'.$orm->{$orm->primary_key()};
			}
			else {
				$m = $this->dir.'/'.$orm->{$orm->primary_key()}.'.'.$orm->{$this->columnType};
			}
            $file->move($m);
        }
    }
}
