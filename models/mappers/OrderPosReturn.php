<?php

class Shop_Model_Mapper_OrderPosReturn extends Lv7CMS_Mapper_Abstract
{

	protected $_domainName = 'Shop_Model_OrderPosReturn';
	protected $_tableName = 'Shop_Model_DbTable_OrderPosReturn';


	public function getList($orderId = null)
	{
		$result = $this->_table->getList($orderId);
		return $this->_rowsetToObjList($result);
	}

	public function deleteByOrder($orderId)
	{
		$where = $this->_table->getAdapter()->quoteInto('`order` = ?', $orderId);
		$this->_table->delete($where);
	}


}




