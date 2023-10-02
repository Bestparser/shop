<?php

class Shop_Model_DbTable_OrderPos extends Zend_Db_Table_Abstract
{

    protected $_name = 'shop_order_pos';
	protected $_primary = 'id';

	public function getList($orderId = null, $supplierId = null, $status = null)
	{
		$select = $this->select();
		if (!is_null($orderId)) {
			$select->where('`order` = ?', $orderId);
		}
		if (!is_null($supplierId)) {
			$select->where('`supplier` = ?', $supplierId);
		}
		if (!is_null($status)) {
			$status = (array) $status;
			$select->where('`status` IN (?)', $status);
		}
		$select->order('order desc');
		$select->order('id');
		return $this->fetchAll($select);
	}
	
}

