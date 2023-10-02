<?php

class Shop_Model_Mapper_OrderTypes
{

	const NORMAL = 1;
	const DISTRIB = 2;
	const INNER = 3;
	const VIN = 4;
    const ORDERHALL = 5;
    const NORMALMP = 6;

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
		$this->_list[self::NORMAL] = new Shop_Model_OrderType(array(
			'id' => self::NORMAL,
			'name' => 'Обычный заказ',
			'shortName' => 'О'
		));
        $this->_list[self::NORMALMP] = new Shop_Model_OrderType(array(
            'id' => self::NORMALMP,
            'name' => 'Обычный заказ (МП)',
            'shortName' => 'О+МП'
        ));
		$this->_list[self::DISTRIB] = new Shop_Model_OrderType(array(
			'id' => self::DISTRIB,
			'name' => 'Товары от поставщиков',
			'shortName' => 'П'
		));
		$this->_list[self::INNER] = new Shop_Model_OrderType(array(
			'id' => self::INNER,
			'name' => 'Внутренний заказ',
			'shortName' => 'В'
		));
		$this->_list[self::VIN] = new Shop_Model_OrderType(array(
			'id' => self::VIN,
			'name' => 'Заказ из запроса по VIN',
			'shortName' => 'VIN'
		));
        $this->_list[self::ORDERHALL] = new Shop_Model_OrderType(array(
            'id' => self::ORDERHALL,
            'name' => 'Заказ продавца из зала',
            'shortName' => 'Зал'
        ));


	}


}




