<?php

class Shop_Model_Mapper_FaceTypes extends Lv7CMS_Mapper_List
{
	const NATURAL = 'natural';
	const LEGAL = 'legal';

	protected function _init()
	{
		$this->_add(self::NATURAL, 'Физическое лицо');
		$this->_add(self::LEGAL, 'Юридическое лицо');
	}
}

