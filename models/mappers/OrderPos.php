<?php

class Shop_Model_Mapper_OrderPos extends Lv7CMS_Mapper_Abstract
{

	protected $_domainName = 'Shop_Model_OrderPos';
	protected $_tableName = 'Shop_Model_DbTable_OrderPos';


	public function getList($orderId = null, $supplierId = null, $status = null)
	{
		$result = $this->_table->getList($orderId, $supplierId, $status);
		return $this->_rowsetToObjList($result);
	}

	public function deleteByOrder($orderId)
	{
		$where = $this->_table->getAdapter()->quoteInto('`order` = ?', $orderId);
		$this->_table->delete($where);
	}


}




