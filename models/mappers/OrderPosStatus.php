<?php


class Shop_Model_Mapper_OrderPosStatus
{

	const UNORDERED = 10;
	const ORDERED = 20;
	const ON_WAREHOUSE = 30;
	const SENT = 40;
	const NOT_AVAILABLE = 50;
	const CANCEL = 60;

	
    protected $_list = array();
    
    public function __construct()
    {
    	
    	$this->_add(array(
    		'id' => self::UNORDERED,
    		'name' => 'Не заказан',
    	));

    	$this->_add(array(
    		'id' => self::ORDERED,
    		'name' => 'Заказан',
    	));

    	$this->_add(array(
    		'id' => self::ON_WAREHOUSE,
    		'name' => 'На складе',
    	));

    	$this->_add(array(
    		'id' => self::SENT,
    		'name' => 'Отправлен',
    	));
    	
    	$this->_add(array(
    		'id' => self::NOT_AVAILABLE,
    		'name' => 'Нет в наличии',
    	));

    	$this->_add(array(
    		'id' => self::CANCEL,
    		'name' => 'Отмена',
    	));


    	
    }
    
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
	
	protected function _add($data)
	{
		$item = new Shop_Model_OrderPosStatus($data);
		$this->_list[$item->id] = $item;
	}

}

