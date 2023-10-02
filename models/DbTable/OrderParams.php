<?php

class Shop_Model_DbTable_OrderParams extends Zend_Db_Table_Abstract
{

    protected $_name = 'shop_order_params';
	protected $_primary = 'id';

	/*
	public function getList($orderId)
	{
		$select = $this->select();
		$select->where('order = ?', $orderId);
		return $this->fetchAll($select);
	}
	*/
	
	public function findByEntity($orderId)
	{
		$select = $this->select();
		$select->where('`order` = ?', $orderId);
		return $this->fetchAll($select);
	}
	
	public function findByEntityAndKey($orderId, $key)
	{
		$select = $this->select();
		$select->where('`order` = ?', $orderId);
		$select->where('`key` = ?', $key);
		return $this->fetchRow($select);
	}	
	
	
}

