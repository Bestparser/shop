<?php

class Shop_Form_Mail extends Lv7CMS_Form
{

	protected function _init()
	{
        $this->setWidth('100', '%');
		$this->setAddStdButton(false);
		
		$element = new Lv7CMS_Form_Element_Wysiwyg('mailText');
		$element->setAttrib('height', 400);
		$element->setAttrib('toolbar', 'Basic');
		//$element->setAttrib('siteId', 'planetspares');
		$this->addElement($element);
		
		$element = new Zend_Form_Element_Submit('submit');
		$element->setLabel('Отправить письмо клиенту');
		$element->setAttrib('class', 'button');
		$this->addElement($element);
			
	}


}

