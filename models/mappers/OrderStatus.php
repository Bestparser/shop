<?php


class Shop_Model_Mapper_OrderStatus
{

	const NEW_ORDER = 10;
	const IN_WORK = 20;
	const WAITING = 30;
	const ON_WAREHOUSE = 40;
	const COMPLETE = 50;
	const CANCEL = 60;
    const RETURNED = 70;

    protected $_list = array();

    public function __construct()
    {

    	$this->_add(array(
    		'id' => self::NEW_ORDER,
    		'name' => 'Новый заказ',
    	));

    	$this->_add(array(
    		'id' => self::IN_WORK,
    		'name' => 'В работе',
    	));

    	$this->_add(array(
    		'id' => self::WAITING,
    		'name' => 'Ожидание поступления',
    	));

    	$this->_add(array(
    		'id' => self::ON_WAREHOUSE,
    		'name' => 'Товары на складе',
    	));

    	$this->_add(array(
    		'id' => self::COMPLETE,
    		'name' => 'Выполнен',
    	));

    	$this->_add(array(
    		'id' => self::CANCEL,
    		'name' => 'Отмена',
    	));

        $this->_add(array(
            'id' => self::RETURNED,
            'name' => 'Возврат',
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
		$item = new Shop_Model_OrderStatus($data);
		$this->_list[$item->id] = $item;
	}

}

