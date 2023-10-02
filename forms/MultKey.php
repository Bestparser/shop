<?php

class Shop_Form_MultKey extends Lv7CMS_Form
{

	protected function _init()
	{
        $this->setWidth('800');
		$this->setAddStdButton(true);
		
		$element = new Zend_Form_Element_Text('name');
		$element->setLabel('Название');
		$element->setRequired(true);
		$element->setAttrib('size', '40');
		$this->addElement($element);
		
		$element = new Zend_Form_Element_Textarea('keys');
		$element->setLabel('Ключевые слова');
		$element->setRequired(true);
		$element->setAttrib('cols', '50');
		$element->setAttrib('rows', '10');
		$this->addElement($element);
		
		$element = new Zend_Form_Element_Text('mult');
		$element->setLabel('Кратность');
		$element->setRequired(true);
		$element->setAttrib('size', '10');
		$this->addElement($element);
		
		$element = new Zend_Form_Element_Checkbox('activity');
		$element->setLabel('Активность');
		$this->addElement($element);
		
		$this->addPanel(array('name', 'keys', 'mult', 'activity'), 
			'panel', 
			array(Lv7CMS_Form::D_TWO_COL, Lv7CMS_Form::D_BORDER));
	}


}

