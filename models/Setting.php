<?php

class Shop_Model_Setting
{
	protected $_code;
	protected $_name;
	protected $_type;
	
	public function __construct($code, $name, $type = Lv7CMS_DataTypes::STRING)
	{
		$this->_code = $code;
		$this->_name = $name;
		$this->_type = $type;
	}
	
	public function getCode()
	{
		return $this->_code;
	}
	
	public function getName()
	{
		return $this->_name;
	}
	
	public function getType()
	{
		return $this->_type;
	}
	
	
}