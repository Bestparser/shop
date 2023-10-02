<?php

class Shop_Form_OrderDocType extends Lv7CMS_Form
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
		
		$element = new Zend_Form_Element_Text('sort');
		$element->setLabel('Сортировка');
		$element->setDescription('Оставьте пусты чтобы заполнить автоматически');
		$element->setAttrib('size', '10');
		$this->addElement($element);
		
		$element = new Zend_Form_Element_Checkbox('activity');
		$element->setLabel('Активность');
		$this->addElement($element);
		
		$this->addPanel(array('name', 'sort', 'activity'), 
			'panel', 
			array(Lv7CMS_Form::D_TWO_COL, Lv7CMS_Form::D_BORDER));
	}


}

