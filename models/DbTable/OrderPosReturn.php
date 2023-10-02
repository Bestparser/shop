<?php

class Shop_Model_DbTable_OrderPosReturn extends Zend_Db_Table_Abstract
{

    protected $_name = 'shop_order_pos_return';
	protected $_primary = 'id';

	public function getList($orderId)
	{
		$select = $this->select();
		if (!is_null($orderId)) {
			$select->where('`order` = ?', $orderId);
		}
		$select->order('order desc');
		$select->order('id');
		return $this->fetchAll($select);
	}

}

