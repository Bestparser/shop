<?php

class Shop_Model_Mapper_OrderPosTypes
{

	const WAREHOUSE = 1;
	const SUPPLIERS = 2;
	const MANUAL = 3;
	const PARTNER = 4;
	
	
	protected $_list = array();
	
	
	public function getList()
	{
		return $this->_list;
	}
	
	public function findAll()
	{
		return $this->_list;
	}
	
	public function find($id)
	{
		return $this->_list[$id];
	}
	
	public function __construct()
	{
		$this->_list[self::WAREHOUSE] = new Shop_Model_OrderPosType(array(
			'id' => self::WAREHOUSE,
			'name' => 'На складе'));
		$this->_list[self::SUPPLIERS] = new Shop_Model_OrderPosType(array(
			'id' => self::SUPPLIERS,
			'name' => 'Товар от поставщика'));
		$this->_list[self::MANUAL] = new Shop_Model_OrderPosType(array(
			'id' => self::MANUAL,
			'name' => 'Товар, добавленный вручную'));
		$this->_list[self::PARTNER] = new Shop_Model_OrderPosType(array(
				'id' => self::PARTNER,
			'name' => 'Товар с партнерских складов'));
		
	}
	
	
}




