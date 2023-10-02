<?php

class Shop_Model_DbTable_Orders extends Zend_Db_Table_Abstract
{

    protected $_name = 'shop_orders';
	protected $_primary = 'id';

	public function getList($clientId = null)
	{
		$select = $this->select();
		//$select->where('scheme = ?', $scheme);
		if (!is_null($clientId)) {
			$select->where('client = ?', $clientId);
		}
		$select->order('created desc');
		return $this->fetchAll($select);
	}
   public function findByNumber($orderNumber)
    {
    	$select = $this->select();
    	$select->where('number = ?', $orderNumber);
    	return $this->fetchRow($select);
    }
	public function getListOld($daysAgo)
	{
		$select = $this->select();
		$date = new Zend_Date();
		$date->subDay($daysAgo);
		$select->where('created < ?', Lv7_Service_Datetime::ZendDateToClearAtom($date));
		//$select->order('created desc');
		return $this->fetchAll($select);
	}


	public function getNewNumberByCurrentYear()
	{
		$db = $this->getAdapter();
		return ((int) $db->fetchOne('SELECT MAX(`number` + 0) as NUMB FROM `' . $this->_name . '` WHERE YEAR(`created`) = ' . date('Y'))) + 1;
	}

	public function getNewNumber()
	{
		$db = $this->getAdapter();
		return ((int) $db->fetchOne('SELECT MAX(`number` + 0) as NUMB FROM `' . $this->_name . '`')) + 1;
	}

	public function getCountOrdersByClient($clientId)
	{
		$db = $this->getAdapter();
		return ((int) $db->fetchOne('SELECT COUNT(*) FROM `' . $this->_name . '` WHERE client = ' . intval($clientId)));
	}

	public function getNewOrders($type = null, $shopId = null)
	{
		$select = $this->select();
		$select->where('status = ?', Shop_Model_Mapper_OrderStatus::NEW_ORDER);
		if (is_array($type)) {
			$select->where('type IN (?)', $type);
		} else if (is_numeric($type)) {
			$select->where('type = ?', $type);
		}
		if (!is_null($shopId)) {
			$select->where('shop = ?', $shopId);
		}
		$select->order('created desc');
		return $this->fetchAll($select);
	}



}

